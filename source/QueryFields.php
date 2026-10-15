<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

final class QueryFields
{
    public static function hidden(array $values, string $prefix, array &$result, int $depth = 0): void
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
