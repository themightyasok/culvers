@php
use App\Helpers\Background;
use App\Helpers\Component;
use App\Helpers\Grid;
use App\Helpers\Image;
use App\Helpers\LayoutShell;
use App\Helpers\Padding;
use App\Helpers\TextFormatter;
use App\Helpers\Typography;
use App\Helpers\Video;

/**
 * Horizontal scroller — GSAP-driven seamless infinite strip with drag,
 * configurable header alignment, and an alternate "disable scroll" centred
 * row. Items can be image, video (file or YouTube embed), text, or image+text.
 *
 * Spacing: outer vertical rhythm comes from the parent flexible-components
 * grid (`gap-y-32`, see App\Helpers\Grid). The strip's own internal vertical
 * "safe area" is owned by `.horizontal-scroller__wrapper` in
 * resources/styles/components/horizontal-scroller.css — not editor-tunable.
 */

$c = is_array($component ?? null) ? $component : [];

$body_text_tone = Component::bodyTextTone($c);
// Canonical opener: always strip the inherited horizontal grid inset because
// the scroller renders its own full-bleed strip + wrapper shell.
$gridClasses = Grid::stripHorizontalInsetPadding(
    isset($c['_grid_classes']) && is_string($c['_grid_classes']) ? $c['_grid_classes'] : ''
);
$backgroundHandled = !empty($c['_background_handled']);
$backgroundData = $backgroundHandled ? Background::getEmptyStub() : Background::process($c);
$backgroundClasses = $backgroundData['classes'] ?? '';
$backgroundStyles = $backgroundData['styles'] ?? '';

$header_text = $c['scroller_header_text'] ?? '';
$header_text_color = $c['scroller_header_text_color'] ?? 'text-white';
// Section H2 default — 58 px Canela (text-6xl), matching
// Component::sectionHeadingClasses(). The dropdown still allows opting up
// to text-7xl/text-8xl/text-9xl when a landing page genuinely wants a
// hero-scale header strip.
$header_text_size = $c['scroller_header_text_size'] ?? 'text-6xl';
$header_text_weight = Typography::coerceCanelaHeadingWeight(
    (string) ($c['scroller_header_text_weight'] ?? 'font-normal')
);
$header_alignment = $c['scroller_header_alignment'] ?? 'top';
$header_text_alignment = $c['scroller_header_text_alignment'] ?? 'left';
$subheading_text = $c['scroller_subheading_text'] ?? '';
$subheading_text_color = $c['scroller_subheading_text_color'] ?? 'text-white';
$subheading_text_size = $c['scroller_subheading_text_size'] ?? 'text-xl';
$subheading_text_weight = $c['scroller_subheading_text_weight'] ?? 'font-medium';
$button_text = $c['scroller_button_text'] ?? '';
$button_link = is_array($c['scroller_button_link'] ?? null) ? $c['scroller_button_link'] : [];
$button_variant = match($c['scroller_button_variant'] ?? 'primary') {
    'outline', 'primary', 'secondary' => $c['scroller_button_variant'] ?? 'primary',
    default => 'primary',
};
$button_size = match($c['scroller_button_size'] ?? 'md') {
    'lg', 'md', 'sm' => $c['scroller_button_size'] ?? 'md',
    default => 'md',
};
$button_show_arrow = $c['scroller_button_show_arrow'] ?? true;
$body_text = $c['scroller_body_text'] ?? '';
$body_text_color_raw = $c['scroller_body_text_color'] ?? '';
$scroll_cards = is_array($c['scroller_items'] ?? null) ? $c['scroller_items'] : [];
$scroll_speed = $c['scroller_speed'] ?? 'medium';
$disable_scroll = !empty($c['scroller_disabled']);
// Horizontal gap between row items (logos/cards). Set on the flex container so it cannot be lost to inheritance.
$raw_strip_spacing = $c['scroller_item_spacing'] ?? null;
if ($raw_strip_spacing === null || $raw_strip_spacing === '' || $raw_strip_spacing === false) {
    $strip_item_spacing = 32;
} elseif (is_numeric($raw_strip_spacing)) {
    $strip_item_spacing = (int) round((float) $raw_strip_spacing);
} elseif (is_string($raw_strip_spacing) && preg_match('/\d+/', $raw_strip_spacing, $strip_spacing_match)) {
    $strip_item_spacing = (int) $strip_spacing_match[0];
} else {
    $strip_item_spacing = 32;
}
$strip_item_spacing = max(12, min(6000, $strip_item_spacing));
$horizontal_scroller_gap_css = '--hs-item-gap:' . $strip_item_spacing . 'px';
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

