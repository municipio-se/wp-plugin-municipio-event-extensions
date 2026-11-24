<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

final class CardView
{
    public static function fromEvent(array $event, array $settings): array
    {
        $fields = is_array($settings['mod_event_fields'] ?? null) ? $settings['mod_event_fields'] : [];
        $content = [];
        foreach (['occasion', 'location'] as $field) {
            if (in_array($field, $fields, true) && $event[$field] !== '') {
                $content[] = esc_html($event[$field]);
            }
        }
        if (in_array('description', $fields, true) && ($event['description'] ?? '') !== '') {
            $description = self::limitDescription(
                (string) $event['description'],
                self::descriptionLimit($settings['mod_event_descr_limit'] ?? -1),
            );
            if ($description !== '') {
                $content[] = esc_html($description);
            }
        }
        $image = in_array('image', $fields, true) ? $event['image'] : null;
        $badge = in_array('dateBadge', $fields, true);
        return [
            'heading' => esc_html($event['title']),
            'link' => esc_url($event['url']),
            'context' => ['module.event.index'],
            'content' => implode('<br>', $content),
            'eventImage' => $image,
            'date' => $badge ? ['timestamp' => $event['badge'], 'format' => 'j M'] : null,
            'dateBadge' => $badge,
            'ratio' => str_replace('-', ':', (string) ($settings['mod_event_image_ratio'] ?? '16-9')),
            'classList' => array_filter(['u-height--100', 'c-card--event', $image ? 'c-card--has-image' : null]),
        ];
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
