<?php

namespace WPSP\API;

use WPSP\Helper;

/**
 * AI Caption API Handler
 *
 * Generates social captions for a post via the OpenAI Chat Completions API.
 * Reads the OpenAI API key saved on the SchedulePress Settings → AI panel
 * (`openai_api_key` in the `wpsp_settings_v5` option).
 *
 * Route: POST wp-scheduled-posts/v1/ai-caption/{post_id}
 * Response: { success: true, captions: { facebook: "...", twitter: "..." } }
 *
 * @since 5.3.0
 */
class AICaption
{
    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * OpenAI chat completions endpoint.
     */
    const OPENAI_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    /**
     * OpenAI models endpoint — used to cheaply validate an API key.
     */
    const OPENAI_MODELS_ENDPOINT = 'https://api.openai.com/v1/models';

    /**
     * Per-platform display name + character budget used to guide the model.
     *
     * @var array
     */
    protected $platform_meta = [
        'facebook'        => ['name' => 'Facebook', 'limit' => 63206],
        'twitter'         => ['name' => 'X (Twitter)', 'limit' => 280],
        'linkedin'        => ['name' => 'LinkedIn', 'limit' => 3000],
        'pinterest'       => ['name' => 'Pinterest', 'limit' => 500],
        'instagram'       => ['name' => 'Instagram', 'limit' => 2200],
        'medium'          => ['name' => 'Medium', 'limit' => 45000],
        'threads'         => ['name' => 'Threads', 'limit' => 500],
        'google_business' => ['name' => 'Google Business Profile', 'limit' => 1500],
    ];

