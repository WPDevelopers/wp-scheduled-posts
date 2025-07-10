<?php

namespace WPSP\Helpers;
use WPSP\Helper;

/**
 * Custom Template Helper Class
 * 
 * Provides utility methods for managing custom social media templates
 * 
 * @since 2.6.0
 */
class CustomTemplateHelper
{
    /**
     * Get custom template for a specific post-profile combination
     *
     * @param int $post_id
     * @param string $platform
     * @param string $profile_id
     * @return string|false
     */
    public static function get_custom_template( $post_id, $platform, $profile_id ) {
        $templates = self::get_migrated_templates($post_id);
        if (!isset($templates[$platform])) {
            return false;
        }
        return $templates[$platform]['template'];
    }

    /**
     * Check if a custom template exists for a post-profile combination
     * 
     * @param int $post_id
     * @param string $platform
     * @param string $profile_id
     * @return bool
     */
    public static function has_custom_template( $post_id, $platform, $profile_id ) {
        return self::get_custom_template($post_id, $platform, $profile_id) !== false;
    }

    /**
     * Get all custom templates for a post
     *
     * @param int $post_id
     * @return array
     */
    public static function get_all_templates( $post_id ) {
        return self::get_migrated_templates($post_id);
    }

    /**
     * Get all templates for a specific platform
     *
     * @param int $post_id
     * @param string $platform
     * @return array
     */
    public static function get_platform_templates( $post_id, $platform ) {
        $templates = self::get_migrated_templates($post_id);

        if (!isset($templates[$platform])) {
            return array();
        }

        return $templates[$platform];
    }

    /**
     * Save custom template for a post-profile combination
     *
     * @param int $post_id
     * @param string $platform
     * @param string $profile_id
     * @param string $template
     * @return bool
     */
    public static function save_template( $post_id, $platform, $profile_id, $template ) {
        $templates = self::get_migrated_templates($post_id);

        // Ensure platform exists in structure
        if (!isset($templates[$platform])) {
            $templates[$platform] = array();
        }

        // Save template in hierarchical structure
        $templates[$platform][$profile_id] = $template;

        return update_post_meta($post_id, '_wpsp_custom_templates', $templates) !== false;
    }

    /**
     * Delete custom template for a post-profile combination
     *
     * @param int $post_id
     * @param string $platform
     * @param string $profile_id
     * @return bool
     */
    public static function delete_template( $post_id, $platform, $profile_id ) {
        $templates = self::get_migrated_templates($post_id);

        if (!isset($templates[$platform][$profile_id])) {
            return false;
        }

        // Remove template from hierarchical structure
        unset($templates[$platform][$profile_id]);

        // Keep platform as empty array for consistency
        if (empty($templates[$platform])) {
            $templates[$platform] = array();
        }

        return update_post_meta($post_id, '_wpsp_custom_templates', $templates) !== false;
    }

    /**
     * Get template with fallback hierarchy
     * 
     * Priority: Post-Profile Custom Template → Global Platform Template
     * 
     * @param int $post_id
     * @param string $platform
     * @param string $profile_id
     * @return string
     */
    public static function get_resolved_template( $post_id, $platform, $profile_id ) {
        // First, try to get custom template for this post-profile combination
        $custom_template = self::get_custom_template($post_id, $platform, $profile_id);
        
        if ($custom_template !== false) {
            return $custom_template;
        }
        
        // Fallback to global platform template
        return self::get_global_platform_template($platform);
    }

    /**
     * Get global platform template from settings
     * 
     * @param string $platform
     * @return string
     */
    public static function get_global_platform_template( $platform ) {
        $settings = \WPSP\Helper::get_settings('social_templates');
        if (!$settings || !isset($settings->$platform)) {
            return self::get_default_template();
        }
        
        $platform_settings = json_decode(json_encode($settings->$platform), true);
        
        return isset($platform_settings['template_structure']) 
            ? $platform_settings['template_structure'] 
            : self::get_default_template();
    }

    /**
     * Get default template structure
     * 
     * @return string
     */
    public static function get_default_template() {
        return '{title}{content}{url}{tags}';
    }

