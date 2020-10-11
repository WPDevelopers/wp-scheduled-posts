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
    public static function add_sub_tab($tab, $sub_tab)
    {

        self::$tabs[$tab]['sub_tabs'][$sub_tab['id']] = $sub_tab;
    }
    public static function add_sub_field($parent_tab_name, $sub_tab, $fields)
    {
        return self::$tabs[$parent_tab_name]['sub_tabs'][$sub_tab]['fields'][]  = $fields;
    }
    public static function add_field($tabname, $fields)
    {
        return self::$fields[$tabname][]  = $fields;
    }

    public static function build_settings($tabs, $fields)
    {
        foreach ($fields as $key => $value) {
            $tabs[$key]['fields'] = $value;
            error_log(print_r($value, true));
        }
        return array_values($tabs);
    }
}
