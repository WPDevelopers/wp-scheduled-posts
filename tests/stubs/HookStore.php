<?php

namespace WPSP\Tests\Stubs;

/**
 * Minimal filter/action registry for the unit suite.
 *
 * Priority ordering is honoured because the registry seams rely on it; nothing
 * else about WordPress's hook system is reproduced.
 */
class HookStore
{
    /** @var array<string,array<int,callable[]>> */
    private static $hooks = array();

    public static function add($hook, $callback, $priority = 10)
    {
        self::$hooks[$hook][$priority][] = $callback;
    }

    public static function remove($hook, $callback)
    {
        if (!isset(self::$hooks[$hook])) {
            return;
        }

        foreach (self::$hooks[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $index => $registered) {
                if ($registered === $callback) {
                    unset(self::$hooks[$hook][$priority][$index]);
                }
            }
        }
    }

    /**
     * @return callable[] In priority order.
     */
    public static function callbacks($hook)
    {
        if (empty(self::$hooks[$hook])) {
            return array();
        }

        $by_priority = self::$hooks[$hook];
        ksort($by_priority);

        $flat = array();
        foreach ($by_priority as $callbacks) {
            foreach ($callbacks as $callback) {
                $flat[] = $callback;
            }
        }

        return $flat;
    }

    public static function has($hook)
    {
        return !empty(self::callbacks($hook));
    }

    /**
     * @param string|null $hook Null clears everything.
     */
    public static function reset($hook = null)
    {
        if ($hook === null) {
            self::$hooks = array();
            return;
        }

        unset(self::$hooks[$hook]);
    }
}
