@php
  use App\Customizer\FooterCustomizer;

  $newsletterImgId = (int) get_theme_mod(FooterCustomizer::MOD_NEWSLETTER_IMAGE_ID, 0);
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

  /* Assigned location but empty menu yields `<ul></ul>` — center column vanished in flex; grid + fallback fixes it. */
  /* Figma legal-band typography: Commuter Sans Bold 10px / lh 13 / uppercase,
     0.5px tracking (≈ `tracking-[0.05em]`). Halyard `text-xs` was the legacy
     value and renders 1px larger with looser leading; switching to `font-label`
     keeps it crisp without bumping site-wide tracking utilities. */
  $footerLegalNavHtml = '';
  if (has_nav_menu('footer_brand_subnav')) {
      $footerLegalNavHtml = (string) wp_nav_menu([
          'theme_location' => 'footer_brand_subnav',
          'container' => false,
          'menu_class' =>
              'footer-nav__list footer-nav__list--legal flex flex-wrap items-center justify-center gap-x-4 gap-y-2 whitespace-normal font-label text-[10px] font-bold uppercase leading-[1.3] tracking-[0.05em] text-lighter-cream md:gap-x-8 [&>li]:flex [&>li]:shrink-0 [&>li]:items-center [&>li>a]:inline-flex [&>li>a]:min-h-[2rem] [&>li>a]:items-center [&>li>a]:px-3 [&>li>a]:py-1 md:[&>li>a]:px-5',
          'fallback_cb' => false,
          'depth' => 1,
          'echo' => false,
      ]);
  }
  $footerLegalNavHasItems = str_contains($footerLegalNavHtml, '<li');

  $footerLegalFallbackUlClass =
      'footer-nav__list footer-nav__list--legal flex flex-wrap items-center justify-center gap-x-4 gap-y-2 whitespace-normal font-label text-[10px] font-bold uppercase leading-[1.3] tracking-[0.05em] text-lighter-cream md:gap-x-8 [&>li]:flex [&>li]:shrink-0 [&>li]:items-center [&>li>a]:inline-flex [&>li>a]:min-h-[2rem] [&>li>a]:items-center [&>li>a]:px-3 [&>li>a]:py-1 md:[&>li>a]:px-5';
@endphp

