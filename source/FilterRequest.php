<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

final class FilterRequest
{
    public const KEYS = ['startDate', 'endDate', 'categories', 'tags', 'minAge', 'maxAge'];

    public static function name(array $module, string $key): string
    {
        return 'event_' . $key . '_' . absint($module['ID']);
    }

    public static function input(array $module, string $key): mixed
    {
        $name = self::name($module, $key);
        if (array_key_exists($name, $_GET)) {
            return wp_unslash($_GET[$name]);
        }
        // React's old URLs repeat taxonomy keys without PHP's [] notation.
        if (in_array($key, ['categories', 'tags'], true)) {
            $values = [];
            foreach (explode('&', $_SERVER['QUERY_STRING'] ?? '') as $pair) {
                [$field, $value] = array_pad(explode('=', $pair, 2), 2, '');
                if (urldecode($field) === $key) {
                    $values[] = urldecode($value);
                }
            }
            if ($values !== []) {
                return $values;
            }
        }
        return wp_unslash($_GET[$key] ?? '');
    }
}
