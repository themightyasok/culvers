@php
  /**
   * Shared intro stack: optional heading → optional subheading → optional body → optional CTA.
   *
   * @var string $headingTag
   * @var string $heading
   * @var string $headingClasses
   * @var string $subheading
   * @var string $subheadingClasses
   * @var string $bodyHtml
   * @var string $bodyClasses
   * @var string $ctaLabel
   * @var string $ctaUrl
   * @var string $ctaSize
   * @var string $wrapperClasses
   * @var string $contentAlignClasses
   */

  use App\Helpers\Component;

  $headingTag = $headingTag ?? 'h2';
  $heading = trim((string) ($heading ?? ''));
  $headingClasses = trim((string) ($headingClasses ?? Component::sectionIntroHeadingClasses('text-faded-olive')));
  $subheading = trim((string) ($subheading ?? ''));
  $subheadingClasses = trim((string) ($subheadingClasses ?? Component::sectionIntroBodyClasses('text-deep-moss')));
  $bodyHtml = (string) ($bodyHtml ?? '');
  $bodyClasses = trim((string) ($bodyClasses ?? Component::sectionIntroBodyClasses('text-deep-moss')));
  $ctaSize = trim((string) ($ctaSize ?? 'large'));
  $wrapperClasses = trim((string) ($wrapperClasses ?? 'mx-auto max-w-[52rem] text-center'));
  $contentAlignClasses = trim((string) ($contentAlignClasses ?? 'items-center'));

  /*
   * BladeOne keeps parent scope across flexible rows — only honour CTA when the
   * caller opts in, otherwise a prior row’s $ctaLabel/$ctaUrl (e.g. info_block)
   * bleeds into unrelated intro stacks (three_card_block “Plan my visit” ghost).
   */
  $introStackIncludeCta = ($introStackIncludeCta ?? false) === true;
  $ctaLabel = $introStackIncludeCta ? trim((string) ($ctaLabel ?? '')) : '';
  $ctaUrl = $introStackIncludeCta ? trim((string) ($ctaUrl ?? '')) : '';

  $hasBody = trim(strip_tags($bodyHtml)) !== '';
  $hasSub = $subheading !== '';
  $hasCta = $ctaLabel !== '' && $ctaUrl !== '';
  $hasContentGroup = $hasSub || $hasBody || $hasCta;
  $ctaGapClass = $hasSub || $hasBody
      ? Component::sectionBodyToCtaGapClasses('flex justify-center')
      : ($heading !== '' ? Component::sectionHeadingToBodyGapClasses('flex justify-center') : 'flex justify-center');

  $introStackTailGap = trim((string) ($introStackTailGap ?? ''));
  if ($introStackTailGap === '' && ! $hasCta) {
      if ($hasBody || $hasSub) {
          $introStackTailGap = Component::sectionBodyToFollowContentGapClasses();
      } elseif ($heading !== '') {
          $introStackTailGap = Component::sectionHeadingToFollowContentGapClasses();
      }
  }
@endphp

@if($heading !== '' || $hasContentGroup)
  <header class="{{ esc_attr(trim('section-intro-stack flex flex-col ' . $contentAlignClasses . ' ' . $wrapperClasses . ' ' . $introStackTailGap)) }}">
    @if($heading !== '')
      <{{ $headingTag }} class="{{ esc_attr($headingClasses) }}">
        {{ esc_html($heading) }}
      </{{ $headingTag }}>
    @endif

    @if($hasContentGroup)
      <div class="{{ Component::sectionIntroContentStackClasses($contentAlignClasses) }}">
        @if($hasSub)
          <p class="{{ esc_attr($subheadingClasses) }}">
            {!! nl2br(e($subheading)) !!}
          </p>
        @endif

        @if($hasBody)
          <div class="{{ esc_attr(trim($bodyClasses . ($hasSub ? ' ' . Component::sectionSubheadingToBodyGapClasses() : ''))) }}">
            {!! $bodyHtml !!}
          </div>
        @endif

        @if($hasCta)
          <div class="{{ esc_attr($ctaGapClass) }}">
            @include('components.button', [
                'label' => $ctaLabel,
                'href' => $ctaUrl,
                'size' => $ctaSize,
            ])
          </div>
        @endif
      </div>
    @endif
  </header>
@endif
