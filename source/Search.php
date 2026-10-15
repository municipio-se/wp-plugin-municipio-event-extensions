<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

final class Search
{
    public static function parameter(array $module): string
    {
        return 'event_search_' . absint($module['ID']);
    }

    public static function value(array $module): string
    {
        $value = $_GET[self::parameter($module)] ?? $_GET['searchString'] ?? '';
        return is_string($value) ? mb_substr(sanitize_text_field(wp_unslash($value)), 0, 200) : '';
    }

    public static function view(array $module): array
    {
        $parameter = self::parameter($module);
        $remove = ['currentPage', 'event_page_' . absint($module['ID']), 'searchString', $parameter];
        $base = remove_query_arg($remove);
        $hidden = [];
        // GET submission replaces the query string. Preserve other modules and
        // routing parameters as controls, including array-valued filters.
        self::hidden(array_diff_key(wp_unslash($_GET), array_flip($remove)), '', $hidden);
        return [
            'enabled' => (bool) ($module['settings']['mod_event_filter_search'] ?? false),
            'name' => $parameter,
            'value' => self::value($module),
            'action' => strtok($base, '?') . '#event-module-' . absint($module['ID']),
            'reset' => $base . '#event-module-' . absint($module['ID']),
            'hidden' => $hidden,
        ];
    }

    private static function hidden(array $values, string $prefix, array &$result, int $depth = 0): void
    {
        if ($depth > 5) {
            return;
        }
        foreach ($values as $key => $value) {
            $name = $prefix === '' ? (string) $key : $prefix . '[' . $key . ']';
            if (is_array($value)) {
                self::hidden($value, $name, $result, $depth + 1);
            } elseif (is_scalar($value)) {
                $result[] = ['name' => $name, 'value' => (string) $value];
            }
        }
    }
}