// Keep heading sizes constrained to the Typography header dropdown choices.
// Default is text-6xl (58 px) so the strip header sits on the canonical
// section H2 ramp; editors can opt into larger sizes per layout.
//
// Historical posts saved `text-7xl` (the old 64 px default before the spec
// was lowered to 58 px). Coerce that legacy value here so we don't have to
// migrate every existing horizontal_scroller row.
$header_desktop_size = in_array(
    $header_text_size,
    array_keys(Typography::getHeaderSizeChoices()),
    true
) ? $header_text_size : 'text-6xl';
if ($header_desktop_size === 'text-7xl') {
    $header_desktop_size = 'text-6xl';
}
// Pair every editor-chosen desktop size with a mobile fallback so the
// header always steps down on phones (matches the canonical section H2
// ramp documented in Component::sectionHeadingClasses()).
$header_mobile_size = match ($header_desktop_size) {
    'text-3xl' => 'text-2xl',
    'text-4xl' => 'text-3xl',
    'text-5xl' => 'text-4xl',
    'text-6xl' => 'text-5xl',
    'text-7xl' => 'text-5xl', // canonical 48 → 64
    'text-8xl' => 'text-6xl',
    'text-9xl' => 'text-7xl',
    default => 'text-5xl',
};
$header_size_class = $header_mobile_size . ' md:' . $header_desktop_size;
$header_text_color_class = match ($header_text_color) {
    'text-black', 'text-brand-500', 'text-deep-moss', 'text-faded-olive', 'text-text-muted', 'text-white', 'text-white/80' => $header_text_color,
    default => 'text-white'
};
$header_text_weight_class = $header_text_weight;

$subheading_size_class = Typography::validateBodySize($subheading_text_size ?? null, 'text-xl');
$subheading_text_color_class = match ($subheading_text_color) {
    'text-black', 'text-brand-500', 'text-deep-moss', 'text-faded-olive', 'text-text-muted', 'text-white', 'text-white/80' => $subheading_text_color,
    default => 'text-white'
};
$subheading_text_weight_class = match($subheading_text_weight) {
    'font-light', 'font-normal', 'font-medium', 'font-semibold', 'font-bold' => $subheading_text_weight,
    default => 'font-medium',
};
$header_padding_class = Padding::getHeaderSubheaderPaddingClasses(
    $c['scroller_header_padding_top'] ?? 'none',
    $c['scroller_header_padding_bottom'] ?? 'none'
);
$subheader_padding_class = Padding::getHeaderSubheaderPaddingClasses(
    $c['scroller_subheader_padding_top'] ?? 'none',
    $c['scroller_subheader_padding_bottom'] ?? 'none'
);
$body_padding_class = Padding::getHeaderSubheaderPaddingClasses(
    $c['scroller_body_padding_top'] ?? 'none',
    $c['scroller_body_padding_bottom'] ?? 'none'
);
$body_classes = Typography::classes(
    'body',
    $c['scroller_body_text_size'] ?? 'text-xl',
    $c['scroller_body_text_weight'] ?? 'font-medium'
);
$allowed_scroller_body_colors = [
    'text-black',
    'text-brand-500',
    'text-deep-moss',
    'text-faded-olive',
    'text-text-muted',
    'text-white',
    'text-white/80',
];
$scroller_body_color = is_string($body_text_color_raw) ? trim($body_text_color_raw) : '';
$intro_body_color_class = ($scroller_body_color !== '' && in_array($scroller_body_color, $allowed_scroller_body_colors, true))
    ? $scroller_body_color
    : Component::bodyTextTone($c, 'light-band');

$intro_flush_to_content = !empty($c['scroller_intro_flush']);

$item_kicker_classes = Typography::classes(
    'body',
    $c['scroller_item_kicker_size'] ?? 'text-xs',
    $c['scroller_item_kicker_weight'] ?? 'font-semibold'
);
$item_heading_classes = Typography::classes(
    'heading',
    $c['scroller_item_heading_size'] ?? 'text-2xl',
    $c['scroller_item_heading_weight'] ?? 'font-normal'
);
$item_body_classes = Typography::classes(
    'body',
    $c['scroller_item_body_size'] ?? 'text-lg',
    $c['scroller_item_body_weight'] ?? 'font-normal'
);

$item_kicker_padding_class = Padding::getHeaderSubheaderPaddingClasses($c['scroller_item_kicker_padding_top'] ?? 'none', $c['scroller_item_kicker_padding_bottom'] ?? 'none');
$item_heading_padding_class = Padding::getHeaderSubheaderPaddingClasses($c['scroller_item_heading_padding_top'] ?? 'none', $c['scroller_item_heading_padding_bottom'] ?? 'none');
$item_body_padding_class = Padding::getHeaderSubheaderPaddingClasses($c['scroller_item_body_padding_top'] ?? 'none', $c['scroller_item_body_padding_bottom'] ?? 'none');

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

    $image = $item['item_image'] ?? null;
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
    $alt_text = trim((string)($item['item_image_alt'] ?? ''));

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
    ];
}

