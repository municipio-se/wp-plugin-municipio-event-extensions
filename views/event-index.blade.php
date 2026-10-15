@if (empty($eventExtensions))
    {!! \EventManagerIntegration\Helper\RenderBlade::blade('event-index', get_defined_vars(), EVENTMANAGERINTEGRATION_PATH . 'source/php/Module/Event/views/') !!}
@else
    @if (empty($hideTitle) && !empty($postTitle))
        @typography(['element' => 'h2', 'variant' => 'h2', 'classList' => ['module-title']])
            {!! $postTitle !!}
        @endtypography
    @endif
    <div id="event-module-{{ $ID }}">
    @foreach ($eventExtensions['search']['errors'] as $error)
        <p role="alert">{{ $error }}</p>
    @endforeach
    @if ($eventExtensions['search']['enabled'])
        <form method="get" action="{{ esc_url($eventExtensions['search']['action']) }}" class="u-margin__bottom--4">
            @foreach ($eventExtensions['search']['hidden'] as $control)
                <input type="hidden" name="{{ $control['name'] }}" value="{{ $control['value'] }}">
            @endforeach
            @if ($eventExtensions['search']['searchVisible'])
                @field(['id' => 'event-search-' . $ID, 'type' => 'search', 'label' => __('Search', 'event-integration'), 'name' => $eventExtensions['search']['name'], 'value' => $eventExtensions['search']['value'], 'attributeList' => ['maxlength' => 200], 'classList' => ['u-margin__bottom--2']])
                @endfield
            @endif
            @foreach (['startDate' => __('From', 'event-integration') . ' ' . __('date', 'event-integration'), 'endDate' => __('To', 'event-integration') . ' ' . __('date', 'event-integration'), 'minAge' => __('From age', 'event-integration'), 'maxAge' => __('To age', 'event-integration')] as $key => $label)
                @if ($eventExtensions['search']['controls'][$key]['visible'])
                    <div class="u-margin__bottom--2">
                        @field([
                            'id' => $eventExtensions['search']['controls'][$key]['name'],
                            'label' => $label,
                            'type' => in_array($key, ['startDate', 'endDate']) ? 'date' : 'number',
                            'name' => $eventExtensions['search']['controls'][$key]['name'],
                            'value' => $eventExtensions['search']['controls'][$key]['value'] ?: '',
                            'attributeList' => in_array($key, ['minAge', 'maxAge']) ? ['min' => 0, 'max' => 99, 'step' => 1] : [],
                        ])
                        @endfield
                    </div>
                @endif
            @endforeach
            @foreach (['categories' => __('Categories', 'event-integration'), 'tags' => __('Tags', 'event-integration')] as $key => $label)
                @if ($eventExtensions['search']['controls'][$key]['visible'] && $eventExtensions['search']['controls'][$key]['options'])
                    <fieldset class="u-margin__bottom--2">
                        <legend>{{ $label }}</legend>
                        @foreach ($eventExtensions['search']['controls'][$key]['options'] as $option)
                            <label>
                                <input type="checkbox" name="{{ $eventExtensions['search']['controls'][$key]['name'] }}[]" value="{{ $option['id'] }}" @if (in_array((int) $option['id'], $eventExtensions['search']['controls'][$key]['value'], true)) checked @endif>
                                {{ $option['title'] }}
                            </label>
                        @endforeach
                    </fieldset>
                @endif
            @endforeach
            @button(['type' => 'submit', 'text' => __('Search', 'event-integration'), 'color' => 'primary'])
            @endbutton
            <a href="{{ esc_url($eventExtensions['search']['reset']) }}">{{ __('Reset filters', 'event-integration') }}</a>
        </form>
    @endif
    <div class="o-grid {{ $classes }}">
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
    </div>
@endif
