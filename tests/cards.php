<?php

declare(strict_types=1);

namespace {
    function wp_timezone(): \DateTimeZone
    {
        return new \DateTimeZone('Europe/Stockholm');
    }

    function get_post_meta(int $id, string $key, bool $single): array
    {
        return ['title' => 'Nora centrum'];
    }

    function get_post_thumbnail_id(int $id): int
    {
        return 0;
    }

    function wp_strip_all_tags(string $text, bool $removeBreaks = false): string
    {
        $text = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $text) ?? '';
        $text = strip_tags($text);
        return $removeBreaks ? preg_replace('/[\r\n\t ]+/', ' ', $text) ?? '' : $text;
    }

    function add_query_arg(string $key, string $value, string $url): string
    {
        return $url . '?' . rawurlencode($key) . '=' . rawurlencode($value);
    }

    function get_permalink(int $id): string
    {
        return 'https://example.test/event/' . $id . '/';
    }

    function esc_html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    function esc_url(string $value): string
    {
        return $value;
    }
}

namespace EventManagerIntegration {
    final class App
    {
        public static function formatEventDate(string $start, string $end): string
        {
            return '5 december 10.00–12.00';
        }
    }
}

namespace MunicipioEventExtensions {
    final class EventImage
    {
        public static function fromAttachment(int $id): ?array
        {
            return null;
        }
    }
}

namespace {
    require dirname(__DIR__) . '/source/EventManagerOccurrence.php';
    require dirname(__DIR__) . '/source/CardView.php';

    use MunicipioEventExtensions\CardView;
    use MunicipioEventExtensions\EventManagerOccurrence;

    function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    $event = (object) [
        'ID' => 17,
        'start_date' => '2026-12-05 10:00:00',
        'end_date' => '2026-12-05 12:00:00',
        'post_title' => 'Jul & marknad',
        'post_content' => "<p>Välkommen &amp; upptäck <strong>Nora</strong>.</p>\n<script>alert('bad')</script>",
    ];
    $normalized = EventManagerOccurrence::normalize($event);

    check(
        $normalized['description'] === 'Välkommen & upptäck Nora.',
        'Description normalization must keep text while removing markup and executable content.',
    );

    $settings = [
        'mod_event_fields' => ['occasion', 'description'],
        'mod_event_descr_limit' => -1,
    ];
    $full = CardView::fromEvent($normalized, $settings);
    check(
        $full['content'] === '5 december 10.00–12.00<br>Välkommen &amp; upptäck Nora.',
        'Selected unlimited description must be rendered safely after event metadata.',
    );
    check($full['eventImage'] === null, 'Description must work without an image.');

    $limited = CardView::fromEvent($normalized, [...$settings, 'mod_event_descr_limit' => 9]);
    check(
        $limited['content'] === '5 december 10.00–12.00<br>Välkommen...',
        'Description limit must count UTF-8 characters and append an ellipsis.',
    );

    $hidden = CardView::fromEvent($normalized, ['mod_event_fields' => ['occasion']]);
    check(!str_contains($hidden['content'], 'Välkommen'), 'Description must remain hidden when its field is not selected.');

    $zero = CardView::fromEvent($normalized, [...$settings, 'mod_event_descr_limit' => 0]);
    check(!str_contains($zero['content'], '...'), 'A zero limit must not render an ellipsis-only description.');

    echo "Event card description checks passed.\n";
}