// Horizontal grid inset is already stripped at the canonical opener above.
$fullBleedScrollerStrip = ! empty($normalized_items);

$section_style_parts = [];
if (is_string($backgroundStyles) && $backgroundStyles !== '') {
    $section_style_parts[] = $backgroundStyles;
}
$section_styles_attr = implode('; ', array_filter($section_style_parts));

$has_any_content = $hasHeaderBlock || $hasButton || !empty($normalized_items);
$live_region_id = 'horizontal-scroller-description-' . uniqid();

// Match every other component’s `$root` convention so the editor placeholder
// fallback can reuse the same name. Outer vertical rhythm is owned by the
// parent flexible-components grid (`gap-y-32`), not by this component.
$root = $gridClasses;
@endphp

@if($has_any_content)
<section
    class="horizontal-scroller {{ $gridClasses }} relative {{ $backgroundClasses }} @if($disable_scroll) horizontal-scroller--disable-scroll @endif"
    @if($section_styles_attr !== '') style="{{ esc_attr($section_styles_attr) }}" @endif
    data-component-root
    data-horizontal-scroller
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
            <div class="horizontal-scroller__header mx-auto flex w-full max-w-8xl flex-col justify-start {{ LayoutShell::GUTTER_X }} lg:min-h-[240px] {{ $header_alignment_class }} {{ $header_text_align_class }}">
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
                            <div class="{{ $body_classes }} {{ $intro_body_color_class }} prose prose-neutral max-w-none {{ $body_padding_class }}">
                                {!! TextFormatter::rich((string) $body_text) !!}
                            </div>
                        @endif
                    </div>
                @endif

                @if($hasButton)
                    @php
                        /*
                         * Map editor variant/size choices onto the canonical CTA classes
                         * defined in `resources/styles/app.css` (`.btn` family). This keeps
                         * the horizontal-scroller CTA hovering identically to every other
                         * CTA on the site (Figma "Button Hover" — padding widens, fills
                         * stay put). DO NOT add inline `px-*` / `py-*` here — that
                         * silently overrides `.btn-primary`'s `hover:px-[34px]` and breaks
                         * the canonical hover-widen.
                         *
                         * The Culver Square design system ships two paint variants
                         * (primary, outline). The legacy "secondary" choice from this
                         * component's older API collapses to `btn-outline` so editors who
                         * picked it keep a sensible button.
                         */
                        $btn_variant_class = match ($button_variant) {
                            'outline', 'secondary' => 'btn-outline',
                            default => 'btn-primary',
                        };
                        $btn_size_class = match ($button_size) {
                            'lg' => ' btn-large',
                            default => '',
                        };
                        $btn_classes = trim('btn ' . $btn_variant_class . $btn_size_class . ' gap-2');
                        $btn_target = trim((string) ($button_link['target'] ?? ''));
                    @endphp
                    <div class="horizontal-scroller__header-cta mt-6">
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
            <div class="@if($fullBleedScrollerStrip) horizontal-scroller__strip-breakout @endif">
            <div class="horizontal-scroller__wrapper {{ $disable_scroll ? 'overflow-visible' : 'overflow-hidden' }} {{ $scroll_speed_class }} {{ $header_text_color_class }}">
                <div class="horizontal-scroller__container" style="{{ esc_attr($horizontal_scroller_gap_css) }}" aria-label="{{ $disable_scroll ? __('Floating content', 'culvers') : __('Horizontal scrolling floating content', 'culvers') }}">
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
                                    </div>
                                @elseif(($item['type'] === 'image' || $item['type'] === 'image_text') && is_array($item['image']) && !empty($item['image']['url']))
                                    @php
                                        $render_image = $item['image'];
                                        if ($item['alt_text'] !== '') {
                                            $render_image = array_merge($render_image, ['alt' => $item['alt_text']]);
                                        }
                                    @endphp
                                    <div class="horizontal-scroller-item__media">
                                        {!! Image::render($render_image, ['size' => 'large', 'class' => 'horizontal-scroller-item__image']) !!}
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
@elseif(current_user_can('edit_posts'))
@include('partials.component-editor-placeholder', [
    'wrapperClasses' => $root,
    'message' => __('Add header text or scroller items to this block.', 'culvers'),
])
@endif
