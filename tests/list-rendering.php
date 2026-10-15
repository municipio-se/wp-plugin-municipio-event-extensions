<?php

// Run with wp eval-file tests/list-rendering.php LIST_MODULE_ID.
$post = get_post((int) ($args[0] ?? 0));
if (!$post instanceof WP_Post || get_field('mod_event_display', $post->ID) !== 'list') {
    throw new RuntimeException('Provide an existing list event module.');
}
$GLOBALS['post'] = $post;
$module = new EventManagerIntegration\Module\Event($post);
$module->template();
$directory = EVENTMANAGERINTEGRATION_PATH . 'source/php/Module/Event/views/';
$expected = EventManagerIntegration\Helper\RenderBlade::blade('event-list', $module->data, $directory);
$actual = EventManagerIntegration\Helper\RenderBlade::blade('event-list', $module->data, dirname(__DIR__) . '/views');
// Component IDs vary between renders; compare the visible list content.
$text = static fn(string $html): string => preg_replace('/\s+/u', ' ', trim(strip_tags($html)));
if ($text($expected) === '' || $text($actual) !== $text($expected)) {
    throw new RuntimeException('Delegated list must preserve the upstream rendered content.');
}
echo "List inheritance and partial rendering passed.\n";
