<?php

namespace WPSP;



class API
{
    public function __construct()
    {
        $this->load_settings_API();
    }
    public function load_settings_API()
    {
        API\Settings::get_instance();
    }
}
