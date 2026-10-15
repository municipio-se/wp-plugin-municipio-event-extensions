@if (empty($eventExtensions))
    {!! \EventManagerIntegration\Helper\RenderBlade::blade('event-index', array_diff_key(get_defined_vars(), ['__env' => true]), EVENTMANAGERINTEGRATION_PATH . 'source/php/Module/Event/views/') !!}
@else
    @if (empty($hideTitle) && !empty($postTitle))
        @typography(['element' => 'h2', 'variant' => 'h2', 'classList' => ['module-title']])
            {!! $postTitle !!}
        @endtypography
    @endif
    {{-- The theme captures wpFooter before module rendering. Module scripts
         load here; the browser evaluates the same module URL only once. --}}
    {!! wp_get_script_tag(['type' => 'module', 'src' => $eventExtensions['script']]) !!}
    <link rel="stylesheet" href="{!! esc_url($eventExtensions['stylesheet']) !!}">
    <div id="event-module-{{ $ID }}" data-event-module="{{ $ID }}" data-loading="{{ __('Loading events…', 'municipio-event-extensions') }}" data-result-message="{{ $eventExtensions['resultMessage'] }}">
    @foreach ($eventExtensions['search']['errors'] as $error)
        <p role="alert">{{ $error }}</p>
    @endforeach
    @if ($eventExtensions['search']['enabled'])
        <form method="get" action="{!! esc_url($eventExtensions['search']['action']) !!}" class="u-margin__bottom--4">
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
            <a data-event-navigation href="{!! esc_url($eventExtensions['search']['reset']) !!}">{{ __('Reset filters', 'event-integration') }}</a>
        </form>
    @endif
    @if (!empty($mobileHorizontalTrack) && count($eventExtensions['cards']) > 1)
        <nav data-event-scroll-nav hidden aria-label="{{ wp_strip_all_tags($postTitle) }}">
            @foreach (['previous' => ['keyboard_arrow_left', __('Previous', 'event-integration')], 'next' => ['keyboard_arrow_right', __('Next', 'event-integration')]] as $direction => $control)
                @button(['type' => 'button', 'size' => 'sm', 'color' => 'primary', 'icon' => $control[0], 'ariaLabel' => $control[1], 'classList' => ['c-button--pill'], 'attributeList' => ['data-event-scroll' => $direction, 'aria-controls' => 'event-results-' . $ID]])
                @endbutton
            @endforeach
        </nav>
    @endif
    <div data-event-body>
    <div id="event-results-{{ $ID }}" data-event-results @if (!empty($mobileHorizontalTrack)) data-event-track tabindex="0" @else tabindex="-1" @endif role="region" aria-label="{{ $eventExtensions['resultMessage'] }}" class="o-grid {{ $classes }}">
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
    <div data-event-loading hidden aria-hidden="true">
        @loader(['shape' => 'linear', 'color' => 'black', 'size' => 'sm'])
        @endloader
    </div>
    </div>
    @if ($eventExtensions['pages'] > 1)
        @include('event-pagination')
    @endif
    @if (!empty($settings['mod_event_archive']) && !empty($archive_url))
        <div class="o-grid">
            <div class="o-grid-12 u-display--flex u-justify-content--center">
                @button(['href' => $archive_url, 'target' => '_self', 'color' => 'primary', 'text' => __('More events', 'event-integration')])
                @endbutton
            </div>
        </div>
    @endif
    </div>
@endif
