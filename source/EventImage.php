<?php

declare(strict_types=1);

namespace MunicipioEventExtensions;

final class EventImage
{
    public static function fromAttachment(int $id): ?array
    {
        if (!$id) {
            return null;
        }

        $candidates = [];
        $largest = null;
        // Municipio generates these sizes outside WordPress attachment metadata,
        // so wp_get_attachment_image_srcset() cannot discover them. A false height
        // preserves the source crop; card ratios and the mobile track remain CSS-owned.
        foreach ([400, 800, 1280] as $width) {
            $image = wp_get_attachment_image_src($id, [$width, false]);
            if (!$image || empty($image[0]) || empty($image[1]) || empty($image[2])) {
                continue;
            }
            $candidates[$image[0]] = sprintf('%s %dw', $image[0], $image[1]);
            if ($largest === null || $image[1] > $largest[1]) {
                $largest = $image;
            }
        }

        if ($largest === null) {
            return null;
        }

        return [
            'src' => $largest[0],
            'alt' => '',
            'srcset' => count($candidates) > 1 ? implode(', ', $candidates) : '',
            'sizes' => '(max-width: 767px) 100vw, 33vw',
            'width' => $largest[1],
            'height' => $largest[2],
        ];
    }
}