{{--
  Site footer — Culver Square

  Naming:
    site-footer          Outer landmark (`site-footer__*` for major bands).
    footer-nav__link*    Column/legal/social/phone link styles (`addComponents` in tailwind.config.js).
    footer-nav           WP menu wrappers (`footer-nav__list`, modifiers).
    footer-link--*       Shared link treatments (e.g. persistent underline).

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

          {{-- Mobile: headline above field. Desktop: right column — headline centred above pill field (Figma). --}}
          <div class="relative z-10 grid grid-cols-1 gap-8 px-6 py-11 md:grid-cols-2 md:items-center md:gap-10 md:px-12 md:py-14 lg:gap-14 lg:px-14 lg:py-16">
            <div class="hidden min-h-[80px] md:block" aria-hidden="true"></div>
            <div class="flex max-w-full flex-col gap-6 md:max-w-[26rem] md:justify-self-end lg:max-w-[30rem]">
              <div class="flex flex-col gap-4 text-center">
                @php $headingParts = FooterCustomizer::newsletterHeadingParts(); @endphp
                {{-- Two-tone headline (Figma): accent words in glowleaf, remainder in white. The
                     split is data-driven via Customizer (`MOD_NEWSLETTER_HEADING_ACCENT`) so editors
                     can re-pick which words pop without touching markup. --}}
                {{-- Figma `2:1113` mobile = 36 px, Figma desktop newsletter heading = ~34 (snapped
                     to text-4xl = 40 in the type ramp). Mobile bumped from text-3xl (32) → text-[36px]. --}}
                <h2
                  id="footer-newsletter-heading"
                  class="font-heading text-[36px] leading-[1.1] md:text-4xl">
                  @if($headingParts['accent'] !== '')
                    <span class="text-glowleaf">{{ esc_html($headingParts['accent']) }}</span><span class="text-lighter-cream">{{ esc_html($headingParts['rest']) }}</span>
                  @else
                    <span class="text-lighter-cream">{{ esc_html($headingParts['rest']) }}</span>
                  @endif
                </h2>
                @php $newsBody = FooterCustomizer::newsletterBody(); @endphp
                @if($newsBody !== '')
                  <p class="font-sans text-lg leading-relaxed text-light-cream/88">
                    {{ esc_html($newsBody) }}
                  </p>
                @endif
              </div>

              <form
                class="footer-newsletter-form pointer-events-auto w-full md:max-w-none"
                method="post"
                action="{{ esc_url($newsletterAction ?? '#') }}"
                @if($newsletterAction === null) onsubmit="event.preventDefault(); return false;" @endif>
                <label class="sr-only" for="footer-newsletter-email">{{ __('Email address', 'culvers') }}</label>
                {{-- Sheet feedback row 11: reduce size of the email pill (Figma 2:1118 is 46 px tall).
                     Tightened padding to py-1 + input min-h-[38px] + size-9 button → ~46 px overall. --}}
                <div
                  class="flex items-center gap-2 rounded-full border-[1.5px] border-glowleaf bg-transparent px-4 py-1 md:px-5">
                  <input
                    id="footer-newsletter-email"
                    name="EMAIL"
                    type="email"
                    autocomplete="email"
                    placeholder="{{ esc_attr(FooterCustomizer::newsletterPlaceholder()) }}"
                    {{-- Figma `2:1118` placeholder: Commuters SemiBold 12.887 / lh 30.928 / 0.6443 px tracking / uppercase. --}}
                    class="min-h-[38px] flex-1 border-0 bg-transparent font-label text-[13px] font-semibold uppercase leading-[30px] tracking-[0.05em] text-lighter-cream placeholder:text-lighter-cream/75 placeholder:uppercase focus:ring-0 focus:outline-none md:text-sm" />
                  <button
                    type="submit"
                    class="inline-flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-full text-lighter-cream transition-colors hover:bg-white/10 culvers-focus-ring"
                    aria-label="{{ esc_attr__('Subscribe', 'culvers') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path
                        d="M5 12h14m-6-6 6 6-6 6"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round" />
                    </svg>
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
      <div class="relative z-10 grid grid-cols-1 gap-12 pt-20 sm:grid-cols-2 sm:pt-24 lg:grid-cols-4 lg:gap-10 lg:pt-16 xl:gap-14">
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
            {{-- Figma: Commuter Sans Bold 14px / lh 1.3 / 1px tracking. --}}
            <a
              class="footer-link--persistent-underline mt-6 inline-flex items-center gap-2 font-label text-sm font-bold uppercase leading-[1.3] tracking-[0.07em] text-glowleaf transition-colors hover:text-lighter-cream"
              href="{{ esc_url($mapUrl) }}"
              @if(str_starts_with($mapUrl, 'http')) target="_blank" rel="noopener noreferrer" @endif>
              {{ esc_html(FooterCustomizer::gettingHereMapLabel()) }}
              @if(str_starts_with($mapUrl, 'http'))
                <span class="sr-only">{{ __('(opens in new tab)', 'culvers') }}</span>
              @endif
              <span aria-hidden="true">›</span>
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
              {{-- Sheet feedback row 9: footer socials need to be visible (URLs now seeded via
                   Customizer mods) + slightly bigger icons (16 → 22). --}}
              @if($instagramUrl !== '' && $instagramUrl !== '#')
                <a
                  class="footer-nav__link-social"
                  href="{{ esc_url($instagramUrl) }}"
                  target="_blank"
                  rel="noopener noreferrer">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="5" stroke="currentColor" stroke-width="1.4" />
                    <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.4" />
                    <circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" />
                  </svg>
                  {{ __('Instagram', 'culvers') }}
                </a>
              @endif
              @if($facebookUrl !== '' && $facebookUrl !== '#')
                <a
                  class="footer-nav__link-social"
                  href="{{ esc_url($facebookUrl) }}"
                  target="_blank"
                  rel="noopener noreferrer">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path
                      d="M14 8h3V5h-3c-2.2 0-4 1.8-4 4v2H7v3h3v8h3v-8h3.2l.8-3H13v-2c0-.6.4-1 1-1Z" />
                  </svg>
                  {{ __('Facebook', 'culvers') }}
                </a>
              @endif
            </div>
          @endif
        </div>

        <div class="contents lg:contents" x-data="footerMenuAccordion()">
          <div class="flex flex-col border-b border-light-cream/25 pb-8 lg:border-0 lg:pb-0">
            <h2 class="hidden font-heading text-3xl text-lighter-cream lg:block">
              {{ esc_html(FooterCustomizer::columnOneTitle()) }}
            </h2>
            <button
              type="button"
              class="flex w-full items-start justify-between gap-4 border-0 bg-transparent p-0 text-left lg:hidden culvers-focus-ring"
              x-bind:aria-expanded="isDesktop || openWhatsHere ? 'true' : 'false'"
              aria-controls="footer-nav-whats-here"
              id="footer-acc-whats-here-trigger"
              x-on:click="toggleWhatsHere()">
              <span class="font-heading text-3xl leading-snug text-lighter-cream">
                {{ esc_html(FooterCustomizer::columnOneTitle()) }}
              </span>
              <span class="mt-0.5 inline-flex min-w-[1.75rem] shrink-0 select-none justify-end font-heading text-4xl font-light leading-none tracking-normal text-lighter-cream lg:hidden" x-text="openWhatsHere ? '\u2212' : '+'" aria-hidden="true"></span>
            </button>
            @if($hasFooterMenuWhatsHere)
              <nav
                id="footer-nav-whats-here"
                class="footer-nav footer-nav--col mt-6 hidden lg:block"
                aria-label="{{ esc_attr(FooterCustomizer::columnOneTitle()) }}"
                x-bind:class="{ '!block': openWhatsHere && !isDesktop }">
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
              <p
                class="mt-6 hidden font-sans text-base text-light-cream/55 lg:block"
                x-bind:class="{ '!block': openWhatsHere && !isDesktop }">
                {{ __('Assign a menu to Appearance → Menus → “Footer column 3 — What’s Here”.', 'culvers') }}
              </p>
            @endif
          </div>

          <div class="flex flex-col border-b border-light-cream/25 pb-8 lg:border-0 lg:pb-0">
            <h2 class="hidden font-heading text-3xl text-lighter-cream lg:block">
              {{ esc_html(FooterCustomizer::columnTwoTitle()) }}
            </h2>
            <button
              type="button"
              class="flex w-full items-start justify-between gap-4 border-0 bg-transparent p-0 text-left lg:hidden culvers-focus-ring"
              x-bind:aria-expanded="isDesktop || openUsefulLinks ? 'true' : 'false'"
              aria-controls="footer-nav-useful-links"
              id="footer-acc-useful-links-trigger"
              x-on:click="toggleUsefulLinks()">
              <span class="font-heading text-3xl leading-snug text-lighter-cream">
                {{ esc_html(FooterCustomizer::columnTwoTitle()) }}
              </span>
              <span class="mt-0.5 inline-flex min-w-[1.75rem] shrink-0 select-none justify-end font-heading text-4xl font-light leading-none tracking-normal text-lighter-cream lg:hidden" x-text="openUsefulLinks ? '\u2212' : '+'" aria-hidden="true"></span>
            </button>
            @if($hasFooterMenuUsefulLinks)
              <nav
                id="footer-nav-useful-links"
                class="footer-nav footer-nav--col mt-6 hidden lg:block"
                aria-label="{{ esc_attr(FooterCustomizer::columnTwoTitle()) }}"
                x-bind:class="{ '!block': openUsefulLinks && !isDesktop }">
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
              <p
                class="mt-6 hidden font-sans text-base text-light-cream/55 lg:block"
                x-bind:class="{ '!block': openUsefulLinks && !isDesktop }">
                {{ __('Assign a menu to Appearance → Menus → “Footer column 4 — Useful Links”.', 'culvers') }}
              </p>
            @endif
          </div>
        </div>
      </div>

      {{-- Wordmark — clip SVG/logo bleed so nothing paints over the legal band below (desktop outline marks can extend past the box). --}}
      {{-- Sheet feedback row 9: footer wordmark should read lighter — apply 70 % opacity so the
           filled Glowleaf wordmark reads as a thinner accent rather than a heavy banner. --}}
      <div
        class="relative z-10 mt-14 w-full overflow-hidden opacity-70 md:mt-16 lg:mt-20 [&_img]:mx-auto [&_img]:block [&_img]:h-auto [&_img]:w-full [&_img]:max-h-[min(30vw,220px)] [&_img]:max-w-none [&_img]:object-contain [&_svg]:block [&_svg]:h-auto [&_svg]:w-full [&_svg]:max-h-[min(30vw,220px)] [&_svg]:max-w-none">
        <a
          class="site-footer__logo flex w-full justify-center text-glowleaf"
          href="{{ esc_url(home_url('/')) }}"
          rel="home"
          aria-label="{{ esc_attr(get_bloginfo('name')) }}">
          @if(has_custom_logo())
            <span class="block w-full [&_img]:mx-auto [&_img]:block [&_img]:w-full [&_img]:max-w-none [&_img]:object-contain">
              {!! get_custom_logo() !!}
            </span>
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
          class="site-footer__legal-band grid w-full grid-cols-1 gap-y-4 py-6 text-center md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] md:items-center md:gap-x-6 md:gap-y-0 md:py-7">
          <p
            class="font-label text-[10px] font-bold uppercase leading-[1.3] tracking-[0.05em] text-lighter-cream md:justify-self-start md:text-left">
            &copy;
            {{ esc_html(strtoupper((string) get_bloginfo('name'))) }}
            {{ wp_date('Y') }}
          </p>

          <nav class="min-w-0 justify-self-center md:max-w-none" aria-label="{{ esc_attr__('Legal', 'culvers') }}">
            @if($footerLegalNavHasItems)
              {!! $footerLegalNavHtml !!}
            @else
              {{-- Site has no `footer_brand_subnav` menu yet — link directly to the seeded policy pages
                   so live legal links never depend on the optional WP menu being assigned. --}}
              <ul class="{{ esc_attr($footerLegalFallbackUlClass) }} list-none">
                <li class="list-none">
                  <a class="footer-nav__link--legal" href="{{ esc_url(home_url('/cookie-policy/')) }}">{{ __('Cookie Policy', 'culvers') }}</a>
                </li>
                <li class="list-none">
                  <a class="footer-nav__link--legal" href="{{ esc_url(home_url('/privacy-policy/')) }}">{{ __('Privacy Policy', 'culvers') }}</a>
                </li>
                <li class="list-none">
                  <a class="footer-nav__link--legal" href="{{ esc_url(home_url('/terms-and-conditions/')) }}">{{ __('Terms & Conditions', 'culvers') }}</a>
                </li>
              </ul>
            @endif
          </nav>

          @php $credit = FooterCustomizer::siteCredit(); @endphp
          @if($credit !== '')
            <p
              class="font-label text-[10px] font-bold uppercase leading-[1.3] tracking-[0.05em] text-lighter-cream md:justify-self-end md:text-right">
              {{ esc_html(strtoupper($credit)) }}
            </p>
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
