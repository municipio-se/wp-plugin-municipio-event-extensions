@if (empty($eventExtensions))
    {!! \EventManagerIntegration\Helper\RenderBlade::blade('event-index', get_defined_vars(), EVENTMANAGERINTEGRATION_PATH . 'source/php/Module/Event/views/') !!}
@else
    @if (empty($hideTitle) && !empty($postTitle))
        @typography(['element' => 'h2', 'variant' => 'h2', 'classList' => ['module-title']])
            {!! $postTitle !!}
        @endtypography
    @endif
    <div id="event-module-{{ $ID }}" class="o-grid {{ $classes }}">
        @forelse ($eventExtensions['cards'] as $eventCard)
            <div class="{{ $gridColumn }}">
                @card(array_diff_key($eventCard, ['eventImage' => true]))
                    @slot('beforeContent')
                        @if ($eventCard['eventImage'])
                            <div class="c-card__image-container">
                                @image([
                                    'src' => $eventCard['eventImage']['src'],
                                    'alt' => $eventCard['eventImage']['alt'],
                                    'srcset' => $eventCard['eventImage']['srcset'],
                                    'imgAttributeList' => [
                                        'sizes' => $eventCard['eventImage']['sizes'],
                                        'width' => $eventCard['eventImage']['width'],
                                        'height' => $eventCard['eventImage']['height'],
                                    ],
                                    'cover' => true,
                                    'classList' => ['c-card__image'],
                                ])
                                @endimage
                            </div>
                        @endif
                    @endslot
                @endcard
            </div>
        @empty
            <p class="o-grid-12">{{ $no_events }}</p>
        @endforelse
    </div>
    @if ($eventExtensions['pages'] > 1)
        <nav aria-label="{{ sprintf(__('Pagination: %s', 'municipio-event-extensions'), wp_strip_all_tags($postTitle)) }}" class="u-margin__top--4">
            @if ($eventExtensions['previous'])
                <a href="{{ esc_url($eventExtensions['previous']) }}">{{ __('Previous', 'event-integration') }}</a>
            @endif
            <span>{{ $eventExtensions['page'] }} / {{ $eventExtensions['pages'] }}</span>
            @if ($eventExtensions['next'])
                <a href="{{ esc_url($eventExtensions['next']) }}">{{ __('Next', 'event-integration') }}</a>
            @endif
        </nav>
    @endif
@endif
