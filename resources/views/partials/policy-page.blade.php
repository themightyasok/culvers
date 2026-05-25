{{--
  Legal policy layout — matches Figma policy templates (dark hero + lime keyline + two-column body).
  Content comes from App\Legal\* definitions (synced from Culver Square developer Figma).
--}}
@php
  /** @var string $hero_title */
  /** @var string $hero_subtitle */
  /** @var list<array{aside: string, body: string}> $sections */
  use App\Helpers\Component;
  use App\Helpers\LayoutShell;

  $hero_title = isset($hero_title) ? (string) $hero_title : '';
  $hero_subtitle = isset($hero_subtitle) ? (string) $hero_subtitle : '';
  $sections = isset($sections) && is_array($sections) ? $sections : [];
@endphp

{{-- Figma policy headers (`51:6558`): faded olive + 50% scrim, overlay diamond, 646px band. --}}
<section
  class="policy-page__hero text-hero-viewport hero-slider--viewport relative isolate flex min-h-[480px] flex-col items-center justify-center overflow-hidden px-6 pb-14 pt-[calc(var(--site-header-offset,11.25rem)+2.5rem)] text-center text-white sm:px-10 md:min-h-[580px] md:px-12 md:pb-20 md:pt-[calc(var(--site-header-offset,11.25rem)+3rem)] lg:min-h-[646px]"
  aria-labelledby="policy-page-heading">
  @include('partials.text-hero-backdrop')
  <div class="relative z-10 mx-auto w-full max-w-5xl px-6 sm:px-10 md:px-12">
    @if($hero_title !== '')
      <h1 id="policy-page-heading" class="image-hero__title m-0 md:whitespace-nowrap {{ Component::imageHeroTitleClasses('text-brand-500') }}">
        {{ esc_html($hero_title) }}
      </h1>
    @endif
    @if($hero_subtitle !== '')
      <p class="mx-auto mt-5 max-w-[44rem] font-label text-sm font-semibold uppercase leading-relaxed tracking-[0.22em] text-white md:text-base">
        {{ esc_html($hero_subtitle) }}
      </p>
    @endif
  </div>
</section>

<div class="policy-page__surface relative text-deep-moss">
  <div class="relative z-10 mx-auto w-full max-w-5xl {{ LayoutShell::GUTTER_X }} py-12 md:py-16 lg:py-20">
    <div class="policy-page__stack flex flex-col gap-12 md:gap-14 lg:gap-16">
      @foreach($sections as $section)
        @php
          $aside = isset($section['aside']) ? (string) $section['aside'] : '';
          $body = isset($section['body']) ? (string) $section['body'] : '';
        @endphp
        @if($aside !== '' || $body !== '')
          <article
            class="policy-page__row grid grid-cols-1 gap-8 border-b border-deep-moss/[0.12] pb-12 last:border-b-0 last:pb-0 md:gap-10 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] lg:gap-x-16 xl:gap-x-24">
            {{-- Sheet feedback row 27: align aside + body typography with Figma — bigger aside
                 heading (Canela ~32px) and slightly bigger body copy with looser leading. --}}
            @if($aside !== '')
              <aside class="policy-page__aside {{ Component::mobilePanelSubheadClasses('text-faded-olive', 'lg:max-w-[22rem] lg:pt-1 rt-link-prose') }}">
                {!! $aside !!}
              </aside>
            @endif
            @if($body !== '')
              <div
                class="policy-page__body prose max-w-none font-sans text-xl font-light leading-[1.5] text-deep-moss prose-headings:font-heading prose-headings:text-deep-moss prose-p:text-deep-moss prose-p:font-light prose-li:text-deep-moss prose-li:font-light prose-strong:text-deep-moss prose-strong:font-medium rt-link-prose md:text-xl">
                {!! $body !!}
              </div>
            @endif
          </article>
        @endif
      @endforeach
    </div>
  </div>
</div>
