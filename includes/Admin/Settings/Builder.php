<?php

namespace WPSP\Admin\Settings;

class Builder
{
    public static $tabs = array();
    public static $fields = array();
    public static function load()
    {
        return self::build_settings(self::$tabs, self::$fields);
    }

    public static function add_tab($tab)
    {
        // Bail if not array.
        if (!is_array($tab)) {
            return false;
        }

        // Assign to the tabs array
        return self::$tabs[$tab['id']] = $tab;
    }
    public static function add_field($tabname, $fields)
    {
        return self::$fields[$tabname][]  = $fields;
    }

    public static function build_settings($tabs, $fields)
    {
        foreach ($fields as $key => $value) {
            $tabs[$key]['fields'] = $value;
        }
        return array_values($tabs);
    }
}
