{{-- Reuse Municipio's pagination styling and Button/Icon components. Its PHP
     Pagination controller removes module-specific query arguments. --}}
<nav aria-label="{{ sprintf(__('Pagination: %s', 'municipio-event-extensions'), wp_strip_all_tags($postTitle)) }}" class="c-pagination u-display--flex u-justify-content--center u-margin__top--4">
    <ul class="c-pagination__list">
        <li class="c-pagination__item--previous c-pagination__item">
            @button([
                'href' => $eventExtensions['previous'] ?? '',
                'target' => '_self',
                'type' => 'button',
                'size' => 'sm',
                'color' => 'default',
                'icon' => 'chevron_left',
                'ariaLabel' => __('Previous page', 'event-integration'),
                'classList' => ['c-pagination__link'],
                'attributeList' => $eventExtensions['previous'] ? ['data-event-navigation' => ''] : ['disabled' => true],
            ])
            @endbutton
        </li>
        <li class="c-pagination__page-wrapper">
            <ul class="c-pagination__pages">
                @foreach ($eventExtensions['pagination'] as $item)
                    <li class="c-pagination__item {{ $item['edge'] ? 'u-display--none@xs' : '' }}">
                        @if ($item['number'] === null)
                            @icon(['icon' => 'more_horiz', 'size' => 'sm', 'attributeList' => ['aria-hidden' => 'true']])
                            @endicon
                        @else
                            @button([
                                'href' => $item['href'],
                                'target' => '_self',
                                'size' => 'sm',
                                'color' => $item['current'] ? 'primary' : 'default',
                                'text' => (string) $item['number'],
                                'ariaLabel' => __('Go to page: ', 'event-integration') . $item['number'],
                                'classList' => ['c-pagination__link'],
                                'attributeList' => array_merge(['data-event-navigation' => ''], $item['current'] ? ['aria-current' => 'page'] : []),
                            ])
                            @endbutton
                        @endif
                    </li>
                @endforeach
            </ul>
        </li>
        <li class="c-pagination__item--next c-pagination__item">
            @button([
                'href' => $eventExtensions['next'] ?? '',
                'target' => '_self',
                'type' => 'button',
                'size' => 'sm',
                'color' => 'default',
                'icon' => 'chevron_right',
                'ariaLabel' => __('Next page', 'event-integration'),
                'classList' => ['c-pagination__link'],
                'attributeList' => $eventExtensions['next'] ? ['data-event-navigation' => ''] : ['disabled' => true],
            ])
            @endbutton
        </li>
    </ul>
</nav>
