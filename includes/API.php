<?php

namespace WPSP;



class API
{
    public function __construct()
    {
        $this->load_settings_API();
        add_filter('jwt_auth_whitelist', array($this, 'whitelist_API'));
    }
    public function load_settings_API()
    {
        API\Settings::get_instance();
    }
    public function whitelist_API($endpoints)
    {
        $endpoints[] = '/wp-json/wp-scheduled-posts/v1/*';
        return $endpoints;
    }
}
