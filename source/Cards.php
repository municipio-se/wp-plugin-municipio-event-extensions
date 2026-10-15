<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

final class Cards
{
    public function __construct(
        private string $directory,
        private EventSource $source = new EventManagerSource(),
    ) {}

    public function register(): void
    {
        add_filter('Modularity/Module/event/TemplatePath', fn(array $paths): array => [
            $this->directory . '/views',
            ...$paths,
        ]);
        add_filter(
            '/Modularity/externalViewPath',
            function (array $paths): array {
                $paths['mod-event'] = $this->directory . '/views';
                return $paths;
            },
            999,
        );
        add_filter('Modularity/Display/mod-event/viewData', [$this, 'data']);
    }

    public function data(array $data): array
    {
        if (($data['template'] ?? '') !== 'index') {
            return $data;
        }
        if (!CardSupport::accepts($data)) {
            $data['eventExtensions'] = null;
            return $data;
        }
        $events = $this->source->occurrences($data);
        $settings = $data['settings'];
        $configuredPerPage = (int) ($settings['mod_event_per_page'] ?? 10);
        $perPage = (bool) ($settings['mod_event_pagination'] ?? false) && $configuredPerPage !== -1
            ? max(1, $configuredPerPage)
            : max(1, count($events));
        $pages = max(1, (int) ceil(count($events) / $perPage));
        $parameter = 'event_page_' . absint($data['ID']);
        $requested = $_GET[$parameter] ?? $_GET['currentPage'] ?? 1;
        $page = min($pages, max(1, is_scalar($requested) ? (int) $requested : 1));
        $cards = [];
        foreach (array_slice($events, ($page - 1) * $perPage, $perPage) as $event) {
            $cards[] = CardView::fromEvent($event, $settings);
        }
        $base = remove_query_arg(['currentPage', $parameter]);
        $data['eventExtensions'] = [
            'search' => Search::view($data),
            'cards' => $cards,
            'page' => $page,
            'pages' => $pages,
            'previous' => $page > 1
                ? add_query_arg($parameter, $page - 1, $base) . '#event-module-' . $data['ID']
                : null,
            'next' => $page < $pages
                ? add_query_arg($parameter, $page + 1, $base) . '#event-module-' . $data['ID']
                : null,
        ];
        $data['gridColumn'] = apply_filters('Modularity/Display/replaceGrid', (string) $data['gridColumn']);
        return $data;
    }
}
