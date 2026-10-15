<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

interface EventSource
{
    /** Return normalized occurrences; presentation must not depend on imported metadata. */
    public function occurrences(array $module): array;
}
