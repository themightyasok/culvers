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
  $titleTag = Component::headingTagFromComponent($c, 'career_job_title_level', 1);
  $sectionTag = Component::headingTagFromComponent($c, 'career_section_heading_level', 2);

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
      <div
        class="career-detail__band grid items-start gap-10 md:gap-12 lg:grid-cols-[minmax(0,336px)_minmax(0,1fr)] lg:gap-x-16">
        {{-- Figma 51:6450: title (51:6464) top-aligns with sections (51:6472); meta below title (51:6465). --}}
        <aside class="career-detail__sidebar flex flex-col self-start text-deep-moss max-sm:items-center max-sm:text-center">
          @if($title !== '')
            <{{ $titleTag }} class="career-detail__title {{ Component::sectionHeadingClasses('text-deep-moss') }}">
              {{ esc_html($title) }}
            </{{ $titleTag }}>
          @endif

          @if($employerLogoUrl !== '')
            <div class="career-detail__employer-logo mb-6 flex max-w-[13rem] md:max-w-[15rem] max-sm:justify-center">
              @php
                  $employerLogoAlt = trim((string) ($employerLogo['alt'] ?? ''));
                  $employerLogoImgArgs = [
                      'class' => 'h-auto w-full max-h-14 object-contain object-left md:max-h-[4.25rem] max-sm:object-center',
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

          @if($meta !== [])
            <dl class="career-detail__meta mt-8 flex flex-col">
              @foreach($meta as $row)
                <div class="career-detail__meta-row flex flex-col gap-1 border-t border-deep-moss/20 py-5 first:border-t-0 first:pt-0 max-sm:items-center">
                  @if($row['label'] !== '')
                    <dt class="career-detail__meta-label font-sans text-xl font-medium leading-6 text-deep-moss max-sm:text-center">
                      {{ esc_html($row['label']) }}
                    </dt>
                  @endif
                  @if($row['value'] !== '')
                    <dd class="career-detail__meta-value m-0 font-sans text-xl font-light leading-6 text-deep-moss max-sm:text-center">
                      {{ esc_html($row['value']) }}
                    </dd>
                  @endif
                </div>
              @endforeach
            </dl>
          @endif

          @if($hasApply)
            <div class="career-detail__sidebar-cta @if($meta !== []) border-t border-deep-moss/20 @endif">
              <div class="pt-8 max-sm:flex max-sm:justify-center">
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
            </div>
          @endif
        </aside>

        @if($sections !== [])
          <div class="career-detail__sections flex flex-col gap-10 self-start md:gap-14">
            @foreach($sections as $section)
              <article class="career-detail__section">
                @if($section['heading'] !== '')
                  <{{ $sectionTag }} class="career-detail__section-heading {{ Component::sectionHeadingClasses('text-faded-olive', 'mb-4') }}">
                    {{ esc_html($section['heading']) }}
                  </{{ $sectionTag }}>
                @endif
                @if($section['body_plain'] !== '')
                  <div class="career-detail__section-body font-sans text-xl font-light leading-[1.3] text-deep-moss rt-link-faded [&_p+p]:mt-3 [&_strong]:font-medium [&_strong]:text-deep-moss [&_ol]:my-3 [&_ol]:list-decimal [&_ol]:pl-6 [&_ol_li+li]:mt-2 [&_ul]:m-0 [&_ul]:w-full [&_ul]:list-outside [&_ul]:list-disc [&_ul]:p-0 [&_ul]:text-left [&_ul>li]:ms-[1.875rem] [&_ul>li]:leading-[1.3] [&_ul>li]:marker:text-deep-moss [&_ul>li+li]:mt-0">
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