    /**
     * Validate template content
     * 
     * @param string $template
     * @param string $platform
     * @return array
     */
    public static function validate_template( $template, $platform ) {
        // Platform character limits
        $limits = array(
            'twitter' => 280,
            'facebook' => 63206,
            'linkedin' => 3000,
            'pinterest' => 500,
            'instagram' => 2200,
            'medium' => 100000,
            'threads' => 500
        );

        // Check if template is empty
        if (empty(trim($template))) {
            return array(
                'valid' => false,
                'message' => __('Template cannot be empty.', 'wp-scheduled-posts')
            );
        }

        // Check character limit for platform
        $limit = isset($limits[$platform]) ? $limits[$platform] : 1000;
        if (strlen($template) > $limit) {
            return array(
                'valid' => false,
                'message' => sprintf(
                    __('Template exceeds character limit for %s (%d/%d characters).', 'wp-scheduled-posts'),
                    ucfirst($platform),
                    strlen($template),
                    $limit
                )
            );
        }

        // Validate placeholder syntax
        $valid_placeholders = array('{title}', '{content}', '{url}', '{tags}');
        preg_match_all('/\{[^}]+\}/', $template, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[0] as $placeholder) {
                if (!in_array($placeholder, $valid_placeholders)) {
                    return array(
                        'valid' => false,
                        'message' => sprintf(
                            __('Invalid placeholder "%s". Valid placeholders are: %s', 'wp-scheduled-posts'),
                            $placeholder,
                            implode(', ', $valid_placeholders)
                        )
                    );
                }
            }
        }

