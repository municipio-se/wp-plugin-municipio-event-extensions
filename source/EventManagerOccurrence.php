<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

use DateTimeImmutable;

final class EventManagerOccurrence
{
    public static function normalize(object $event): array
    {
        $start = new DateTimeImmutable($event->start_date, wp_timezone());
        $end = new DateTimeImmutable($event->end_date, wp_timezone());
        $now = new DateTimeImmutable('today', wp_timezone());
        $badge = match (true) {
            $now < $start->setTime(0, 0) => $start,
            $now > $end => $end,
            default => $now,
        };
        $location = get_post_meta((int) $event->ID, 'location', true);
        $imageId = (int) get_post_thumbnail_id((int) $event->ID);
        $image = $imageId ? wp_get_attachment_image_src($imageId, 'large') : false;
        $srcset = $imageId ? wp_get_attachment_image_srcset($imageId, 'large') : false;
        return [
            'key' => $event->ID . ':' . $event->start_date . ':' . $event->end_date,
            'title' => wp_strip_all_tags($event->post_title),
            'url' => add_query_arg('date', $start->format('YmdHis'), get_permalink((int) $event->ID)),
            'occasion' => \EventManagerIntegration\App::formatEventDate($event->start_date, $event->end_date),
            'badge' => $badge->getTimestamp(),
            'location' => is_array($location) ? (string) ($location['title'] ?? '') : '',
            'image' => $image
                ? [
                    'src' => $image[0],
                    'alt' => '',
                    'srcset' => is_string($srcset) ? $srcset : '',
                    'sizes' => '(max-width: 767px) 100vw, 33vw',
                    'width' => $image[1],
                    'height' => $image[2],
                ] : null,
        ];
    }
}
