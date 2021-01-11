<?php

namespace WPSP\Admin\Settings;

class Data
{
    public static $setting_array = [];
    public static $settings;
    public static $option;

    public function __construct($option, $settings)
    {
        self::$option = $option;
        self::$setting_array =  $settings;
    }

    /**
     * Set default settings data in database
     *
     * @return null save option in database
     *
     * @since 1.0.0
     */
    public function save_option_value($type)
    {
        $field = array();
        foreach (self::$setting_array as $setting_item) {
            if (!isset($setting_item['group'])) {
                //normal field
                if (isset($setting_item['fields'])) {
                    foreach ($setting_item['fields'] as $fieldItem) {
                        if (isset($fieldItem['default'])) {
                            $field[$fieldItem['id']] = $fieldItem['default'];
                        }
                    }
                }
            } else {
                // group field
                foreach ($setting_item['group'] as $groupKey => $groupItem) {
                    $group = [];
                    if (isset($groupItem['fields'])) {
                        foreach ($groupItem['fields'] as $groupField) {
                            $group[][$groupField['id']] = (isset($groupField['default']) ? $groupField['default'] : '');
                        }
                    }
                    $field[$setting_item['id']][$groupKey] = $group;
                }
            }
        }
        if (get_option(self::$option) !== false) {
            $default = \json_decode(get_option(self::$option), true);
            $value = \wp_parse_args($default, $field);
            update_option(self::$option, \json_encode($value));
        } else {
            add_option(self::$option, \json_encode($field));
        }
    }
}
