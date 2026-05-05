@php
use App\Helpers\Background;
use App\Helpers\Image;
use App\Helpers\Padding;
use App\Helpers\TextFormatter;
use App\Helpers\Typography;
use App\Helpers\Video;
use App\Helpers\TailwindColors;

$body_text_tone = $component['body_text_tone'] ?? TailwindColors::DEFAULT_BODY_TEXT_TONE;
$remove_vertical_padding = !empty($component['remove_vertical_padding']);
$padding = $remove_vertical_padding ? '' : Padding::getClasses($component ?? []);
$backgroundHandled = !empty($component['_background_handled']);
$backgroundData = $backgroundHandled ? Background::getEmptyStub() : Background::process($component ?? []);
$backgroundClasses = $backgroundData['classes'] ?? '';
$backgroundStyles = $backgroundData['styles'] ?? '';
$gridClasses = '';
if (isset($component['_grid_classes']) && is_string($component['_grid_classes'])) {
    $gridClasses = $component['_grid_classes'];
}

$header_text = $component['header_text'] ?? '';
$header_text_color = $component['header_text_color'] ?? 'text-white';
$header_text_size = $component['header_text_size'] ?? 'text-6xl';
$header_text_weight = $component['header_text_weight'] ?? 'font-medium';
$header_alignment = $component['header_alignment'] ?? 'top';
$header_text_alignment = $component['header_text_alignment'] ?? 'left';
$subheading_text = $component['subheading_text'] ?? '';
$subheading_text_color = $component['subheading_text_color'] ?? 'text-white';
$subheading_text_size = $component['subheading_text_size'] ?? 'text-lg';
$subheading_text_weight = $component['subheading_text_weight'] ?? 'font-medium';
$button_text = $component['button_text'] ?? '';
$button_link = is_array($component['button_link'] ?? null) ? $component['button_link'] : [];
$button_variant = match($component['button_variant'] ?? 'primary') {
    'outline', 'primary', 'secondary' => $component['button_variant'] ?? 'primary',
    default => 'primary',
};
$button_size = match($component['button_size'] ?? 'md') {
    'lg', 'md', 'sm' => $component['button_size'] ?? 'md',
    default => 'md',
};
$button_show_arrow = $component['button_show_arrow'] ?? true;
$body_text = $component['body_text'] ?? '';
$body_text_color = $component['body_text_color'] ?? 'text-white';
$scroll_cards = is_array($component['scroll_cards'] ?? null) ? $component['scroll_cards'] : [];
$scroll_speed = $component['scroll_speed'] ?? 'medium';
$disable_scroll = !empty($component['disable_scroll']);
$wider_item_gaps = !empty($component['wider_item_gaps']);
$media_object_fit = match ($component['media_object_fit'] ?? 'cover') {
    'contain' => 'contain',
    default => 'cover',
};
$media_object_fit_class = $media_object_fit === 'contain' ? 'horizontal-scroller-component--media-contain' : '';

$header_alignment_class = match($header_alignment) {
    'middle' => 'lg:justify-center',
    'bottom' => 'lg:justify-end',
    default => 'lg:justify-start'
};

$header_text_align_class = match($header_text_alignment) {
    'center' => 'text-center',
    'right' => 'text-right',
    default => 'text-left'
};

$scroll_speed_class = $disable_scroll
    ? ''
    : match($scroll_speed) {
        'slow' => 'horizontal-scroll-slow',
        'fast' => 'horizontal-scroll-fast',
        default => 'horizontal-scroll-medium'
    };

// Keep heading sizes constrained to allowed Tailwind utilities.
$header_size_class = match ($header_text_size) {
    'text-2xl', 'text-3xl', 'text-4xl', 'text-5xl', 'text-6xl', 'text-7xl', 'text-8xl', 'text-9xl', 'text-xxl', 'text-xxxl', 'text-xxxxl' => $header_text_size,
    default => 'text-6xl'
};
$header_text_color_class = match ($header_text_color) {
    'text-black', 'text-brand-500', 'text-deep-moss', 'text-text-muted', 'text-white', 'text-white/80' => $header_text_color,
    default => 'text-white'
};
$header_text_weight_class = match($header_text_weight) {
    'font-light', 'font-normal', 'font-medium', 'font-semibold', 'font-bold' => $header_text_weight,
    default => 'font-medium',
};

