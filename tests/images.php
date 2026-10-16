<?php

declare(strict_types=1);

require dirname(__DIR__) . '/source/EventImage.php';

use MunicipioEventExtensions\EventImage;

function wp_get_attachment_image_src(int $id, array $size): array|false
{
    if ($size[1] !== false) {
        throw new RuntimeException('Image generation must preserve the original crop.');
    }
    return match ($id) {
        1 => ['image-' . $size[0] . '.webp', $size[0], (int) ($size[0] / 2), true],
        2 => ['small.jpg', 200, 100, false],
        3 => $size[0] === 800 ? ['medium.webp', 800, 400, true] : false,
        default => false,
    };
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$image = EventImage::fromAttachment(1);
check(
    $image['srcset'] === 'image-400.webp 400w, image-800.webp 800w, image-1280.webp 1280w',
    'All generated sizes must be selectable.',
);
check(
    $image['src'] === 'image-1280.webp' && $image['width'] === 1280 && $image['height'] === 640,
    'Fallback dimensions must match its source.',
);
$small = EventImage::fromAttachment(2);
check(
    $small['srcset'] === '' && $small['width'] === 200,
    'Repeated fallback URLs must not advertise nonexistent sizes.',
);
check(EventImage::fromAttachment(3)['src'] === 'medium.webp', 'A failed candidate must not discard valid images.');
check(
    EventImage::fromAttachment(0) === null && EventImage::fromAttachment(4) === null,
    'Missing images must remain absent.',
);
echo "Image candidate checks passed.\n";
