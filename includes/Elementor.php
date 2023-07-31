<?php

namespace WPSP;

class Elementor
{
    public function __construct()
    {
        add_filter( 'elementor/frontend/section/should_render', [ $this, 'content_render' ], 10, 2 );
        add_filter( 'elementor/frontend/container/should_render', [ $this, 'content_render' ], 10, 2 );
    }

    public function content_render( $should_render, \Elementor\Element_Base $element ) {
        $settings  = $element->get_settings_for_display();
        $section_publish_on     = !empty( $settings['wpsp_section_publish_on'] ) ? $settings['wpsp_section_publish_on'] : '';
        $section_republish_on   = !empty( $settings['wpsp_section_republish_on'] ) ? $settings['wpsp_section_republish_on'] : '';
        $section_unpublish_on   = !empty( $settings['wpsp_section_unpublish_on'] ) ? $settings['wpsp_section_unpublish_on'] : '';
        if( $section_republish_on && ( strtotime( $section_republish_on ) < current_time('timestamp') ) ) {
            return $should_render;
        }else if( $section_publish_on && ( strtotime( $section_publish_on ) ) > current_time('timestamp') || $section_unpublish_on && ( strtotime( $section_unpublish_on ) < current_time('timestamp') )  ) {
            return false;
        }
        return $should_render;
    }
}