    /**
     * Initialize hooks.
     */
    private function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register the AI caption REST route.
     */
    public function register_routes()
    {
        $namespace = WPSP_PLUGIN_SLUG . '/v1';

        register_rest_route($namespace, 'ai-caption/(?P<post_id>\d+)', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'generate_captions'),
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
            'args'                => array(
                'post_id' => array(
                    'required'          => true,
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));

        // Validate an OpenAI API key from the settings panel.
        register_rest_route($namespace, 'ai-test-key', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'test_connection'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));
    }

    /**
     * Test an OpenAI API key by hitting the models endpoint.
     *
     * Accepts an `api_key` param (the value currently typed into the settings
     * field) and falls back to the saved key when none is supplied.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function test_connection($request)
    {
        $api_key = $request->get_param('api_key');
        $api_key = is_string($api_key) ? trim($api_key) : '';

        if (empty($api_key)) {
            $saved   = Helper::get_settings('openai_api_key');
            $api_key = is_string($saved) ? trim($saved) : '';
        }

        if (empty($api_key)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Please enter an API key before testing the connection.', 'wp-scheduled-posts'),
            ), 400);
        }

        $response = wp_remote_get(self::OPENAI_MODELS_ENDPOINT, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
        ));

        if (is_wp_error($response)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => $response->get_error_message(),
            ), 502);
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code === 200) {
            return new \WP_REST_Response(array(
                'success' => true,
                'message' => __('Connection successful. Your API key is valid.', 'wp-scheduled-posts'),
            ), 200);
        }

        $data    = json_decode(wp_remote_retrieve_body($response), true);
        $message = isset($data['error']['message'])
            ? $data['error']['message']
            : __('Connection failed. Please verify your API key and try again.', 'wp-scheduled-posts');

        return new \WP_REST_Response(array(
            'success' => false,
            'message' => $message,
        ), 200);
    }

    /**
     * Generate captions for the selected platforms.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function generate_captions($request)
    {
        $post_id = (int) $request->get_param('post_id');

        if (!get_post($post_id) || !current_user_can('edit_post', $post_id)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Post not found or insufficient permissions.', 'wp-scheduled-posts'),
            ), 403);
        }

        $api_key = Helper::get_settings('openai_api_key');
        $api_key = is_string($api_key) ? trim($api_key) : '';

        if (empty($api_key)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('OpenAI API key is not configured. Add it in SchedulePress → Settings → AI.', 'wp-scheduled-posts'),
            ), 400);
        }

        // Read and sanitize request payload.
        $platforms = (array) $request->get_param('platforms');
        $platforms = array_values(array_intersect(
            array_map('sanitize_key', $platforms),
            array_keys($this->platform_meta)
        ));

        if (empty($platforms)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Please select at least one social platform.', 'wp-scheduled-posts'),
            ), 400);
        }

        $prompt            = sanitize_textarea_field((string) $request->get_param('prompt'));
        $auto_generate     = filter_var($request->get_param('autoGenerate'), FILTER_VALIDATE_BOOLEAN);
        $tone              = sanitize_text_field((string) $request->get_param('tone')) ?: 'professional';
        $length            = sanitize_key((string) $request->get_param('length')) ?: 'auto';
        $generate_hashtags = filter_var($request->get_param('generateHashtags'), FILTER_VALIDATE_BOOLEAN);
        $include_emojis    = filter_var($request->get_param('includeEmojis'), FILTER_VALIDATE_BOOLEAN);

        // Post context.
        $post          = get_post($post_id);
        $post_title    = get_the_title($post_id);
        $post_content  = wp_strip_all_tags($post->post_content);
        $post_content  = trim(preg_replace('/\s+/', ' ', $post_content));
        $post_excerpt  = function_exists('mb_substr') ? mb_substr($post_content, 0, 1500) : substr($post_content, 0, 1500);
        $permalink     = get_permalink($post_id);

        $messages = $this->build_messages(
            $platforms,
            compact('prompt', 'auto_generate', 'tone', 'length', 'generate_hashtags', 'include_emojis'),
            array(
                'title'     => $post_title,
                'excerpt'   => $post_excerpt,
                'permalink' => $permalink,
            )
        );

        $captions = $this->request_openai($api_key, $messages, $platforms);

        if (is_wp_error($captions)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => $captions->get_error_message(),
            ), 502);
        }

        return new \WP_REST_Response(array(
            'success'  => true,
            'captions' => $captions,
        ), 200);
    }

    /**
     * Build the OpenAI chat messages (system + user).
     *
     * @param array $platforms
     * @param array $opts
     * @param array $post
     * @return array
     */
    protected function build_messages($platforms, $opts, $post)
    {
        // Character ranges the AI generates to. Keep these in sync with
        // LENGTH_OPTIONS in src/components/modals/socialTemplates/AICaptionDrawer.js.
        $length_guide = array(
            'auto'   => 'an appropriate length for each platform',
            'short'  => 'short and punchy — roughly 50-120 characters (1-2 sentences)',
            'medium' => 'a moderate length — roughly 120-250 characters (2-4 sentences)',
            'long'   => 'detailed and engaging — roughly 250-500 characters (multiple sentences)',
        );
        $length_text = isset($length_guide[$opts['length']]) ? $length_guide[$opts['length']] : $length_guide['auto'];

        // Per-platform constraints. Use the limits configured in settings (the
        // same ones template validation enforces) so generated captions fit.
        $limits         = Helper::get_social_platform_limits();
        $platform_lines = array();
        foreach ($platforms as $key) {
            $meta             = $this->platform_meta[$key];
            $limit            = isset($limits[$key]) ? $limits[$key] : $meta['limit'];
            $platform_lines[] = sprintf('- "%s" (%s, max %d characters — never exceed this)', $key, $meta['name'], $limit);
        }
        $platform_block = implode("\n", $platform_lines);
        $keys_list      = '"' . implode('", "', $platforms) . '"';

        $system = 'You are an expert social media copywriter. You write engaging, platform-native captions that drive engagement. '
            . 'You always respond with a single valid JSON object and nothing else — no markdown, no code fences, no commentary.';

        $user  = "Write a social media caption for each of the following platforms, tailored to each platform's style and character limit:\n";
        $user .= $platform_block . "\n\n";
        $user .= "Post title: " . $post['title'] . "\n";
        if (!empty($post['excerpt'])) {
            $user .= "Post content: " . $post['excerpt'] . "\n";
        }
        if (!empty($post['permalink'])) {
            $user .= "Post URL: " . $post['permalink'] . "\n";
        }
        $user .= "\nRequirements:\n";
        if ($opts['tone'] === 'post_specific') {
            // Match the post's own voice instead of applying a fixed preset.
            $user .= "- Tone/style: analyze the tone, voice, and writing style of the post title and content above, then write each caption to match that same tone and style.\n";
        } else {
            $user .= "- Tone/style: {$opts['tone']}.\n";
        }
        $user .= "- Length: {$length_text}.\n";
        $user .= '- Hashtags: ' . ($opts['generate_hashtags'] ? 'include a few relevant hashtags.' : 'do not include hashtags.') . "\n";
        $user .= '- Emojis: ' . ($opts['include_emojis'] ? 'use tasteful, relevant emojis.' : 'do not use emojis.') . "\n";

        if (!empty($opts['prompt'])) {
            $user .= "- Extra instructions from the user: {$opts['prompt']}\n";
        } elseif (!empty($opts['auto_generate'])) {
            $user .= "- Base the captions on the post title and content above.\n";
        }

        $user .= "\nReturn a JSON object whose keys are exactly {$keys_list} and whose values are the caption strings. Respect each platform's character limit.";

        return array(
            array('role' => 'system', 'content' => $system),
            array('role' => 'user', 'content' => $user),
        );
    }

    /**
     * Call the OpenAI API and parse captions out of the response.
     *
     * @param string $api_key
     * @param array  $messages
     * @param array  $platforms
     * @return array|\WP_Error  Map of platform => caption, or WP_Error on failure.
     */
    protected function request_openai($api_key, $messages, $platforms)
    {
        $model = apply_filters('wpsp_openai_model', 'gpt-4o-mini');

        $body = array(
            'model'           => $model,
            'messages'        => $messages,
            'temperature'     => 0.7,
            'response_format' => array('type' => 'json_object'),
        );

        $response = wp_remote_post(self::OPENAI_ENDPOINT, array(
            'timeout' => 60,
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body'    => wp_json_encode($body),
        ));

        if (is_wp_error($response)) {
            return new \WP_Error('wpsp_openai_request_failed', $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $raw  = wp_remote_retrieve_body($response);
        $data = json_decode($raw, true);

        if ($code !== 200) {
            $message = isset($data['error']['message'])
                ? $data['error']['message']
                : __('OpenAI request failed. Please verify your API key and try again.', 'wp-scheduled-posts');
            return new \WP_Error('wpsp_openai_error', $message);
        }

        $content = isset($data['choices'][0]['message']['content'])
            ? $data['choices'][0]['message']['content']
            : '';

        if (empty($content)) {
            return new \WP_Error('wpsp_openai_empty', __('OpenAI returned an empty response.', 'wp-scheduled-posts'));
        }

        $parsed = json_decode($content, true);

        // Fallback: if the model didn't return clean JSON, apply the text to every platform.
        if (!is_array($parsed)) {
            $text   = trim($content);
            $parsed = array();
            foreach ($platforms as $key) {
                $parsed[$key] = $text;
            }
        }

        // Keep only requested platforms and coerce to trimmed strings.
        // Hard-trim to each platform's character limit so the generated caption
        // always passes template validation when the user saves it.
        $limits   = Helper::get_social_platform_limits();
        $captions = array();
        foreach ($platforms as $key) {
            if (isset($parsed[$key])) {
                $caption = is_string($parsed[$key]) ? trim($parsed[$key]) : trim(wp_json_encode($parsed[$key]));
                $limit   = isset($limits[$key]) ? $limits[$key] : 0;
                $captions[$key] = $this->enforce_limit($caption, $limit);
            }
        }

        if (empty($captions)) {
            return new \WP_Error('wpsp_openai_unparsable', __('Could not parse captions from the AI response.', 'wp-scheduled-posts'));
        }

        return $captions;
    }

    /**
     * Trim a caption to a platform character limit without cutting mid-word.
     *
     * Counts characters (not bytes) so emojis are handled correctly, and backs
     * up to the last whitespace when possible to avoid a chopped word.
     *
     * @param string $text
     * @param int    $limit  Character limit; 0 or less means "no limit".
     * @return string
     */
    protected function enforce_limit($text, $limit)
    {
        $limit  = (int) $limit;
        $length = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);

        if ($limit <= 0 || $length <= $limit) {
            return $text;
        }

        $cut  = function_exists('mb_substr') ? mb_substr($text, 0, $limit) : substr($text, 0, $limit);
        $space = function_exists('mb_strrpos') ? mb_strrpos($cut, ' ') : strrpos($cut, ' ');

        // Only honour the word boundary if it does not discard too much text.
        if ($space !== false && $space > (int) ($limit * 0.6)) {
            $cut = function_exists('mb_substr') ? mb_substr($cut, 0, $space) : substr($cut, 0, $space);
        }

        return rtrim($cut);
    }

    /**
     * Return an instance of this class.
     *
     * @return object
     */
    public static function get_instance()
    {
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }
}
