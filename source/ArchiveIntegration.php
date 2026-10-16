<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

use EventManagerIntegration\EventArchive;

final class ArchiveIntegration
{
    /**
     * Upstream exposes no instance accessor. Reuse its registered instance:
     * constructing another would duplicate SQL hooks, and the query hook is
     * registered only when its occasions table exists.
     *
     * @mago-expect lint:no-global
     */
    public static function enabled(): ?EventArchive
    {
        $hooks = $GLOBALS['wp_filter']['pre_get_posts']->callbacks ?? [];
        foreach ($hooks as $callbacks) {
            foreach ($callbacks as $callback) {
                $function = $callback['function'];
                if (is_array($function) && $function[0] instanceof EventArchive && $function[1] === 'filterEvents') {
                    return $function[0];
                }
            }
        }

        return null;
    }
}
