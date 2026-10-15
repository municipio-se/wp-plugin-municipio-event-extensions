<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

use EventManagerIntegration\Helper\QueryEvents;

final class EventManagerSource implements EventSource
{
    public function occurrences(array $module): array
    {
        $module += [
            'groups' => [],
            'lang' => '',
            'lat' => 0,
            'lng' => 0,
            'distance' => 0,
            'age_from' => 0,
            'age_to' => 0,
        ];
        $state = Filters::state($module);
        if ($state['errors'] !== []) {
            return [];
        }
        $params = [
            'start_date' => $state['startDate'] . ' 00:00:00',
            'end_date' => $state['endDate'] . ' 23:59:59',
            'display_limit' => -1,
            'search_string' => Search::value($module),
            'categories' => $this->terms($module, 'categories'),
            'tags' => $this->terms($module, 'tags'),
            'groups' => $this->ids($module['groups']),
            'lang' => is_string($module['lang']) ? $module['lang'] : '',
            'location' => ['lat' => (float) $module['lat'], 'lng' => (float) $module['lng']],
            'distance' => max(0, (float) $module['distance']),
            'age_min' => $state['minAge'],
            'age_max' => $state['maxAge'],
        ];
        // Match the existing index REST request. Its display_limit is unlimited;
        // mod_event_limit/hide_past_events belong to the legacy list query.
        // Avoid the upstream cache: its key omits the page argument.
        $events = QueryEvents::getEventsByInterval($params);
        return array_map(EventManagerOccurrence::normalize(...), $events);
    }

    private function terms(array $module, string $taxonomy): array
    {
        $selected = TaxonomyFilter::selected($module, $taxonomy);
        if ($selected !== []) {
            return $selected;
        }
        if ((bool) ($module['settings']['mod_event_' . $taxonomy . '_show'] ?? false)) {
            return [];
        }
        $values = $module[$taxonomy] ?? [];
        $values = is_string($values) ? json_decode($values, true) : $values;
        return $this->ids(array_column(is_array($values) ? $values : [], 'id'));
    }

    private function ids(mixed $values): array
    {
        $values = is_string($values) ? json_decode($values, true) : $values;
        return array_values(array_unique(array_filter(array_map('absint', is_array($values) ? $values : []))));
    }
}
