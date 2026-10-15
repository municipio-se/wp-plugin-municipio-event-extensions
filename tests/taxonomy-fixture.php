<?php

// Local-only fixture: temporarily attach terms to existing event posts and always restore them.
use MunicipioEventExtensions\EventManagerSource;
use MunicipioEventExtensions\FilterRequest;

if (wp_get_environment_type() !== 'local') {
    throw new RuntimeException('This fixture may only run in a local environment.');
}
$post = get_post((int) ($args[0] ?? 0));
$GLOBALS['post'] = $post;
$module = new EventManagerIntegration\Module\Event($post);
$module->template();
$data = $module->data;
$source = new EventManagerSource();
$_GET = [];
$events = $source->occurrences($data);
$ids = array_values(array_unique(array_map(
    static fn(array $event): int => (int) explode(':', $event['key'])[0],
    $events,
)));
if (count($ids) < 2) {
    throw new RuntimeException('Two current events are required.');
}
$created = [];
$original = [];
try {
    foreach (['categories', 'tags'] as $key) {
        $taxonomy = 'event_' . $key;
        $term = wp_insert_term('Temporary filter verification ' . wp_generate_uuid4(), $taxonomy);
        if (is_wp_error($term)) {
            throw new RuntimeException($term->get_error_message());
        }
        $created[$taxonomy] = (int) $term['term_id'];
        $original[$taxonomy] = wp_get_object_terms($ids[0], $taxonomy, ['fields' => 'ids']);
        wp_set_object_terms($ids[0], [$created[$taxonomy]], $taxonomy, true);
        $data[$key] = wp_json_encode([['id' => $created[$taxonomy], 'title' => 'Temporary fixture']]);
        $_GET = [];
        $_GET[FilterRequest::name($data, $key)] = [(string) $created[$taxonomy]];
        $matches = $source->occurrences($data);
        $matchedIds = array_values(array_unique(array_map(
            static fn(array $event): int => (int) explode(':', $event['key'])[0],
            $matches,
        )));
        if ($matchedIds !== [$ids[0]]) {
            throw new RuntimeException('Taxonomy filter did not isolate the tagged event.');
        }
    }
    foreach ($created as $taxonomy => $termId) {
        $_GET[FilterRequest::name($data, substr($taxonomy, 6))] = [(string) $termId];
    }
    if (count($source->occurrences($data)) !== count($matches)) {
        throw new RuntimeException('Combined category/tag selection changed the matching occurrences.');
    }
    echo "Category, tag and combined selection passed.\n";
} finally {
    foreach ($created as $taxonomy => $termId) {
        if (isset($original[$taxonomy]) && !is_wp_error($original[$taxonomy])) {
            wp_set_object_terms($ids[0], array_map('intval', $original[$taxonomy]), $taxonomy);
        }
        wp_delete_term($termId, $taxonomy);
    }
    $_GET = [];
}