$subheading_size_class = Typography::validateBodySize($subheading_text_size ?? null, 'text-lg');
$subheading_text_color_class = match ($subheading_text_color) {
    'text-black', 'text-brand-500', 'text-deep-moss', 'text-text-muted', 'text-white', 'text-white/80' => $subheading_text_color,
    default => 'text-white'
};
$subheading_text_weight_class = match($subheading_text_weight) {
    'font-light', 'font-normal', 'font-medium', 'font-semibold', 'font-bold' => $subheading_text_weight,
    default => 'font-medium',
};
$header_padding_class = Padding::getHeaderSubheaderPaddingClasses(
    $component['header_padding_top'] ?? 'none',
    $component['header_padding_bottom'] ?? 'none'
);
$subheader_padding_class = Padding::getHeaderSubheaderPaddingClasses(
    $component['subheader_padding_top'] ?? 'none',
    $component['subheader_padding_bottom'] ?? 'none'
);
$body_padding_class = Padding::getHeaderSubheaderPaddingClasses(
    $component['body_padding_top'] ?? 'none',
    $component['body_padding_bottom'] ?? 'none'
);
$body_classes = Typography::classes(
    'body',
    $component['body_text_size'] ?? 'text-lg',
    $component['body_text_weight'] ?? 'font-medium'
);
$body_text_color_class = match ($body_text_color) {
    'text-black', 'text-brand-500', 'text-deep-moss', 'text-text-muted', 'text-white', 'text-white/80' => $body_text_color,
    default => 'text-white'
};

$intro_flush_to_content = !empty($component['intro_flush_to_content']);
$flat_logo_strip = !empty($component['scroll_strip_flat_logos']);
$edge_to_edge_strip = !empty($component['scroll_strip_edge_to_edge']);
/** Partner logo strips must bleed to the viewport; CMS rows may omit the edge toggle. */
$fullBleedScrollerStrip = $flat_logo_strip || $edge_to_edge_strip;
if ($fullBleedScrollerStrip && $gridClasses !== '') {
    $gridClasses = trim(preg_replace('/\s+/', ' ', preg_replace('/\b(?:sm:|md:|lg:|xl:)?px-[^\s]+\s*/', '', $gridClasses)));
}

$item_kicker_classes = Typography::classes(
    'body',
    $component['item_kicker_size'] ?? 'text-xs',
    $component['item_kicker_weight'] ?? 'font-semibold'
);
$item_heading_classes = Typography::classes(
    'heading',
    $component['item_heading_size'] ?? 'text-xl',
    $component['item_heading_weight'] ?? 'font-medium'
);
$item_body_classes = Typography::classes(
    'body',
    $component['item_body_size'] ?? 'text-base',
    $component['item_body_weight'] ?? 'font-normal'
);
$item_overlay_channel_classes = Typography::classes(
    'body',
    $component['item_overlay_channel_size'] ?? 'text-sm',
    $component['item_overlay_channel_weight'] ?? 'font-medium'
);
$item_overlay_title_classes = Typography::classes(
    'body',
    $component['item_overlay_title_size'] ?? 'text-xl',
    $component['item_overlay_title_weight'] ?? 'font-medium'
);

$item_overlay_channel_color = $component['item_overlay_channel_color'] ?? 'text-white/80';
$item_overlay_title_color = $component['item_overlay_title_color'] ?? 'text-white';
$item_overlay_channel_color_class = match ($item_overlay_channel_color) {
    'text-white', 'text-white/80', 'text-black', 'text-brand-500', 'text-deep-moss', 'text-text-muted' => $item_overlay_channel_color,
    default => 'text-white/80',
};
$item_overlay_title_color_class = match ($item_overlay_title_color) {
    'text-white', 'text-black', 'text-brand-500', 'text-deep-moss', 'text-text-muted', 'text-white/80' => $item_overlay_title_color,
    default => 'text-white',
};

