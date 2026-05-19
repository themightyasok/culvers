@php
  use App\Customizer\FooterCustomizer;
  use App\Footer\FooterNewsletterImage;

  $newsletterImgId = FooterNewsletterImage::attachmentIdForCurrentView();
  $newsletterAction = FooterCustomizer::newsletterFormAction();
  $instagramUrl = FooterCustomizer::instagramUrl();
  $facebookUrl = FooterCustomizer::facebookUrl();

  // FooterCustomizer string helpers + map link URL.
  $mapUrl = FooterCustomizer::gettingHereMapUrl();
  $contactEmail = FooterCustomizer::contactEmail();

  $hasFooterMenuWhatsHere = has_nav_menu('footer_column_one');
  $hasFooterMenuUsefulLinks = has_nav_menu('footer_column_two');

  $newsletterFallbackRel = 'resources/images/footer-newsletter-placeholder.jpg';
  $newsletterFallbackAbs = get_template_directory() . '/' . $newsletterFallbackRel;
  $newsletterFallbackUri = is_readable($newsletterFallbackAbs)
      ? get_theme_file_uri($newsletterFallbackRel)
      : '';

  /* Fixed legal row — always these four pages in this order (menu location is unused here
     so editors cannot ship wrong labels/URLs to production). */
  $footerLegalListClass =
      'footer-nav__list footer-nav__list--legal flex flex-wrap items-center justify-center gap-x-[18px] gap-y-2 whitespace-normal font-label text-xs font-normal uppercase leading-[1.3] tracking-wider text-lighter-cream [&>li]:flex [&>li]:shrink-0 [&>li]:items-center [&>li>a]:inline-flex [&>li>a]:min-h-[2rem] [&>li>a]:items-center [&>li>a]:px-0.5 [&>li>a]:py-1';

  $societyLogoAbs = get_template_directory() . '/resources/images/footer/society-studios-wordmark.svg';
  $societyLogoSvg = '';
  if (is_readable($societyLogoAbs)) {
      $societyLogoRaw = (string) file_get_contents($societyLogoAbs);
      if ($societyLogoRaw !== '') {
          $societyLogoSvg = (string) preg_replace(
              '/<svg\b/',
              '<svg class="block h-[11px] w-auto max-w-[min(100%,10.5rem)] text-lighter-cream"',
              $societyLogoRaw,
              1,
          );
      }
  }
@endphp

