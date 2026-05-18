@php
  use App\Helpers\Component;
  use App\Helpers\Image;
  use App\Helpers\LayoutShell;

  /**
   * Career detail — split job-header band:
   *   Left sidebar  → job title + meta rows + Apply CTA
   *   Right column  → stacked role sections (heading + WYSIWYG body)
   * Designed to sit between an image hero and the existing perks / apply CTA
   * components on a job page. Figma ref: 51:6450.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);

  $title = trim((string) ($c['career_job_title'] ?? ''));
  $titleTag = Component::headingTag($c['career_job_title_level'] ?? 1);
  $sectionTag = Component::headingTag($c['career_section_heading_level'] ?? 2);

  $applyLabel = trim((string) ($c['career_apply_label'] ?? ''));
  $applyUrl = trim((string) ($c['career_apply_url'] ?? ''));
  $hasApply = $applyLabel !== '' && $applyUrl !== '';

  $employerLogo = isset($c['career_sidebar_brand_logo']) && is_array($c['career_sidebar_brand_logo'])
      ? $c['career_sidebar_brand_logo']
      : [];
  $employerLogoUrl = isset($employerLogo['url']) ? trim((string) $employerLogo['url']) : '';

  $meta = [];
  if (isset($c['career_meta']) && is_array($c['career_meta'])) {
      foreach ($c['career_meta'] as $row) {
          if (! is_array($row)) {
              continue;
          }
          $label = trim((string) ($row['item_label'] ?? ''));
          $value = trim((string) ($row['item_value'] ?? ''));
          if ($label === '' && $value === '') {
              continue;
          }
          $meta[] = ['label' => $label, 'value' => $value];
      }
  }

  $sections = [];
  if (isset($c['career_sections']) && is_array($c['career_sections'])) {
      foreach ($c['career_sections'] as $row) {
          if (! is_array($row)) {
              continue;
          }
          $heading = trim((string) ($row['item_heading'] ?? ''));
          $bodyHtml = trim((string) ($row['item_body'] ?? ''));
          $bodyPlain = trim(wp_strip_all_tags($bodyHtml));
          if ($heading === '' && $bodyPlain === '') {
              continue;
          }
          $sections[] = [
              'heading' => $heading,
              'body_html' => $bodyHtml,
              'body_plain' => $bodyPlain,
          ];
      }
  }

  $hasContent = $title !== ''
      || ($employerLogoUrl !== '')
      || $meta !== []
      || $sections !== []
      || $hasApply;
@endphp

@if(! $hasContent)
  @if(current_user_can('edit_posts'))
    <section class="career-detail {{ esc_attr($root) }}" data-component-root data-career-detail>
      <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
        <div class="rounded border border-amber-400 bg-amber-50 px-4 py-3 text-sm text-amber-950">
          {{ __('Career detail — add a job title, meta rows or role sections to display this band.', 'culvers') }}
        </div>
      </div>
    </section>
  @endif
@else
  <section
    class="career-detail {{ esc_attr($root) }} text-deep-moss"
    data-component-root
    data-career-detail>
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      <div class="career-detail__band grid gap-10 md:gap-12 lg:grid-cols-[minmax(0,336px)_minmax(0,1fr)] lg:gap-16">
        {{-- Figma 51:8408 mobile: every sidebar element (logo, title, meta rows, Apply CTA)
             is centred on the column. Reverts to a left-aligned column at lg+ where the
             grid splits to a 2-up layout. --}}
        <aside class="career-detail__sidebar flex flex-col items-center text-center text-deep-moss lg:items-stretch lg:text-left">
          @if($employerLogoUrl !== '')
            <div class="career-detail__employer-logo mb-6 flex max-w-[13rem] justify-center lg:justify-start md:max-w-[15rem]">
              @php
                  $employerLogoAlt = trim((string) ($employerLogo['alt'] ?? ''));
                  /* Sidebar centring on mobile (Figma 51:8408) → logo centred in column too.
                     Reverts to `object-left` at lg+ where the sidebar is left-aligned. */
                  $employerLogoImgArgs = [
                      'class' => 'h-auto w-full max-h-14 object-contain object-center lg:object-left md:max-h-[4.25rem]',
                      'loading' => 'eager',
                      'decoding' => 'async',
                  ];
                  if ($employerLogoAlt !== '') {
                      $employerLogoImgArgs['alt'] = $employerLogoAlt;
                  } else {
                      $employerLogoImgArgs['alt'] = '';
                      $employerLogoImgArgs['role'] = 'presentation';
                  }
              @endphp
              {!! Image::render($employerLogo, $employerLogoImgArgs) !!}
            </div>
          @endif
          @if($title !== '')
            {{-- Figma 51:8409 mobile: Canela 46 / lh 1.1 / Faded Olive (H1 Mobile token).
                 `text-5xl` (48 / lh 1.1) is the closest stock token and matches the rest of the
                 site H1 ladder. Desktop bumps to `text-6xl` (58 / lh 1.15) per section H2 spec. --}}
            <{{ $titleTag }} class="career-detail__title font-heading text-5xl lg:text-6xl">
              {{ esc_html($title) }}
            </{{ $titleTag }}>
          @endif

          @if($meta !== [])
            {{-- Figma 51:8411 / 8413 / 8415: meta rows are centred on mobile with a hairline
                 divider between each row, label = Halyard Medium 20 / lh 24 (NOT uppercase),
                 value = Halyard Light 20 / lh 24. Reverts to left-align at lg+. --}}
            <dl class="career-detail__meta mt-8 flex w-full flex-col items-stretch">
              @foreach($meta as $row)
                <div class="career-detail__meta-row flex flex-col items-center gap-1 border-t border-deep-moss/20 py-5 first:border-t-0 first:pt-0 lg:items-start">
                  @if($row['label'] !== '')
                    <dt class="career-detail__meta-label font-sans text-xl font-medium leading-6 text-deep-moss">
                      {{ esc_html($row['label']) }}
                    </dt>
                  @endif
                  @if($row['value'] !== '')
                    <dd class="career-detail__meta-value m-0 font-sans text-xl font-light leading-6 text-deep-moss">
                      {{ esc_html($row['value']) }}
                    </dd>
                  @endif
                </div>
              @endforeach
            </dl>
          @endif

          @if($hasApply)
            {{-- Hand-rolled link (not the partial) because external apply URLs need an
                 inline `sr-only` span the partial doesn't model. Class spine matches
                 the partial — `btn btn-primary` — so hover stays consistent with every
                 other CTA on the site. --}}
            {{-- Apply CTA centred on mobile (Figma 51:8408), left-aligned at lg+ via parent. --}}
            <div class="career-detail__sidebar-cta mt-10 flex w-full justify-center lg:justify-start @if($meta !== []) border-t border-deep-moss/20 pt-8 @endif">
              <a
                class="btn btn-primary"
                href="{{ esc_url($applyUrl) }}"
                @if(str_starts_with($applyUrl, 'http')) target="_blank" rel="noopener noreferrer" @endif>
                {{ esc_html($applyLabel) }}
                @if(str_starts_with($applyUrl, 'http'))
                  <span class="sr-only">{{ __('(opens in new tab)', 'culvers') }}</span>
                @endif
              </a>
            </div>
          @endif
        </aside>

        @if($sections !== [])
          <div class="career-detail__sections flex flex-col gap-10 md:gap-12">
            @foreach($sections as $section)
              <article class="career-detail__section">
                @if($section['heading'] !== '')
                  <{{ $sectionTag }} class="career-detail__section-heading font-heading text-2xl leading-tight md:text-3xl">
                    {{ esc_html($section['heading']) }}
                  </{{ $sectionTag }}>
                @endif
                @if($section['body_plain'] !== '')
                  <div class="career-detail__section-body mt-4 font-sans text-base font-light leading-7 text-deep-moss/85 md:text-lg rt-link-faded [&_p+p]:mt-3 [&_strong]:font-medium [&_strong]:text-deep-moss [&_ul]:my-3 [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:marker:text-faded-olive [&_ul_li+li]:mt-2 [&_ol]:my-3 [&_ol]:list-decimal [&_ol]:pl-6 [&_ol_li+li]:mt-2">
                    {!! $section['body_html'] !!}
                  </div>
                @endif
              </article>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  </section>
@endif
