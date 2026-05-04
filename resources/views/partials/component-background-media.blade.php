@php
$backgroundData = is_array($backgroundData ?? null) ? $backgroundData : [];
$image = $backgroundData['image'] ?? null;
$video = $backgroundData['video'] ?? null;
$videoEmbedUrl = is_string($backgroundData['video_embed_url'] ?? null) ? (string) $backgroundData['video_embed_url'] : '';
$videoPoster = $backgroundData['video_poster'] ?? null;
$overlayStyles = (string) ($backgroundData['overlay_styles'] ?? '');
$centeredCardStyles = (string) ($backgroundData['centered_card_styles'] ?? '');
$centeredCardClasses = trim((string) ($backgroundData['centered_card_classes'] ?? ''));
$parallaxEnabled = (bool) ($backgroundData['parallax'] ?? true);
$backgroundType = (string) ($backgroundData['type'] ?? '');
$backgroundParallaxAxis = isset($backgroundParallaxAxis) && $backgroundParallaxAxis === 'x' ? 'x' : 'y';
@endphp

@if(!empty($image) && is_array($image) && isset($image['url']))
    @if($backgroundType === 'image_centered')
        <div class="pointer-events-none absolute inset-0 z-0" aria-hidden="true">
            <div class="shadow-card mx-auto h-[80%] w-full max-w-[500px] overflow-hidden rounded-2xl {{ $centeredCardClasses }}"
                 @if($centeredCardStyles !== '') style="{{ $centeredCardStyles }}" @endif>
                <img class="h-full w-full object-cover"
                     @if($parallaxEnabled) data-background-parallax-image="1" data-background-parallax-axis="{{ $backgroundParallaxAxis }}" @endif
                     src="{{ esc_url($image['url']) }}"
                     alt=""
                     decoding="async">
            </div>
        </div>
    @else
        <div class="pointer-events-none absolute inset-0 z-0 overflow-hidden" aria-hidden="true">
            <img class="h-full w-full object-cover"
                 @if($parallaxEnabled) data-background-parallax-image="1" data-background-parallax-axis="{{ $backgroundParallaxAxis }}" @endif
                 src="{{ esc_url($image['url']) }}"
                 alt=""
                 decoding="async">
        </div>
    @endif
@endif

@if(!empty($video) && is_array($video) && isset($video['url']))
    <video class="absolute inset-0 z-0 h-full w-full object-cover" data-bg-video="1" autoplay muted loop playsinline
           preload="metadata"
           @if(!empty($videoPoster['url'])) poster="{{ esc_url($videoPoster['url']) }}" @endif
           aria-hidden="true">
        <source src="{{ esc_url($video['url']) }}" type="{{ esc_attr($video['mime_type'] ?? 'video/mp4') }}">
    </video>
@elseif($videoEmbedUrl !== '')
    <div class="pointer-events-none absolute inset-0 z-0 overflow-hidden" aria-hidden="true">
        <iframe class="h-full w-full"
                src="{{ esc_url($videoEmbedUrl) }}"
                title="{{ esc_attr__('Background video', 'culvers') }}"
                loading="lazy"
                allow="autoplay; encrypted-media; picture-in-picture"
                allowfullscreen
                tabindex="-1"></iframe>
    </div>
@endif

@if($overlayStyles !== '')
    <div class="absolute inset-0 z-10" style="{{ $overlayStyles }}" aria-hidden="true"></div>
@endif