{{--
  Site footer — Culver Square

  Naming:
    site-footer          Outer landmark (`site-footer__*` for major bands).
  footer-nav__link*    Column/legal/social/phone link styles (`addComponents` in tailwind.config.js).
  footer-nav           Shared list classes (`footer-nav__list`, modifiers).

  Legal row under wordmark always renders Cookie → Accessibility → Privacy → Terms (`home_url`).
  Theme still registers `footer_brand_subnav` for tooling/seeds but the template does not read it.

  Layout:
    `site-footer__columns` uses gutter padding, then `max-w-8xl` inner — pair with
    `mega-nav__bar-gutter` / `mega-nav__bar-row` in the header.

  Sections (top → bottom): newsletter band → four columns → wordmark → legal row → accent strip.
--}}
<footer class="site-footer">
  <div
    class="site-footer__columns relative overflow-visible bg-faded-olive px-4 pb-4 pt-0 text-light-cream md:px-12 md:pb-5 lg:pb-5">
    <div class="relative z-10 mx-auto w-full max-w-8xl">
      {{-- Newsletter: vertical centre sits on the white/olive boundary (50% above / 50% on olive).
           `-translate-y-1/2` centres on the footer top edge; `-mb-[half min-heights]` collapses the
           phantom layout gap (transform does not affect flow). Spacer in `layouts/app.blade.php`
           matches these halves so main content is not overlapped. --}}
      <section
        class="footer-newsletter-band relative z-20 -mb-[150px] -translate-y-1/2 md:-mb-[190px] lg:-mb-[210px]"
        aria-labelledby="footer-newsletter-heading">
        <div
          class="footer-newsletter relative min-h-[300px] overflow-hidden rounded-lg md:min-h-[380px] md:rounded-[10px] lg:min-h-[420px]"
          data-background-parallax-trigger>
          <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-[inherit]" aria-hidden="true">
            @if($newsletterImgId > 0 || $newsletterFallbackUri !== '')
              <div class="absolute inset-0 size-full" data-background-parallax-image="1">
                @if($newsletterImgId > 0)
                  {!! wp_get_attachment_image(
                      $newsletterImgId,
                      'large',
                      false,
                      [
                          'class' => 'absolute inset-0 size-full object-cover object-center',
                          'sizes' => '(max-width: 768px) 100vw, 1440px',
                      ]
                  ) !!}
                @else
                  <img
                    src="{{ esc_url($newsletterFallbackUri) }}"
                    alt=""
                    class="absolute inset-0 size-full object-cover object-center"
                    loading="lazy"
                    decoding="async" />
                @endif
              </div>
            @else
              <div class="absolute inset-0 bg-deep-moss"></div>
            @endif
          </div>

          {{-- Headline uses its own max width; pill is capped independently (does not stretch to headline width). CTA hugs the banner's right edge with a modest gutter.
               Grid inherits the band's `min-h-*` so the empty space below the CTA stack collapses
               and `items-center` actually centres the heading + email pill vertically inside
               the photographic band (otherwise the grid takes content-height and the stack sits
               near the top, leaving the image bottom-heavy on desktop). --}}
          <div class="relative z-10 grid min-h-[300px] grid-cols-1 items-center gap-8 px-6 py-11 md:min-h-[380px] md:grid-cols-2 md:gap-10 md:px-12 md:py-14 lg:min-h-[420px] lg:gap-14 lg:px-14 lg:py-16">
            <div class="hidden min-h-[80px] md:block" aria-hidden="true"></div>
            <div
              class="footer-newsletter__cta flex w-full flex-col items-center gap-7 justify-self-center md:mr-3 md:w-auto md:justify-self-end lg:mr-5 xl:mr-6">
              <div class="w-full max-w-[26rem] text-center md:mx-0">
                <h2
                  id="footer-newsletter-heading"
                  class="w-full text-center font-heading text-3xl font-normal leading-[1.1] text-white md:text-4xl">
                  <span class="block text-glowleaf">{{ __('Get the latest news,', 'culvers') }}</span>
                  <span class="block"><span class="text-glowleaf">{{ __('offers & events', 'culvers') }}</span> <span class="text-white">{{ __('delivered', 'culvers') }}</span></span>
                  <span class="block text-white">{{ __('directly to your inbox', 'culvers') }}</span>
                </h2>
                @php $newsBody = FooterCustomizer::newsletterBody(); @endphp
                @if($newsBody !== '')
                  <p class="mt-4 font-sans text-lg leading-relaxed text-light-cream/88">
                    {{ esc_html($newsBody) }}
                  </p>
                @endif
              </div>

              <form
                class="footer-newsletter-form pointer-events-auto w-full max-w-[363px] shrink-0"
                method="post"
                action="{{ esc_url($newsletterAction ?? '#') }}"
                @if($newsletterAction === null) onsubmit="event.preventDefault(); return false;" @endif>
                <label class="sr-only" for="footer-newsletter-email">{{ __('Email address', 'culvers') }}</label>
                <div
                  class="flex w-full items-center gap-2 rounded-full border-[1.5px] border-glowleaf bg-transparent py-1.5 pl-6 pr-2 md:pr-2.5">
                  <input
                    id="footer-newsletter-email"
                    name="EMAIL"
                    type="email"
                    autocomplete="email"
                    placeholder="{{ esc_attr(FooterCustomizer::newsletterPlaceholder()) }}"
                    class="min-w-0 flex-1 border-0 bg-transparent font-label text-xs font-normal leading-8 tracking-widest text-white placeholder:font-semibold placeholder:uppercase placeholder:tracking-widest placeholder:text-white placeholder:opacity-100 focus:ring-0 focus:outline-none" />
                  <button
                    type="submit"
                    class="inline-flex shrink-0 cursor-pointer items-center justify-center rounded-full py-2 pl-0.5 pr-1 text-lighter-cream transition-colors hover:bg-white/10 culvers-focus-ring"
                    aria-label="{{ esc_attr__('Subscribe', 'culvers') }}">
                    @include('partials.footer-newsletter-submit-arrow', [
                        'arrowClass' => 'block size-4 shrink-0 text-current',
                    ])
                  </button>
                </div>
                @if($newsletterAction === null && current_user_can('customize'))
                  <p class="mt-3 text-center font-sans text-xs text-light-cream/55">
                    {{ __('Connect your ESP URL under Appearance → Customize → Culver Square footer.', 'culvers') }}
                  </p>
                @endif
              </form>
            </div>
          </div>
        </div>
      </section>

      {{-- Column menus (What’s Here / Useful Links + address blocks). Tight 16-unit padding-top
           on lg — overlaps beneath the newsletter are handled by `.footer-newsletter-band` + spacer
           in `layouts/app.blade.php` (centred straddle), not extra padding here. --}}
      <div class="relative z-10 grid grid-cols-1 gap-12 pt-20 sm:pt-24 lg:grid-cols-4 lg:gap-10 lg:pt-16 xl:gap-14">
        <div class="flex flex-col">
          <h2 class="font-heading text-3xl text-lighter-cream">
            {{ esc_html(FooterCustomizer::gettingHereTitle()) }}
          </h2>
          @php $addr = FooterCustomizer::gettingHereAddress(); @endphp
          @if($addr !== '')
            {{-- Sheet feedback row 9: address bumped to text-xl (20 px Halyard Book) for parity with
                 Figma footer column copy. --}}
            <div class="mt-6 font-sans text-xl font-light leading-[1.4] text-light-cream">
              {!! nl2br(esc_html($addr)) !!}
            </div>
          @endif
          @if($mapUrl !== '')
            {{--
              Figma 51:5147: glowleaf label + icon, 2 px underline hugs text+arrow only (`w-max self-start`).
              Explicit border/text colours beat parent `text-light-cream` / flex stretch making a full‑width rule.
            --}}
            <a
              href="{{ esc_url($mapUrl) }}"
              class="footer-getting-here-map-link mt-6 inline-flex max-w-full w-max shrink-0 self-start flex-nowrap items-center gap-2 border-b-2 border-glowleaf pb-0.5 font-label text-sm font-semibold uppercase leading-[1.3] tracking-[1px] text-glowleaf transition-colors hover:border-lighter-cream hover:text-lighter-cream"
              @if(str_starts_with($mapUrl, 'http')) target="_blank" rel="noopener noreferrer" @endif>
              {{ esc_html(FooterCustomizer::gettingHereMapLabel()) }}
              @include('partials.footer-external-arrow-figma')
              @if(str_starts_with($mapUrl, 'http'))
                <span class="sr-only">{{ __('(opens in new tab)', 'culvers') }}</span>
              @endif
            </a>
          @endif
        </div>

        <div class="flex flex-col">
          <h2 class="font-heading text-3xl text-lighter-cream">
            {{ esc_html(FooterCustomizer::contactTitle()) }}
          </h2>
          @php $phone = FooterCustomizer::contactPhone(); @endphp
          @if($phone !== '')
            <a
              class="footer-nav__link-phone mt-6 block"
              href="{{ esc_url('tel:' . preg_replace('/\s+/', '', $phone)) }}">
              {{ esc_html($phone) }}
            </a>
          @endif
          @if($contactEmail !== '')
            <a
              class="footer-link--persistent-underline mt-2 block w-fit font-sans text-base text-glowleaf transition-colors hover:text-lighter-cream"
              href="{{ esc_url('mailto:' . $contactEmail) }}">
              {{ esc_html($contactEmail) }}
            </a>
          @endif
          @if(($instagramUrl !== '' && $instagramUrl !== '#') || ($facebookUrl !== '' && $facebookUrl !== '#'))
            <div class="mt-8 flex flex-wrap gap-x-8 gap-y-3">
              @if($instagramUrl !== '' && $instagramUrl !== '#')
                <a
                  class="footer-nav__link-social"
                  href="{{ esc_url($instagramUrl) }}"
                  target="_blank"
                  rel="noopener noreferrer">
                  @include('partials.figma-social-icon', [
                      'social_icon_variant' => 'instagram',
                      'social_icon_class' => 'size-6 shrink-0 overflow-visible text-white',
                  ])
                  {{ __('Instagram', 'culvers') }}
                </a>
              @endif
              @if($facebookUrl !== '' && $facebookUrl !== '#')
                <a
                  class="footer-nav__link-social"
                  href="{{ esc_url($facebookUrl) }}"
                  target="_blank"
                  rel="noopener noreferrer">
                  @include('partials.figma-social-icon', [
                      'social_icon_variant' => 'facebook',
                      'social_icon_class' => 'size-6 shrink-0 text-white',
                  ])
                  {{ __('Facebook', 'culvers') }}
                </a>
              @endif
            </div>
          @endif
        </div>

        <div class="hidden flex-col lg:flex">
          <h2 class="font-heading text-3xl text-lighter-cream">
            {{ esc_html(FooterCustomizer::columnOneTitle()) }}
          </h2>
          @if($hasFooterMenuWhatsHere)
            <nav
              class="footer-nav footer-nav--col mt-6"
              aria-label="{{ esc_attr(FooterCustomizer::columnOneTitle()) }}">
              {!! wp_nav_menu([
                  'theme_location' => 'footer_column_one',
                  'container' => false,
                  'menu_class' => 'footer-nav__list flex flex-col gap-3',
                  'fallback_cb' => false,
                  'depth' => 1,
                  'echo' => false,
              ]) !!}
            </nav>
          @elseif(current_user_can('edit_theme_options'))
            <p class="mt-6 font-sans text-base text-light-cream/55">
              {{ __('Assign a menu to Appearance → Menus → “Footer column 3 — What’s Here”.', 'culvers') }}
            </p>
          @endif
        </div>

        <div class="hidden flex-col lg:flex">
          <h2 class="font-heading text-3xl text-lighter-cream">
            {{ esc_html(FooterCustomizer::columnTwoTitle()) }}
          </h2>
          @if($hasFooterMenuUsefulLinks)
            <nav
              class="footer-nav footer-nav--col mt-6"
              aria-label="{{ esc_attr(FooterCustomizer::columnTwoTitle()) }}">
              {!! wp_nav_menu([
                  'theme_location' => 'footer_column_two',
                  'container' => false,
                  'menu_class' => 'footer-nav__list flex flex-col gap-3',
                  'fallback_cb' => false,
                  'depth' => 1,
                  'echo' => false,
              ]) !!}
            </nav>
          @elseif(current_user_can('edit_theme_options'))
            <p class="mt-6 font-sans text-base text-light-cream/55">
              {{ __('Assign a menu to Appearance → Menus → “Footer column 4 — Useful Links”.', 'culvers') }}
            </p>
          @endif
        </div>
      </div>

      {{-- Mobile: accordions → legal → wordmark (Figma Footer — Mobile/Default `2:1023`). --}}
      <div class="relative z-10 mt-10 flex flex-col gap-10 lg:hidden" x-data="footerMenuAccordion()">
        <div class="flex flex-col">
          <button
            type="button"
            class="flex w-full flex-col gap-0 border-0 bg-transparent p-0 text-left culvers-focus-ring"
            x-bind:aria-expanded="openWhatsHere ? 'true' : 'false'"
            aria-controls="footer-nav-whats-here-mobile"
            id="footer-acc-whats-here-trigger"
            x-on:click="toggleWhatsHere()">
            <span class="flex w-full items-start justify-between gap-4">
              <span class="font-heading text-3xl leading-[1.1] text-lighter-cream">
                {{ esc_html(FooterCustomizer::columnOneTitle()) }}
              </span>
              <span
                class="mt-0.5 inline-flex min-w-[1.75rem] shrink-0 select-none justify-end font-heading text-4xl font-light leading-none text-lighter-cream"
                x-text="openWhatsHere ? '\u2212' : '+'"
                aria-hidden="true"></span>
            </span>
            <span class="mt-5 block w-full border-b border-glowleaf" aria-hidden="true"></span>
          </button>
          @if($hasFooterMenuWhatsHere)
            <nav
              id="footer-nav-whats-here-mobile"
              class="footer-nav footer-nav--col mt-6 hidden"
              aria-label="{{ esc_attr(FooterCustomizer::columnOneTitle()) }}"
              x-bind:class="{ '!block': openWhatsHere }">
              {!! wp_nav_menu([
                  'theme_location' => 'footer_column_one',
                  'container' => false,
                  'menu_class' => 'footer-nav__list flex flex-col gap-3',
                  'fallback_cb' => false,
                  'depth' => 1,
                  'echo' => false,
              ]) !!}
            </nav>
          @elseif(current_user_can('edit_theme_options'))
            <p class="mt-6 hidden font-sans text-base text-light-cream/55" x-bind:class="{ '!block': openWhatsHere }">
              {{ __('Assign a menu to Appearance → Menus → “Footer column 3 — What’s Here”.', 'culvers') }}
            </p>
          @endif
        </div>

        <div class="flex flex-col">
          <button
            type="button"
            class="flex w-full flex-col gap-0 border-0 bg-transparent p-0 text-left culvers-focus-ring"
            x-bind:aria-expanded="openUsefulLinks ? 'true' : 'false'"
            aria-controls="footer-nav-useful-links-mobile"
            id="footer-acc-useful-links-trigger"
            x-on:click="toggleUsefulLinks()">
            <span class="flex w-full items-start justify-between gap-4">
              <span class="font-heading text-3xl leading-[1.1] text-lighter-cream">
                {{ esc_html(FooterCustomizer::columnTwoTitle()) }}
              </span>
              <span
                class="mt-0.5 inline-flex min-w-[1.75rem] shrink-0 select-none justify-end font-heading text-4xl font-light leading-none text-lighter-cream"
                x-text="openUsefulLinks ? '\u2212' : '+'"
                aria-hidden="true"></span>
            </span>
            <span class="mt-5 block w-full border-b border-glowleaf" aria-hidden="true"></span>
          </button>
          @if($hasFooterMenuUsefulLinks)
            <nav
              id="footer-nav-useful-links-mobile"
              class="footer-nav footer-nav--col mt-6 hidden"
              aria-label="{{ esc_attr(FooterCustomizer::columnTwoTitle()) }}"
              x-bind:class="{ '!block': openUsefulLinks }">
              {!! wp_nav_menu([
                  'theme_location' => 'footer_column_two',
                  'container' => false,
                  'menu_class' => 'footer-nav__list flex flex-col gap-3',
                  'fallback_cb' => false,
                  'depth' => 1,
                  'echo' => false,
              ]) !!}
            </nav>
          @elseif(current_user_can('edit_theme_options'))
            <p class="mt-6 hidden font-sans text-base text-light-cream/55" x-bind:class="{ '!block': openUsefulLinks }">
              {{ __('Assign a menu to Appearance → Menus → “Footer column 4 — Useful Links”.', 'culvers') }}
            </p>
          @endif
        </div>

        <div class="font-label text-xs font-normal uppercase leading-6 tracking-wide text-lighter-cream">
          <ul class="flex list-none flex-wrap gap-x-[18px] gap-y-2 p-0">
            <li class="list-none">
              <a class="footer-nav__link--legal" href="{{ esc_url(home_url('/cookie-policy/')) }}">{{ __('Cookie Policy', 'culvers') }}</a>
            </li>
            <li class="list-none">
              <a class="footer-nav__link--legal" href="{{ esc_url(home_url('/accessible-guide/')) }}">{{ __('Accessibility', 'culvers') }}</a>
            </li>
            <li class="list-none">
              <a class="footer-nav__link--legal" href="{{ esc_url(home_url('/privacy-policy/')) }}">{{ __('Privacy Policy', 'culvers') }}</a>
            </li>
          </ul>
          <div class="mt-6 flex flex-wrap items-center gap-x-[18px] gap-y-2">
            <a class="footer-nav__link--legal" href="{{ esc_url(home_url('/terms-and-conditions/')) }}">{{ __('Terms & Conditions', 'culvers') }}</a>
            <span>
              &copy;
              {{ esc_html(strtoupper((string) get_bloginfo('name'))) }}
              {{ wp_date('Y') }}
            </span>
          </div>
        </div>
      </div>

      {{-- Footer banner uses Figma footer wordmark file when present; otherwise Custom Logo or small inline mark (`partials.culver-square-logo`). --}}
      <div
        class="relative z-10 mt-10 w-full overflow-hidden opacity-70 md:mt-14 lg:mt-20 [&_svg]:mx-auto [&_svg]:block [&_svg]:h-auto [&_svg]:w-full [&_svg]:max-h-[min(30vw,220px)] md:[&_svg]:max-h-[min(26vw,240px)] lg:[&_svg]:max-h-[min(22vw,280px)] [&_svg]:max-w-none [&_svg]:object-contain [&_svg]:text-glowleaf [&_img]:mx-auto [&_img]:block [&_img]:h-auto [&_img]:max-h-[min(30vw,220px)] [&_img]:w-full [&_img]:max-w-none md:[&_img]:max-h-[min(26vw,240px)] lg:[&_img]:max-h-[min(22vw,280px)] [&_img]:object-contain">
        <a
          class="site-footer__logo flex w-full justify-center text-glowleaf"
          href="{{ esc_url(home_url('/')) }}"
          rel="home"
          aria-label="{{ esc_attr(get_bloginfo('name')) }}">
          @php
            $footerWordmarkAbs = get_template_directory() . '/resources/images/brand/culver-square-footer-wordmark.svg';
            $footerWordmarkSvg = '';
            if (is_readable($footerWordmarkAbs)) {
                $footerWordmarkRaw = (string) file_get_contents($footerWordmarkAbs);
                if ($footerWordmarkRaw !== '') {
                    $footerWordmarkSvg = (string) preg_replace(
                        '/<svg\b/',
                        '<svg class="block h-auto w-full max-w-none text-glowleaf" aria-hidden="true" focusable="false"',
                        $footerWordmarkRaw,
                        1,
                    );
                }
            }
          @endphp
          @if($footerWordmarkSvg !== '')
            {!! $footerWordmarkSvg !!}
          @elseif (has_custom_logo())
            {!! get_custom_logo() !!}
          @else
            @include('partials.culver-square-logo', [
                'class' => 'block w-full max-w-none text-glowleaf [&_svg]:block [&_svg]:h-auto [&_svg]:w-full [&_svg]:max-w-none',
            ])
          @endif
        </a>
      </div>

      {{-- Rule above legal row — glowleaf like Figma dividers. --}}
      {{-- Avoid overflow-x-auto here: per CSS overflow rules it forces overflow-y away from visible and can clip row text in flex layouts. Stack on small screens instead of horizontal scroll. --}}
      <div class="footer-under-logo relative z-20 mt-10 w-full border-t border-glowleaf md:mt-12">
        <div
          class="site-footer__legal-band grid w-full grid-cols-1 gap-y-4 py-6 text-center max-lg:place-items-center md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] md:items-center md:gap-x-6 md:gap-y-0 md:py-7">
          <p
            class="hidden font-label text-xs font-normal uppercase leading-[1.3] tracking-wider text-lighter-cream lg:block lg:justify-self-start lg:text-left">
            &copy;
            {{ esc_html(strtoupper((string) get_bloginfo('name'))) }}
            {{ wp_date('Y') }}
          </p>

          <nav class="hidden min-w-0 justify-self-center md:max-w-none lg:block" aria-label="{{ esc_attr__('Legal', 'culvers') }}">
            <ul class="{{ esc_attr($footerLegalListClass) }} list-none">
              <li class="list-none">
                <a class="footer-nav__link--legal" href="{{ esc_url(home_url('/cookie-policy/')) }}">{{ __('Cookie Policy', 'culvers') }}</a>
              </li>
              <li class="list-none">
                <a class="footer-nav__link--legal" href="{{ esc_url(home_url('/accessible-guide/')) }}">{{ __('Accessibility', 'culvers') }}</a>
              </li>
              <li class="list-none">
                <a class="footer-nav__link--legal" href="{{ esc_url(home_url('/privacy-policy/')) }}">{{ __('Privacy Policy', 'culvers') }}</a>
              </li>
              <li class="list-none">
                <a class="footer-nav__link--legal" href="{{ esc_url(home_url('/terms-and-conditions/')) }}">{{ __('Terms & Conditions', 'culvers') }}</a>
              </li>
            </ul>
          </nav>

          @php $credit = FooterCustomizer::siteCredit(); @endphp
          @if($credit !== '')
            <p
              class="font-label text-xs font-normal leading-[1.3] tracking-wider text-lighter-cream md:justify-self-end md:text-right">
              {{ esc_html($credit) }}
            </p>
          @elseif($societyLogoSvg !== '')
            <div
              class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-left font-label md:justify-self-end md:justify-end">
              {{-- Figma `51:5146`: Off White label + Society Studios wordmark (Layer_1). --}}
              <span
                class="text-xs font-normal uppercase leading-[1.3] tracking-wider text-purelinen">
                {{ __('Site by', 'culvers') }}
              </span>
              <span class="inline-flex shrink-0 items-center" aria-label="{{ esc_attr__('Society Studios', 'culvers') }}">
                {!! $societyLogoSvg !!}
              </span>
            </div>
          @else
            <span class="hidden min-h-[1em] md:block" aria-hidden="true"></span>
          @endif
        </div>
      </div>
    </div>
  </div>
  {{-- 10px glowleaf accent at the page bottom (Figma). Fixed height across breakpoints so the
       border looks identical on every page. Sole source of truth for the page-bottom edge. --}}
  <div class="site-footer__accent h-[10px] w-full shrink-0 bg-glowleaf" aria-hidden="true"></div>
</footer>
