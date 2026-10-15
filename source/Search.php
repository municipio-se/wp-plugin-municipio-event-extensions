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
        foreach (FilterRequest::KEYS as $key) {
            $remove[] = $key;
            $remove[] = FilterRequest::name($module, $key);
        }
        $base = remove_query_arg($remove);
        $hidden = [];
        // GET submission replaces the query string. Preserve other modules and
        // routing parameters as controls, including array-valued filters.
        QueryFields::hidden(array_diff_key(wp_unslash($_GET), array_flip($remove)), '', $hidden);
        $controls = [];
        $state = Filters::state($module);
        foreach ([
            'startDate' => 'dates',
            'endDate' => 'dates',
            'minAge' => 'age_group',
            'maxAge' => 'age_group',
            'categories' => 'categories',
            'tags' => 'tags',
        ] as $key => $setting) {
            $visible = (bool) ($module['settings']['mod_event_filter_' . $setting] ?? false);
            $name = FilterRequest::name($module, $key);
            $controls[$key] = ['name' => $name, 'visible' => $visible, 'value' => $state[$key]];
            if (in_array($key, ['categories', 'tags'], true)) {
                $controls[$key]['options'] = TaxonomyFilter::options($module, $key);
            }
            if (!$visible) {
                QueryFields::hidden([$name => $state[$key]], '', $hidden);
            }
        }
        $searchVisible = (bool) ($module['settings']['mod_event_filter_search'] ?? false);
        if (!$searchVisible && self::value($module) !== '') {
            $hidden[] = ['name' => $parameter, 'value' => self::value($module)];
        }
        return [
            'enabled' => $searchVisible || in_array(true, array_column($controls, 'visible'), true),
            'searchVisible' => $searchVisible,
            'controls' => $controls,
            'errors' => $state['errors'],
            'name' => $parameter,
            'value' => self::value($module),
            'action' => strtok($base, '?') . '#event-module-' . absint($module['ID']),
            'reset' => $base . '#event-module-' . absint($module['ID']),
            'hidden' => $hidden,
        ];
    }
}
