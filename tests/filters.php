<?php

// Run through wp eval-file tests/filters.php MODULE_ID on a local reference site.
use MunicipioEventExtensions\Cards;
use MunicipioEventExtensions\EventManagerSource;
use MunicipioEventExtensions\FilterRequest;
use MunicipioEventExtensions\Filters;
use MunicipioEventExtensions\Search;
use MunicipioEventExtensions\TaxonomyFilter;

$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$post = get_post((int) ($args[0] ?? 0));
$check($post instanceof WP_Post && $post->post_type === 'mod-event', 'Provide a module fixture.');
$GLOBALS['post'] = $post;
$module = new EventManagerIntegration\Module\Event($post);
$module->template();
$data = $module->data;
foreach (['search', 'dates', 'categories', 'tags', 'age_group'] as $filter) {
    $data['settings']['mod_event_filter_' . $filter] = true;
}
$_GET = [];
$_SERVER['QUERY_STRING'] = '';
$source = new EventManagerSource();
$baseline = $source->occurrences($data);
$check(count($baseline) > 0, 'Reference site must contain upcoming events.');
$today = new DateTimeImmutable('today', wp_timezone());
$start = $today->format('Y-m-d');
$end = $today->modify('+7 days')->format('Y-m-d');

// Compare combined filters to the existing REST contract, not a second copy of our SQL.
$cases = [
    ['startDate' => $start, 'endDate' => $end],
    ['minAge' => '4', 'maxAge' => '16'],
    ['startDate' => $start, 'endDate' => $end, 'minAge' => '4', 'maxAge' => '16'],
];
foreach (['categories', 'tags'] as $key) {
    $ids = array_slice(array_column(TaxonomyFilter::options($data, $key), 'id'), 0, 2);
    if ($ids !== []) {
        $cases[] = [$key => array_map('strval', $ids)];
    }
}
foreach ($cases as $case) {
    $_GET = [];
    foreach ($case as $key => $value) {
        $_GET[FilterRequest::name($data, $key)] = $value;
    }
    $state = Filters::state($data);
    $request = new WP_REST_Request('GET', '/wp/v2/event/module');
    $request->set_query_params([
        'module_id' => $post->ID,
        'settings' => wp_json_encode($data['settings']),
        'start_date' => $state['startDate'],
        'end_date' => $state['endDate'],
        'age_min' => $state['minAge'],
        'age_max' => $state['maxAge'],
        'groups' => json_decode($data['groups'], true),
        'categories' => $state['categories'],
        'tags' => $state['tags'],
        'page' => 1,
        'per_page' => -1,
    ]);
    $response = rest_do_request($request);
    $check(in_array($response->get_status(), [200, 404], true), 'Baseline API must accept filter query.');
    $expected = $response->get_status() === 404
        ? []
        : array_map(
            static fn(object $event): string => $event->ID . ':' . $event->start_date . ':' . $event->end_date,
            $response->get_data(),
        );
    $check(
        array_column($source->occurrences($data), 'key') === $expected,
        'Filtered occurrences must match existing API: ' . json_encode($case),
    );
}
foreach ([
    ['startDate' => '2026-02-30'],
    ['endDate' => "2026-01-01\0"],
    ['startDate' => '2026-12-31', 'endDate' => '2026-01-01'],
    ['minAge' => ['bad']],
    ['minAge' => '-1'],
    ['maxAge' => '100'],
    ['minAge' => '20', 'maxAge' => '5'],
] as $case) {
    $_GET = [];
    foreach ($case as $key => $value) {
        $_GET[FilterRequest::name($data, $key)] = $value;
    }
    $check(Filters::state($data)['errors'] !== [], 'Invalid filter must produce feedback.');
    $check($source->occurrences($data) === [], 'Invalid filter must not produce misleading results.');
}
$_GET = [];
$options = TaxonomyFilter::options($data, 'categories');
if (count($options) >= 2) {
    $ids = array_map('intval', array_slice(array_column($options, 'id'), 0, 2));
    $_SERVER['QUERY_STRING'] = 'categories=' . $ids[0] . '&categories=' . $ids[1];
    $check(TaxonomyFilter::selected($data, 'categories') === $ids, 'Repeated legacy taxonomy keys must survive.');
    $_GET[FilterRequest::name($data, 'categories')] = [(string) $ids[0], '999999999', ['bad']];
    $check(
        TaxonomyFilter::selected($data, 'categories') === [$ids[0]],
        'Only available term IDs can reach query layer.',
    );
}
$_SERVER['QUERY_STRING'] = '';
$_GET = [FilterRequest::name($data, 'minAge') => '12', 'event_page_' . $post->ID => '2', 'event_page_other' => '3'];
$other = $data;
$other['ID'] = $post->ID + 1;
$check(Filters::state($other)['minAge'] === (int) $other['age_from'], 'Filters must not affect another module.');
$view = Search::view($data);
$check(
    !in_array('event_page_' . $post->ID, array_column($view['hidden'], 'name'), true),
    'Filter submit resets own pagination.',
);
$check(in_array('event_page_other', array_column($view['hidden'], 'name'), true), 'Other pagination survives.');
$_GET = [];
$render = (new Cards(dirname(__DIR__), $source))->data($data);
$html = EventManagerIntegration\Helper\RenderBlade::blade('event-index', $render, dirname(__DIR__) . '/views');
foreach (['type="date"', 'type="number"', 'type="checkbox"', '<legend>', 'method="get"'] as $markup) {
    $check(str_contains($html, $markup), 'Missing rendered filter control: ' . $markup);
}
$check(!str_contains($html, 'class="modularity-event-index'), 'Filters must not require React.');
echo wp_json_encode(['passed' => true, 'comparisonCases' => count($cases)]) . "\n";
