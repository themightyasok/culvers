{{--
  Legal policy layout — matches Figma policy templates (dark hero + lime keyline + two-column body).
  Content comes from App\Legal\* definitions (synced from Culver Square developer Figma).
--}}
@php
  /** @var string $hero_title */
  /** @var string $hero_subtitle */
  /** @var list<array{aside: string, body: string}> $sections */
  use App\Helpers\LayoutShell;

  $hero_title = isset($hero_title) ? (string) $hero_title : '';
  $hero_subtitle = isset($hero_subtitle) ? (string) $hero_subtitle : '';
  $sections = isset($sections) && is_array($sections) ? $sections : [];
@endphp

{{-- Sheet feedback row 27: bigger heading + soft-light rotated square decoration so the policy
     hero reads on-brand instead of as a flat moss band. --}}
<section
  class="policy-page__hero hero-slider--viewport relative isolate flex min-h-[min(560px,88svh)] flex-col items-center justify-center overflow-hidden bg-deep-moss px-6 pb-14 pt-[calc(var(--site-header-offset,11.25rem)+2.5rem)] text-center text-white sm:px-10 md:min-h-[620px] md:px-12 md:pb-20 md:pt-[calc(var(--site-header-offset,11.25rem)+3rem)]"
  aria-labelledby="policy-page-heading">
  {{-- Same decorative diamond used by the homepage hero (Figma `51:4920`):
       1048 px white-at-28% square, mix-blend soft-light, anchored upper-left. --}}
  <div
    class="pointer-events-none absolute left-[12.5%] top-[-260px] hidden size-[1048px] -rotate-45 bg-white/[0.28] mix-blend-soft-light md:block"
    aria-hidden="true"></div>
  <div class="relative z-10 mx-auto w-full max-w-[56rem]">
    @if($hero_title !== '')
      <h1 id="policy-page-heading" class="m-0 font-heading text-5xl font-normal leading-[1.08] tracking-tight text-brand-500 sm:text-7xl md:text-8xl lg:text-9xl lg:leading-[1.05]">
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
  <div class="relative z-10 {{ LayoutShell::INNER_MAX_GUTTERED }} py-12 md:py-16 lg:py-20">
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
              <aside class="policy-page__aside font-heading text-3xl leading-[1.15] text-faded-olive md:text-4xl lg:max-w-[22rem] lg:pt-1 rt-link-prose">
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
