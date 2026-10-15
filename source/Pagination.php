<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

final class Pagination
{
    public static function links(int $page, int $pages, string $base, string $parameter, int $moduleId): array
    {
        $first = max(1, min($page - 2, $pages - 4));
        $last = min($pages, $first + 4);
        $numbers = range($first, $last);
        if ($first > 1) {
            $numbers = [1, null, ...$numbers];
        }
        if ($last < $pages) {
            $numbers = [...$numbers, null, $pages];
        }
        return array_map(static fn(?int $number): array => [
            'number' => $number,
            'current' => $number === $page,
            'edge' => $number === null || $number < $first || $number > $last,
            'href' => $number === null
                ? null
                : add_query_arg($parameter, $number, $base) . '#event-module-' . $moduleId,
        ], $numbers);
    }
}
