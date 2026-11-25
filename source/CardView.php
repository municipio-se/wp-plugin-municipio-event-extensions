<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

final class CardView
{
    public static function fromEvent(array $event, array $settings): array
    {
        $fields = is_array($settings['mod_event_fields'] ?? null) ? $settings['mod_event_fields'] : [];
        $content = [];
        if (in_array('occasion', $fields, true) && ($event['occasion'] ?? '') !== '') {
            $content[] = self::contentField('date', (string) $event['occasion']);
        }
        if (in_array('location', $fields, true) && ($event['location'] ?? '') !== '') {
            $content[] = self::contentField('location', (string) $event['location']);
        }
        if (in_array('description', $fields, true) && ($event['description'] ?? '') !== '') {
            $description = self::limitDescription(
                (string) $event['description'],
                self::descriptionLimit($settings['mod_event_descr_limit'] ?? -1),
            );
            if ($description !== '') {
                $content[] = self::contentField('description', $description);
            }
        }
        $image = in_array('image', $fields, true) ? $event['image'] : null;
        $badge = in_array('dateBadge', $fields, true);
        return [
            'heading' => esc_html($event['title']),
            'link' => esc_url($event['url']),
            'context' => ['module.event.index'],
            'content' => $content === []
                ? ''
                : '<div class="c-event-card__details">' . implode('', $content) . '</div>',
            'eventImage' => $image,
            'date' => $badge ? ['timestamp' => $event['badge'], 'format' => 'j M'] : null,
            'dateBadge' => $badge,
            'ratio' => str_replace('-', ':', (string) ($settings['mod_event_image_ratio'] ?? '16-9')),
            'classList' => array_filter(['u-height--100', 'c-card--event', $image ? 'c-card--has-image' : null]),
        ];
    }

    private static function contentField(string $field, string $content): string
    {
        return sprintf('<div class="c-event-card__%s">%s</div>', $field, esc_html($content));
    }

    private static function descriptionLimit(mixed $value): int
    {
        return is_numeric($value) ? max(-1, (int) $value) : -1;
    }

    private static function limitDescription(string $description, int $limit): string
    {
        if ($limit === -1 || mb_strlen($description, 'UTF-8') <= $limit) {
            return $description;
        }
        if ($limit === 0) {
            return '';
        }
        return rtrim(mb_substr($description, 0, $limit, 'UTF-8')) . '...';
    }
}
