<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

final class CardSupport
{
    public static function accepts(array $data): bool
    {
        foreach (['search', 'categories', 'tags', 'dates', 'age_group'] as $filter) {
            if ((bool) ($data['settings']['mod_event_filter_' . $filter] ?? false)) {
                return false;
            }
        }
        foreach (['searchString', 'categories', 'tags', 'startDate', 'endDate', 'minAge', 'maxAge'] as $key) {
            if ((bool) ($_GET[$key] ?? false)) {
                return false;
            }
        }
        return !(bool) ($data['mobileHorizontalTrack'] ?? false);
    }
}
