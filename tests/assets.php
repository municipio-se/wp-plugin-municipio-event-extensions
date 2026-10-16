<?php

// Run with wp eval-file; only the current process's script registry and query
// are changed. No site settings or content are written.
global $wp_scripts, $wp_query, $post;
$wp_scripts = new WP_Scripts();
$wp_query = new WP_Query();
$post = clone get_post((int) get_option('page_on_front'));
$post->post_content = '';
$originalModules = wp_cache_get('modularity_has_modules_' . $post->ID);
$originalArchiveModules = wp_cache_get('modularity_has_modules_archive-event');
$wp_query->queried_object = $post;
$wp_query->queried_object_id = $post->ID;
$wp_query->is_singular = true;
add_filter('sidebars_widgets', static fn(): array => []);

$assets = new MunicipioEventExtensions\Assets();
$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$enqueue = static function (): void {
    wp_register_script('react', '/react.js');
    wp_register_script('react-dom', '/react-dom.js', ['react']);
    wp_enqueue_script('modularity-event', '/event.js', ['react', 'react-dom']);
    wp_enqueue_script('event-integration', '/front.js');
};

foreach ([['mod-event'], ['mod-event', 'mod-event-submit'], ['mod-location'], null] as $modules) {
    wp_cache_delete('modularity_has_modules_' . $post->ID);
    if ($modules !== null) {
        wp_cache_set('modularity_has_modules_' . $post->ID, $modules);
    }
    $enqueue();
    $assets->removeReplacedScripts();
    $check(!wp_script_is('modularity-event', 'enqueued'), 'Legacy module must be removed.');
    $check(
        wp_script_is('event-integration', 'enqueued') === ($modules !== ['mod-event']),
        'Unexpected front bundle state for ' . json_encode($modules),
    );
}

wp_cache_set('modularity_has_modules_' . $post->ID, ['mod-event']);
$post->post_content = '[event_submit_form]';
$enqueue();
$assets->removeReplacedScripts();
$check(wp_script_is('event-integration', 'enqueued'), 'Retain shortcode form support.');
$post->post_content = '';
$post->post_type = 'event';
$enqueue();
$assets->removeReplacedScripts();
$check(!wp_script_is('event-integration', 'enqueued'), 'PHP event details need no legacy front bundle.');
foreach (['single_event_information', 'single_event_accordion'] as $shortcode) {
    $post->post_content = '[' . $shortcode . ']';
    $enqueue();
    $assets->removeReplacedScripts();
    $check(wp_script_is('event-integration', 'enqueued'), 'Retain legacy shortcode map support.');
}
$post->post_content = '';
$wp_query->is_singular = false;
$wp_query->is_archive = true;
$wp_query->is_post_type_archive = true;
$wp_query->query_vars['post_type'] = 'event';
wp_cache_set('modularity_has_modules_archive-event', []);
$enqueue();
$assets->removeReplacedScripts();
$check(!wp_script_is('event-integration', 'enqueued'), 'PHP event archives need no legacy front bundle.');
wp_cache_set('modularity_has_modules_archive-event', ['mod-event-submit']);
$enqueue();
$assets->removeReplacedScripts();
$check(wp_script_is('event-integration', 'enqueued'), 'Archive form modules retain their scripts.');
wp_cache_delete('modularity_has_modules_archive-event');
if ($originalArchiveModules !== false) {
    wp_cache_set('modularity_has_modules_archive-event', $originalArchiveModules);
}
$wp_query->is_archive = false;
$wp_query->is_post_type_archive = false;
$wp_query->is_singular = true;
$wp_query->query_vars = [];
$post->post_type = 'page';

$assets->removeReplacedScripts();
$wp_scripts->all_deps($wp_scripts->queue);
$check(!in_array('react', $wp_scripts->to_do, true), 'Unused React must not be resolved.');
$check(!in_array('react-dom', $wp_scripts->to_do, true), 'Unused ReactDOM must not be resolved.');

wp_enqueue_script('another-consumer', '/consumer.js', ['react-dom']);
$assets->removeReplacedScripts();
$wp_scripts->all_deps($wp_scripts->queue);
$check(in_array('react', $wp_scripts->to_do, true), 'Other consumers must retain React.');
$check(in_array('react-dom', $wp_scripts->to_do, true), 'Other consumers must retain ReactDOM.');
$check(!in_array('modularity-event', $wp_scripts->to_do, true), 'Removed bundle must not be resolved.');
wp_cache_delete('modularity_has_modules_' . $post->ID);
if ($originalModules !== false) {
    wp_cache_set('modularity_has_modules_' . $post->ID, $originalModules);
}
echo "Asset isolation passed.\n";
