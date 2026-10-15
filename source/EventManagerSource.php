<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

use DateTimeImmutable;
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
        $settings = $module['settings'];
        $today = new DateTimeImmutable('today', wp_timezone());
        $days = max(0, (int) ($settings['mod_event_interval'] ?? 0));
        $params = [
            'start_date' => $today->format('Y-m-d H:i:s'),
            'end_date' => $today->modify("+{$days} days")->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
            'display_limit' => -1,
            'categories' => $this->terms($module, 'categories'),
            'tags' => $this->terms($module, 'tags'),
            'groups' => $this->ids($module['groups']),
            'lang' => is_string($module['lang']) ? $module['lang'] : '',
            'location' => ['lat' => (float) $module['lat'], 'lng' => (float) $module['lng']],
            'distance' => max(0, (float) $module['distance']),
            'age_min' => (int) $module['age_from'],
            'age_max' => (int) $module['age_to'],
        ];
        // Match the existing index REST request. Its display_limit is unlimited;
        // mod_event_limit/hide_past_events belong to the legacy list query.
        // Avoid the upstream cache: its key omits the page argument.
        $events = QueryEvents::getEventsByInterval($params);
        return array_map(EventManagerOccurrence::normalize(...), $events);
    }

    private function terms(array $module, string $taxonomy): array
    {
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
