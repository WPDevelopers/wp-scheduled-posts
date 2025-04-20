<?php 
namespace WPSP;

class Post 
{
    public function __construct()
    {
        // Print modal HTML to footer of all editors + frontend
        add_action('admin_footer', [$this, 'post_modal_options']); // Classic + Gutenberg
        add_action('elementor/editor/footer', [$this, 'post_modal_options']); // Elementor

        add_action('add_meta_boxes', [$this, 'wpsp_add_metabox']);
    }

    public function wpsp_add_metabox() {
        add_meta_box(
            'wpsp_modal_metabox',
            __('SchedulePress', 'myplugin'),
            [$this, 'render_classic_metabox'],
            null, // applies to all post types
            'side',
            'default'
        );
    }
    
    public function render_classic_metabox($post) {
        echo '<button type="button" class="button button-primary" onclick="mypluginOpenModal()">' . esc_html__('Editor Panel', 'myplugin') . '</button>';
    }
    

    public function post_modal_options() {
        ?>
            <div id="wpsp-post-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
                <div style="background:#fff; margin:100px auto; padding:20px; width:500px; border-radius:8px; position:relative;">
                    <h2><?php esc_html_e('My Plugin Modal', 'myplugin'); ?></h2>
                    <p><?php esc_html_e('This content is coming from PHP.', 'myplugin'); ?></p>
                    <button id="wpsp-modal-close"><?php esc_html_e('Close', 'myplugin'); ?></button>
                </div>
            </div>
        <?php
    }
    
}