        return array(
            'valid' => true,
            'message' => __('Template is valid.', 'wp-scheduled-posts')
        );
    }

    /**
     * Get platform character limits
     * 
     * @return array
     */
    public static function get_platform_limits() {
        return array(
            'twitter' => 280,
            'facebook' => 63206,
            'linkedin' => 3000,
            'pinterest' => 500,
            'instagram' => 2200,
            'medium' => 100000,
            'threads' => 500
        );
    }

    /**
     * Get template preview with post data
     * 
     * @param string $template
     * @param int $post_id
     * @return string
     */
    public static function get_template_preview( $template, $post_id ) {
        $post = get_post($post_id);
        if (!$post) {
            return $template;
        }

        $title = get_the_title($post_id);
        $content = wp_strip_all_tags($post->post_content);
        $content = substr($content, 0, 100) . '...';
        $url = get_permalink($post_id);
        $tags = '#example #tags';

        $preview = str_replace(
            array('{title}', '{content}', '{url}', '{tags}'),
            array($title, $content, $url, $tags),
            $template
        );

        return $preview;
    }

    /**
     * Get templates with migration from old flat structure to new hierarchical structure
     *
     * @param int $post_id
     * @return array
     */
    private static function get_migrated_templates( $post_id ) {
        $templates = get_post_meta($post_id, '_wpsp_custom_templates', true);
        return $templates;
    }

    /**
     * Migrate old flat template structure to new hierarchical structure
     *
     * @param array $templates
     * @return array
     */
    private static function migrate_template_structure( $templates ) {
        $new_structure = array(
            'facebook' => array(),
            'twitter' => array(),
            'linkedin' => array(),
            'pinterest' => array(),
            'instagram' => array(),
            'medium' => array(),
            'threads' => array()
        );

        // Migrate old platform_profileId format to new hierarchical format
        foreach ($templates as $key => $template) {
            if (is_string($template) && strpos($key, '_') !== false) {
                $parts = explode('_', $key, 2);
                if (count($parts) === 2) {
                    $platform = $parts[0];
                    $profile_id = $parts[1];

                    if (isset($new_structure[$platform])) {
                        $new_structure[$platform][$profile_id] = $template;
                    }
                }
            } elseif (is_array($template)) {
                // Already in new format, preserve it
                $new_structure[$key] = $template;
            }
        }
        return $new_structure;
    }

    public static function get_scheduled_datetime($data, $base_datetime = null) {
        $now = $base_datetime
            ? new \DateTime($base_datetime, new \DateTimeZone('UTC'))
            : new \DateTime('now', new \DateTimeZone('UTC'));
    
        $dateOption = $data['dateOption'];
        $timeOption = $data['timeOption'];
    
        // Handle date
        switch ($dateOption) {
            case 'today':
                $date = clone $now;
                break;
            case 'tomorrow':
                $date = (clone $now)->modify('+1 day');
                break;
            case 'next_week':
                $date = (clone $now)->modify('+7 days');
                break;
            case 'next_month':
                $date = (clone $now)->modify('+1 month');
                break;
            case 'in_days':
                $days = (int) $data['customDays'];
                $date = (clone $now)->modify("+{$days} days");
                break;
            case 'custom_date':
                if (!empty($data['customDate'])) {
                    $date = \DateTime::createFromFormat('Y-m-d', $data['customDate'], new \DateTimeZone('UTC'));
                    if (!$date) return null;
                    $date->setTime($now->format('H'), $now->format('i'), $now->format('s'));
                } else {
                    return null;
                }
                break;
            case 'same_day':
                $date = clone $now;
                break;
            case 'day_after':
                $date = (clone $now)->modify('+1 day');
                break;
            case 'week_after':
                $date = (clone $now)->modify('+7 days');
                break;
            case 'month_after':
                $date = (clone $now)->modify('+1 month');
                break;
            case 'days_after':
                if (!empty($data['customDays']) && is_numeric($data['customDays'])) {
                    $days = (int) $data['customDays'];
                    $date = (clone $now)->modify("+{$days} days");
                } else {
                    return null;
                }
                break;
            default:
                return null;
        }
    
        // Handle time
        switch ($timeOption) {
            case 'now':
                $final_datetime = clone $date;
                $current_time = new \DateTime('now', new \DateTimeZone('UTC'));
                $final_datetime->setTime(
                    (int)$current_time->format('H'),
                    (int)$current_time->format('i'),
                    (int)$current_time->format('s')
                );
                break;
    
            case 'in_1h':
                $final_datetime = clone $date;
                $current_time = new \DateTime('now', new \DateTimeZone('UTC'));
                $current_time->modify("+" . rand(10, 60) . " minutes");
                $final_datetime->setTime(
                    (int)$current_time->format('H'),
                    (int)$current_time->format('i'),
                    (int)$current_time->format('s')
                );
                break;
    
            case 'in_3h':
                $final_datetime = clone $date;
                $current_time = new \DateTime('now', new \DateTimeZone('UTC'));
                $current_time->modify("+" . rand(60, 180) . " minutes");
                $final_datetime->setTime(
                    (int)$current_time->format('H'),
                    (int)$current_time->format('i'),
                    (int)$current_time->format('s')
                );
                break;
    
            case 'in_5h':
                $final_datetime = clone $date;
                $current_time = new \DateTime('now', new \DateTimeZone('UTC'));
                $current_time->modify("+" . rand(180, 300) . " minutes");
                $final_datetime->setTime(
                    (int)$current_time->format('H'),
                    (int)$current_time->format('i'),
                    (int)$current_time->format('s')
                );
                break;
    
            case 'in_hours':
                $hours = max(1, (int) $data['customHours']);
                $final_datetime = clone $date;
                $current_time = new \DateTime('now', new \DateTimeZone('UTC'));
                $randomOffset = rand(($hours - 1) * 60, ($hours + 1) * 60);
                $current_time->modify("+{$randomOffset} minutes");
                $final_datetime->setTime(
                    (int)$current_time->format('H'),
                    (int)$current_time->format('i'),
                    (int)$current_time->format('s')
                );
                break;
    
            case 'custom_time':
                if (!empty($data['customTime'])) {
                    $timeParts = explode(':', $data['customTime']);
                    if (count($timeParts) >= 2) {
                        $final_datetime = clone $date;
                        $final_datetime->setTime((int)$timeParts[0], (int)$timeParts[1], 0);
                    } else {
                        return null;
                    }
                } else {
                    return null;
                }
                break;
    
            case 'same_time':
                $final_datetime = clone $date;
                break;
    
            case 'hour_after':
                $randomMinutes = rand(10, 60);
                $final_datetime = (clone $date)->modify("+{$randomMinutes} minutes");
                break;
    
            case 'three_hours_after':
                $randomMinutes = rand(60, 180);
                $final_datetime = (clone $date)->modify("+{$randomMinutes} minutes");
                break;
    
            case 'five_hours_after':
                $randomMinutes = rand(180, 300);
                $final_datetime = (clone $date)->modify("+{$randomMinutes} minutes");
                break;
    
            case 'hours_after':
                if (!empty($data['customHours']) && is_numeric($data['customHours'])) {
                    $hours = (int) $data['customHours'];
                    $randomMinutes = rand(($hours - 1) * 60, ($hours + 1) * 60);
                    $final_datetime = (clone $date)->modify("+{$randomMinutes} minutes");
                } else {
                    return null;
                }
                break;
    
            default:
                return null;
        }
    
        return isset($final_datetime) ? $final_datetime->format('Y-m-d H:i:s') : null;
    }
    
}
