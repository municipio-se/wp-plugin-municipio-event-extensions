<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

final class Assets
{
    public function register(): void
    {
        // Run after the module and shared front enqueue callbacks, before the
        // theme captures its footer. Rendering view data is too late here.
        add_action('wp_enqueue_scripts', [$this, 'removeReplacedScripts'], PHP_INT_MAX);
    }

    public function removeReplacedScripts(): void
    {
        if (is_admin()) {
            return;
        }

        // Index is replaced; the legacy list is already server rendered and
        // its pagination belongs to Municipio's component library.
        wp_dequeue_script('modularity-event');

        if ($this->needsLegacyFront()) {
            return;
        }

        wp_dequeue_script('event-integration');

        // Do not dequeue React or jQuery: WordPress resolves any remaining
        // consumers' dependencies. Keep styles used by the legacy list.
    }

    private function needsLegacyFront(): bool
    {
        // Municipio 6 event views render pagination, occasions and map links
        // in PHP. Only the legacy consumers below need the shared bundle.
        if (is_active_widget(false, false, 'display_events', true)) {
            return true;
        }

        $post = get_post();
        if (!$post instanceof \WP_Post) {
            return true;
        }

        // The older event-detail shortcodes can render the #event-map canvas;
        // unlike the current view's ordinary map link it needs event-map.js.
        foreach (['event_submit_form', 'single_event_information', 'single_event_accordion'] as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        $context = \Modularity\Helper\Wp::getArchiveSlug() ?: $post->ID;
        // Reuse the module-placement inventory populated by Modularity's
        // enqueue callbacks (including templates, widgets and shortcodes).
        // Unknown context must retain the shared legacy functionality.
        $modules = wp_cache_get('modularity_has_modules_' . $context);
        return (
            !is_array($modules)
            || in_array('mod-event-submit', $modules, true)
            || in_array('mod-location', $modules, true)
        );
    }
}
