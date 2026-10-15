<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

final class CardSupport
{
    public static function accepts(array $data): bool
    {
        return !(bool) ($data['mobileHorizontalTrack'] ?? false);
    }
}
