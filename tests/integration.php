<?php

// Run through `wp eval-file tests/integration.php MODULE_ID` on a local test site.
use MunicipioEventExtensions\Cards;
use MunicipioEventExtensions\EventManagerSource;
use MunicipioEventExtensions\Search;

$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$post = get_post((int) ($args[0] ?? 0));
$check($post instanceof WP_Post && $post->post_type === 'mod-event', 'Provide an existing event module ID.');
$GLOBALS['post'] = $post;
$module = new EventManagerIntegration\Module\Event($post);
$module->template();
$data = $module->data;
$source = new EventManagerSource();
$events = $source->occurrences($data);
$check(count($events) > 0, 'Fixture must contain current event occurrences.');
$check(count(array_unique(array_column($events, 'key'))) === count($events), 'Occurrence identity must be unique.');

// Compare the adapter against the existing API contract, including occurrence order.
$request = new WP_REST_Request('GET', '/wp/v2/event/module');
$today = new DateTimeImmutable('today', wp_timezone());
$params = [
    'module_id' => (int) $post->ID,
    'settings' => wp_json_encode($data['settings']),
    'start_date' => $today->format('Y-m-d'),
    'end_date' => $today->modify('+' . (int) $data['settings']['mod_event_interval'] . ' days')->format('Y-m-d'),
    'page' => 1,
    'per_page' => -1,
    'groups' => json_decode($data['groups'], true),
    'categories' => [],
    'tags' => [],
];
foreach ($params as $key => $value) {
    $request->set_param($key, $value);
}
$response = rest_do_request($request);
$check($response->get_status() === 200, 'Existing API must provide the comparison fixture.');
$keys = array_map(
    static fn(object $event): string => $event->ID . ':' . $event->start_date . ':' . $event->end_date,
    $response->get_data(),
);
$check($keys === array_column($events, 'key'), 'Adapter must preserve baseline occurrence order and selection.');

$cards = new Cards(dirname(__DIR__), $source);
$_GET = [];
$first = $cards->data($data)['eventExtensions'];
$check(count($first['cards']) > 0, 'First page must be server-rendered.');
$expectedCount = $data['settings']['mod_event_pagination']
    ? min(count($events), (int) $data['settings']['mod_event_per_page'])
    : count($events);
$check(count($first['cards']) === $expectedCount, 'Card count must follow the index pagination settings.');
$allLinks = [];
for ($page = 1; $page <= $first['pages']; $page++) {
    $_GET = ['event_page_' . $post->ID => (string) $page];
    array_push($allLinks, ...array_column($cards->data($data)['eventExtensions']['cards'], 'link'));
}
$check(count($allLinks) === count($events), 'Pagination must include every occurrence exactly once.');
$check(count(array_unique($allLinks)) === count($allLinks), 'Pages must not repeat occurrence links.');
$unlimited = $data;
$unlimited['settings']['mod_event_pagination'] = true;
$unlimited['settings']['mod_event_per_page'] = -1;
$unlimitedResult = $cards->data($unlimited)['eventExtensions'];
$check(
    count($unlimitedResult['cards']) === count($events) && $unlimitedResult['pages'] === 1,
    'Legacy -1 page size must remain unlimited.',
);
$_GET = ['event_page_' . $post->ID => '999999'];
$check(
    $cards->data($data)['eventExtensions']['page'] === $first['pages'],
    'Out-of-range page must clamp to last page.',
);
if ($first['pages'] > 1) {
    $_GET = ['event_page_' . $post->ID => '2'];
    $second = $cards->data($data)['eventExtensions'];
    $check($first['cards'][0]['link'] !== $second['cards'][0]['link'], 'Pagination must change occurrence links.');
}
$_GET = ['event_page_' . $post->ID => ['invalid']];
$check($cards->data($data)['eventExtensions']['page'] === 1, 'Array-valued page input must be rejected.');
$_GET = ['searchString' => 'existing-filter-link'];
$check(Search::value($data) === 'existing-filter-link', 'Legacy search URLs must remain supported.');
$check($cards->data($data)['eventExtensions'] !== null, 'Search links must work without React.');
$_GET = [Search::parameter($data) => 'unlikely-event-match-7e0a931'];
$check($source->occurrences($data) === [], 'Search must constrain the actual database query.');
$_GET = [Search::parameter($data) => $events[0]['title']];
$matches = $source->occurrences($data);
$check(
    in_array($events[0]['key'], array_column($matches, 'key'), true),
    'Title search must retain matching occurrences.',
);
$other = $data;
$other['ID'] = $post->ID + 1;
$check(Search::value($other) === '', 'Module search must not affect other modules.');
$_GET = [Search::parameter($data) => ['invalid']];
$check(Search::value($data) === '', 'Array-valued search must be ignored.');
$_GET = ['event_page_' . $post->ID => '4', 'event_page_other' => '2', 'keep' => ['a', 'b']];
$controls = Search::view($data)['hidden'];
$check(
    !in_array('event_page_' . $post->ID, array_column($controls, 'name'), true),
    'Search submission must reset its page.',
);
$check(
    in_array(['name' => 'event_page_other', 'value' => '2'], $controls, true),
    'Other module pages must survive submission.',
);
$check(
    in_array(['name' => 'keep[1]', 'value' => 'b'], $controls, true),
    'Array query parameters must survive submission.',
);
$_GET = [];
$data['settings']['mod_event_filter_dates'] = true;
$check($cards->data($data)['eventExtensions'] === null, 'Unimplemented filter controls must retain legacy rendering.');
$data['settings']['mod_event_filter_dates'] = false;
$data['mobileHorizontalTrack'] = true;
$check($cards->data($data)['eventExtensions'] === null, 'Mobile carousel must retain legacy rendering.');
$data['mobileHorizontalTrack'] = false;
$empty = new Cards(dirname(__DIR__), new class implements \MunicipioEventExtensions\EventSource {
    public function occurrences(array $module): array
    {
        return [];
    }
});
$emptyResult = $empty->data($data)['eventExtensions'];
$check(
    $emptyResult['cards'] === [] && $emptyResult['pages'] === 1,
    'Empty results must not fall back to a JS-only mount.',
);
$renderData = $cards->data($data);
$renderData['eventExtensions']['search']['enabled'] = true;
$renderData['eventExtensions']['cards'][0]['eventImage'] = [
    'src' => '/fixture-800.jpg',
    'alt' => '',
    'srcset' => '/fixture-400.jpg 400w, /fixture-800.jpg 800w',
    'sizes' => '(max-width: 767px) 100vw, 33vw',
    'width' => 800,
    'height' => 450,
];
$html = EventManagerIntegration\Helper\RenderBlade::blade('event-index', $renderData, dirname(__DIR__) . '/views');
$check(
    str_contains($html, 'fixture-400.jpg 400w') && str_contains($html, 'width="800"'),
    'Blade must preserve responsive image attributes.',
);
$check(!str_contains($html, 'class="modularity-event-index'), 'Server-rendered cards must not mount React.');
$check(
    str_contains($html, 'method="get"') && str_contains($html, 'event_search_' . $post->ID),
    'Search must render a named GET form.',
);
echo wp_json_encode(['passed' => true, 'occurrences' => count($events), 'pages' => $first['pages']]) . "\n";