$item_kicker_padding_class = Padding::getHeaderSubheaderPaddingClasses($component['item_kicker_padding_top'] ?? 'none', $component['item_kicker_padding_bottom'] ?? 'none');
$item_heading_padding_class = Padding::getHeaderSubheaderPaddingClasses($component['item_heading_padding_top'] ?? 'none', $component['item_heading_padding_bottom'] ?? 'none');
$item_body_padding_class = Padding::getHeaderSubheaderPaddingClasses($component['item_body_padding_top'] ?? 'none', $component['item_body_padding_bottom'] ?? 'none');
$item_overlay_channel_padding_class = Padding::getHeaderSubheaderPaddingClasses($component['item_overlay_channel_padding_top'] ?? 'none', $component['item_overlay_channel_padding_bottom'] ?? 'none');
$item_overlay_title_padding_class = Padding::getHeaderSubheaderPaddingClasses($component['item_overlay_title_padding_top'] ?? 'none', $component['item_overlay_title_padding_bottom'] ?? 'none');

$hasHeaderText = TextFormatter::hasVisibleContent((string) $header_text);
$hasSubheadingText = TextFormatter::hasVisibleContent((string) $subheading_text);
$hasBodyText = TextFormatter::hasVisibleContent((string) $body_text);
$hasButton = trim((string) $button_text) !== '' && trim((string) ($button_link['url'] ?? '')) !== '';
$hasHeaderBlock = $hasHeaderText || $hasSubheadingText || $hasBodyText;

$normalized_items = [];
foreach ($scroll_cards as $item) {
    if (!is_array($item)) {
        continue;
    }

    $image = $item['image'] ?? null;
    // Normalize image when stored as attachment ID (e.g. from CLI/import)
    if (is_numeric($image) && (int) $image > 0 && function_exists('acf_get_attachment')) {
        $image = acf_get_attachment((int) $image);
    } elseif (is_numeric($image) && (int) $image > 0) {
        $att_id = (int) $image;
        $src = wp_get_attachment_image_src($att_id, 'full');
        $image = $src ? [
            'id' => $att_id,
            'url' => $src[0],
            'width' => $src[1] ?? null,
            'height' => $src[2] ?? null,
            'alt' => get_post_meta($att_id, '_wp_attachment_image_alt', true),
        ] : null;
    }
    $video = $item['item_video'] ?? null;
    $show_video_controls = !empty($item['item_video_show_controls']);
    $video_embed_url = Video::youtubeEmbedUrl(
        is_string($item['item_video_youtube_url'] ?? null)
            ? (string) $item['item_video_youtube_url']
            : null,
        ['controls' => $show_video_controls]
    );
    $poster = $item['item_video_poster'] ?? null;
    $kicker = trim((string)($item['item_kicker'] ?? ''));
    $heading = trim((string)($item['item_heading'] ?? ''));
    $body = trim((string)($item['item_body'] ?? ''));
    $overlay_channel = trim((string)($item['item_channel_name'] ?? ''));
    $overlay_title = trim((string)($item['item_video_title'] ?? ''));
    $show_youtube_icon = isset($item['item_show_youtube_icon']) ? (bool)$item['item_show_youtube_icon'] : false;
    $overlay_icon = $item['item_overlay_icon'] ?? null;
    $alt_text = trim((string)($item['image_alt_text'] ?? ''));

    $has_image = is_array($image) && !empty($image['url']);
    $has_video = (is_array($video) && !empty($video['url'])) || !empty($video_embed_url);

    $type = match ((string)($item['item_type'] ?? '')) {
        'image', 'video', 'text', 'image_text' => (string)($item['item_type'] ?? ''),
        default => '',
    };

    if ($type === '') {
        if ($has_video) {
            $type = 'video';
        } elseif ($has_image && ($heading !== '' || $body !== '')) {
            $type = 'image_text';
        } elseif ($heading !== '' || $body !== '' || $kicker !== '') {
            $type = 'text';
        } else {
            $type = 'image';
        }
    }

    $item_size = match ((string)($item['item_size'] ?? 'medium')) {
        'small', 'medium', 'large', 'xlarge' => (string)($item['item_size'] ?? 'medium'),
        default => 'medium',
    };

    $item_offset = match ((string)($item['item_vertical_offset'] ?? 'center')) {
        'high-up', 'up', 'center', 'down', 'low-down' => (string)($item['item_vertical_offset'] ?? 'center'),
        default => 'center',
    };

    $item_ratio = match ((string)($item['item_aspect_ratio'] ?? 'landscape')) {
        'portrait', 'square', 'landscape', 'tall' => (string)($item['item_aspect_ratio'] ?? 'landscape'),
        default => 'landscape',
    };

    $has_renderable_content = match ($type) {
        'image' => $has_image,
        'video' => $has_video,
        'image_text' => $has_image || $heading !== '' || $body !== '',
        'text' => $kicker !== '' || $heading !== '' || $body !== '',
        default => false,
    };

    if (!$has_renderable_content) {
        continue;
    }

    // Backfill overlay metadata from existing content so overlays render on legacy cards too.
    if (! $flat_logo_strip) {
        if ($overlay_channel === '' && $kicker !== '') {
            $overlay_channel = $kicker;
        }
        if ($overlay_title === '' && $heading !== '') {
            $overlay_title = $heading;
        }
        if ($overlay_title === '' && $alt_text !== '') {
            $overlay_title = wp_strip_all_tags($alt_text);
        }
    } else {
        $overlay_channel = '';
        $overlay_title = '';
    }
    $normalized_items[] = [
        'type' => $type,
        'size' => $item_size,
        'offset' => $item_offset,
        'ratio' => $item_ratio,
        'kicker' => $kicker,
        'heading' => $heading,
        'body' => $body,
        'image' => $image,
        'video' => $video,
        'video_embed_url' => $video_embed_url,
        'show_video_controls' => $show_video_controls,
        'poster' => $poster,
        'alt_text' => $alt_text,
        'overlay_channel' => $overlay_channel,
        'overlay_title' => $overlay_title,
        'show_youtube_icon' => $show_youtube_icon,
        'overlay_icon' => $overlay_icon,
    ];
}
$has_any_content = $hasHeaderBlock || $hasButton || !empty($normalized_items);
$live_region_id = 'horizontal-scroller-description-' . uniqid();
@endphp

