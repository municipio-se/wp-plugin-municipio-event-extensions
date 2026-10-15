<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

use DateTimeImmutable;

final class Filters
{
    public static function state(array $module): array
    {
        $today = new DateTimeImmutable('today', wp_timezone());
        $days = max(0, (int) ($module['settings']['mod_event_interval'] ?? 0));
        $defaults = [
            'startDate' => $today->format('Y-m-d'),
            'endDate' => $today->modify("+{$days} days")->format('Y-m-d'),
        ];
        $state = ['errors' => []];
        foreach ($defaults as $key => $default) {
            $value = FilterRequest::input($module, $key);
            $value = is_string($value) && $value !== '' ? $value : $default;
            $date = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)
                ? DateTimeImmutable::createFromFormat('!Y-m-d', $value, wp_timezone())
                : false;
            if (!$date || $date->format('Y-m-d') !== $value) {
                $state['errors'][] = __('Enter a valid date.', 'municipio-event-extensions');
                $value = $default;
            }
            $state[$key] = $value;
        }
        if ($state['startDate'] > $state['endDate']) {
            $state['errors'][] = __('The end date must not precede the start date.', 'municipio-event-extensions');
        }
        foreach (['minAge' => 'age_from', 'maxAge' => 'age_to'] as $key => $defaultKey) {
            $raw = FilterRequest::input($module, $key);
            $value = $raw === '' ? (int) ($module[$defaultKey] ?? 0) : $raw;
            $value = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 99]]);
            if ($value === false) {
                $state['errors'][] = __('Enter an age between 0 and 99.', 'municipio-event-extensions');
                $value = 0;
            }
            $state[$key] = (int) $value;
        }
        if ($state['maxAge'] > 0 && $state['minAge'] > $state['maxAge']) {
            $state['errors'][] = __(
                'The maximum age must not be lower than the minimum age.',
                'municipio-event-extensions',
            );
        }
        foreach (['categories', 'tags'] as $taxonomy) {
            $state[$taxonomy] = TaxonomyFilter::selected($module, $taxonomy);
        }
        return $state;
    }
}
