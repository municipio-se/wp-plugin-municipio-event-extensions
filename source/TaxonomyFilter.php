<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

final class TaxonomyFilter
{
    public static function options(array $module, string $taxonomy): array
    {
        $values = $module[$taxonomy] ?? [];
        $values = is_string($values) ? json_decode($values, true) : $values;
        return is_array($values) ? $values : [];
    }

    public static function selected(array $module, string $taxonomy): array
    {
        $values = FilterRequest::input($module, $taxonomy);
        $values = is_array($values) ? $values : [$values];
        $ids = [];
        foreach ($values as $value) {
            if (is_scalar($value) && ctype_digit((string) $value)) {
                $ids[] = (int) $value;
            }
        }
        return array_values(array_intersect(
            array_unique($ids),
            array_map('intval', array_column(self::options($module, $taxonomy), 'id')),
        ));
    }
}