@if($has_any_content)
<section
    class="horizontal-scroller-component {{ $gridClasses }} relative {{ $backgroundClasses }} {{ $padding }} {{ $media_object_fit_class }} @if($disable_scroll) horizontal-scroller-component--disable-scroll @endif @if($wider_item_gaps) horizontal-scroller-component--wide-gaps @endif @if($flat_logo_strip) horizontal-scroller-component--flat-logos @endif"
    @if($backgroundStyles) style="{{ $backgroundStyles }}" @endif
    data-component-root
    data-hs-disable-scroll="{{ $disable_scroll ? '1' : '0' }}"
    data-hs-scroll-speed="{{ esc_attr((string) $scroll_speed) }}"
    x-data="horizontalScroller"
    role="region"
    aria-label="{{ $disable_scroll ? __('Floating content showcase', 'culvers') : __('Horizontal scrolling showcase', 'culvers') }}"
>
    @include('partials.component-background-media', ['backgroundData' => $backgroundData])

    <div class="relative z-20">
        {{-- Screen reader announcement region --}}
        <div class="sr-only" aria-live="polite" aria-atomic="true" id="{{ esc_attr($live_region_id) }}">
            {{ $disable_scroll ? __('Floating items in a centered row.', 'culvers') : __('Horizontal scrolling showcase of floating items. Drag to scroll.', 'culvers') }}
        </div>

        {{-- Top: Header --}}
        @if($hasHeaderBlock || $hasButton)
            <div class="horizontal-scroller-header mx-auto flex max-w-[1272px] flex-col justify-start px-5 lg:min-h-[240px] lg:px-16 {{ $header_alignment_class }} {{ $header_text_align_class }}">
                @if($hasHeaderBlock)
                    <div class="{{ $intro_flush_to_content ? 'mb-0' : 'mb-10 md:mb-14' }} {{ $header_text_align_class }}">
                        @if($hasHeaderText)
                            <h2 class="font-heading {{ $header_text_color_class }} {{ $header_size_class }} {{ $header_text_weight_class }} {{ $header_padding_class }}">
                                {!! TextFormatter::inline((string) $header_text) !!}
                            </h2>
                        @endif

                        @if($hasSubheadingText)
                            <h3 class="font-sans {{ $subheading_text_color_class }} {{ $subheading_size_class }} {{ $subheading_text_weight_class }} {{ $subheader_padding_class }}">
                                {!! TextFormatter::inline((string) $subheading_text) !!}
                            </h3>
                        @endif

                        @if($hasBodyText)
                            <div class="{{ $body_classes }} {{ $body_text_tone }} prose prose-neutral max-w-none {{ $body_padding_class }}">
                                {!! TextFormatter::rich((string) $body_text) !!}
                            </div>
                        @endif
                    </div>
                @endif

                @if($hasButton)
                    @php
                        $btn_classes = match ($button_variant) {
                            'outline' => 'btn btn-outline',
                            'secondary' => 'btn btn-outline border-deep-moss text-deep-moss hover:bg-deep-moss hover:text-light-cream',
                            default => 'btn btn-primary',
                        };
                        $btn_classes .= match ($button_size) {
                            'sm' => ' px-5 py-2 text-micro',
                            'lg' => ' px-12 py-4 text-sm',
                            default => '',
                        };
                        $btn_classes .= ' inline-flex items-center gap-2';
                        $btn_target = trim((string) ($button_link['target'] ?? ''));
                    @endphp
                    <div class="horizontal-scroller-header-cta mt-6">
                        <a
                            href="{{ esc_url((string) ($button_link['url'] ?? '')) }}"
                            class="{{ esc_attr(trim($btn_classes)) }}"
                            @if($btn_target !== '')
                                target="{{ esc_attr($btn_target) }}"
                                @if($btn_target === '_blank')
                                    rel="noopener noreferrer"
                                @endif
                            @endif>
                            {{ esc_html($button_text) }}
                            @if($button_show_arrow)
                                <svg class="size-4 shrink-0" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M8 0L6.59 1.41L12.17 7H0V9H12.17L6.59 14.59L8 16L16 8L8 0Z" fill="currentColor" />
                                </svg>
                            @endif
                        </a>
                    </div>
                @endif
            </div>
        @endif

        @if(! empty($normalized_items))
            <div class="@if($fullBleedScrollerStrip) horizontal-scroller-strip-breakout @endif">
            <div class="horizontal-scroller-wrapper {{ $disable_scroll ? 'overflow-visible' : 'overflow-hidden' }} {{ $scroll_speed_class }} {{ $header_text_color_class }} @if($remove_vertical_padding) horizontal-scroller-wrapper--no-vertical-padding @endif">
                <div class="horizontal-scroller-container" aria-label="{{ $disable_scroll ? __('Floating content', 'culvers') : __('Horizontal scrolling floating content', 'culvers') }}">
                    @foreach($disable_scroll ? [0] : [0, 1] as $set_index)
                        @php $is_clone_set = $set_index > 0; @endphp
                        @foreach($normalized_items as $item)
                            <article class="horizontal-scroller-item horizontal-scroller-item--{{ $item['type'] }} horizontal-scroller-item--{{ $item['size'] }} horizontal-scroller-item--offset-{{ $item['offset'] }} horizontal-scroller-item--ratio-{{ $item['ratio'] }}" data-set-index="{{ $set_index }}" @if($is_clone_set) aria-hidden="true" inert @endif>
                                @if(($item['kicker'] ?? '') !== '')
                                    <p class="horizontal-scroller-item__kicker {{ $item_kicker_classes }} {{ $item_kicker_padding_class }}">
                                        {!! TextFormatter::plain((string) $item['kicker']) !!}
                                    </p>
                                @endif

                                @if(
                                    $item['type'] === 'video'
                                    && (
                                        (is_array($item['video']) && !empty($item['video']['url']))
                                        || !empty($item['video_embed_url'])
                                    )
                                )
                                    @php
                                        $poster_url = is_array($item['poster']) && !empty($item['poster']['url']) ? esc_url($item['poster']['url']) : '';
                                        $has_custom_overlay_icon = ! $flat_logo_strip && is_array($item['overlay_icon']) && !empty($item['overlay_icon']['url']);
                                        $has_overlay_copy = ! $flat_logo_strip && ($item['overlay_channel'] !== '' || $item['overlay_title'] !== '');
                                        $has_overlay = ! $flat_logo_strip && ($has_overlay_copy || $has_custom_overlay_icon || $item['show_youtube_icon']);
                                    @endphp
                                    <div class="horizontal-scroller-item__media">
                                        @if(is_array($item['video']) && !empty($item['video']['url']))
                                            @if($item['show_video_controls'])
                                                <div class="relative h-full w-full min-h-0">
                                                    <video class="horizontal-scroller-item__video w-full h-full"
                                                           src="{{ esc_url($item['video']['url']) }}"
                                                           @if($poster_url) poster="{{ $poster_url }}" @endif
                                                           data-video-manual-start="1"
                                                           data-gsap-autoplay="off"
                                                           data-video-needs-controls="1"
                                                           playsinline
                                                           preload="auto"
                                                           aria-label="{{ esc_attr__('Scroller video', 'culvers') }}"></video>
                                                </div>
                                            @else
                                                <video class="horizontal-scroller-item__video"
                                                       src="{{ esc_url($item['video']['url']) }}"
                                                       @if($poster_url) poster="{{ $poster_url }}" @endif
                                                       autoplay
                                                       muted
                                                       loop
                                                       data-video-ambient="1"
                                                       playsinline
                                                       preload="auto"
                                                       disablepictureinpicture
                                                       disableremoteplayback
                                                       controlslist="nodownload noplaybackrate noremoteplayback"
                                                       aria-hidden="true"></video>
                                            @endif
                                        @elseif(!empty($item['video_embed_url']))
                                            <iframe class="horizontal-scroller-item__video border-0 {{ $item['show_video_controls'] ? '' : 'pointer-events-none' }}"
                                                    src="{{ esc_url((string) $item['video_embed_url']) }}"
                                                    title="{{ esc_attr__('Scroller video', 'culvers') }}"
                                                    loading="lazy"
                                                    allow="autoplay; encrypted-media; picture-in-picture"
                                                    allowfullscreen
                                                    @if(!$item['show_video_controls']) aria-hidden="true" tabindex="-1" @endif></iframe>
                                        @endif
                                        @if($has_overlay)
                                            <div class="horizontal-scroller-item__overlay" aria-hidden="true">
                                                <div class="horizontal-scroller-item__overlay-copy">
                                                    <div class="horizontal-scroller-item__overlay-title-row">
                                                        @if($has_custom_overlay_icon)
                                                            <span class="horizontal-scroller-item__overlay-icon" aria-hidden="true">
                                                                <img src="{{ esc_url($item['overlay_icon']['url']) }}"
                                                                     alt=""
                                                                     loading="lazy"
                                                                     decoding="async">
                                                            </span>
                                                        @elseif($item['show_youtube_icon'])
                                                            <span class="horizontal-scroller-item__overlay-icon !text-white" aria-hidden="true">
                                                                <svg viewBox="0 0 24 24" focusable="false" fill="currentColor">
                                                                    <path fill="currentColor" d="M23.5 7.3a3 3 0 0 0-2.1-2.1C19.5 4.7 12 4.7 12 4.7s-7.5 0-9.4.5A3 3 0 0 0 .5 7.3 31.1 31.1 0 0 0 0 12a31.1 31.1 0 0 0 .5 4.7 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31.1 31.1 0 0 0 24 12a31.1 31.1 0 0 0-.5-4.7ZM9.6 15.2V8.8l6.2 3.2-6.2 3.2Z"/>
                                                                </svg>
                                                            </span>
                                                        @endif
                                                        @if($item['overlay_title'] !== '')
                                                            <span class="horizontal-scroller-item__overlay-title {{ $item_overlay_title_classes }} {{ $item_overlay_title_color_class }} {{ $item_overlay_title_padding_class }}">
                                                                {!! TextFormatter::plain((string) $item['overlay_title']) !!}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    @if($item['overlay_channel'] !== '')
                                                        <span class="horizontal-scroller-item__overlay-channel {{ $item_overlay_channel_classes }} {{ $item_overlay_channel_color_class }} {{ $item_overlay_channel_padding_class }}">
                                                            {!! TextFormatter::plain((string) $item['overlay_channel']) !!}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @elseif(($item['type'] === 'image' || $item['type'] === 'image_text') && is_array($item['image']) && !empty($item['image']['url']))
                                    @php
                                        $render_image = $item['image'];
                                        $has_custom_overlay_icon = ! $flat_logo_strip && is_array($item['overlay_icon']) && !empty($item['overlay_icon']['url']);
                                        $has_overlay_copy = ! $flat_logo_strip && ($item['overlay_channel'] !== '' || $item['overlay_title'] !== '');
                                        $has_overlay = ! $flat_logo_strip && ($has_overlay_copy || $has_custom_overlay_icon || $item['show_youtube_icon']);
                                        if ($item['alt_text'] !== '') {
                                            $render_image = array_merge($render_image, ['alt' => $item['alt_text']]);
                                        }
                                    @endphp
                                    <div class="horizontal-scroller-item__media">
                                        {!! Image::render($render_image, ['size' => 'large', 'class' => 'horizontal-scroller-item__image']) !!}
                                        @if($has_overlay)
                                            <div class="horizontal-scroller-item__overlay" aria-hidden="true">
                                                <div class="horizontal-scroller-item__overlay-copy">
                                                    <div class="horizontal-scroller-item__overlay-title-row">
                                                        @if($has_custom_overlay_icon)
                                                            <span class="horizontal-scroller-item__overlay-icon" aria-hidden="true">
                                                                <img src="{{ esc_url($item['overlay_icon']['url']) }}"
                                                                     alt=""
                                                                     loading="lazy"
                                                                     decoding="async">
                                                            </span>
                                                        @elseif($item['show_youtube_icon'])
                                                            <span class="horizontal-scroller-item__overlay-icon !text-white" aria-hidden="true">
                                                                <svg viewBox="0 0 24 24" focusable="false" fill="currentColor">
                                                                    <path fill="currentColor" d="M23.5 7.3a3 3 0 0 0-2.1-2.1C19.5 4.7 12 4.7 12 4.7s-7.5 0-9.4.5A3 3 0 0 0 .5 7.3 31.1 31.1 0 0 0 0 12a31.1 31.1 0 0 0 .5 4.7 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31.1 31.1 0 0 0 24 12a31.1 31.1 0 0 0-.5-4.7ZM9.6 15.2V8.8l6.2 3.2-6.2 3.2Z"/>
                                                                </svg>
                                                            </span>
                                                        @endif
                                                        @if($item['overlay_title'] !== '')
                                                            <span class="horizontal-scroller-item__overlay-title {{ $item_overlay_title_classes }} {{ $item_overlay_title_color_class }} {{ $item_overlay_title_padding_class }}">
                                                                {!! TextFormatter::plain((string) $item['overlay_title']) !!}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    @if($item['overlay_channel'] !== '')
                                                        <span class="horizontal-scroller-item__overlay-channel {{ $item_overlay_channel_classes }} {{ $item_overlay_channel_color_class }} {{ $item_overlay_channel_padding_class }}">
                                                            {!! TextFormatter::plain((string) $item['overlay_channel']) !!}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if(($item['type'] === 'text' || $item['type'] === 'image_text') && ($item['heading'] !== '' || $item['body'] !== ''))
                                    <div class="horizontal-scroller-item__copy">
                                        @if($item['heading'] !== '')
                                            <h3 class="horizontal-scroller-item__heading {{ $item_heading_classes }} {{ $item_heading_padding_class }}">
                                                {!! TextFormatter::plain((string) $item['heading']) !!}
                                            </h3>
                                        @endif
                                        @if($item['body'] !== '')
                                            <div class="horizontal-scroller-item__body prose prose-neutral max-w-none {{ $body_text_tone }} {{ $item_body_classes }} {{ $item_body_padding_class }}">
                                                {!! TextFormatter::plain((string) $item['body'], true) !!}
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    @endforeach
                </div>
            </div>
            </div>
        @endif
    </div>
</section>
@endif
