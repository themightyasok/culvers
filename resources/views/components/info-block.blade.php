@php
  use App\Helpers\Component;
  use App\Helpers\Image;
  use App\Helpers\LayoutShell;

  /**
   * Info block — intro stack (heading, subheading, body, optional CTA) +
   * up-to-16 square cells in a 4-column grid (1-col on mobile).
   * Sits flush on the page white — no coloured band, no decorative motif.
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);
  $tone = Component::bodyTextTone($c, 'light-band');
  $headingTag = Component::headingTag($c['info_heading_level'] ?? null);

  $heading = trim((string) ($c['info_heading'] ?? ''));
  $subheading = trim((string) ($c['info_subheading'] ?? ''));
  $body = (string) ($c['info_body'] ?? '');
  $ctaLabel = trim((string) ($c['info_cta_label'] ?? ''));
  $ctaUrl = trim((string) ($c['info_cta_url'] ?? ''));

  $rowsRaw = $c['info_items'] ?? [];
  $rows = is_array($rowsRaw) ? $rowsRaw : [];

  $cells = [];
  foreach ($rows as $row) {
      if (! is_array($row)) {
          continue;
      }
      $title = trim((string) ($row['item_heading'] ?? ''));
      $desc = trim((string) ($row['item_description'] ?? ''));
      $img = isset($row['item_image']) && is_array($row['item_image']) ? $row['item_image'] : null;
      $imgUrl = isset($img['url']) ? trim((string) $img['url']) : '';
      if ($title === '') {
          continue;
      }
      $cells[] = [
          'title' => $title,
          'description' => $desc,
          'image' => $imgUrl !== '' ? $img : null,
      ];
  }

  $hasIntro =
      $heading !== ''
      || $subheading !== ''
      || trim(strip_tags($body)) !== ''
      || ($ctaLabel !== '' && $ctaUrl !== '');
  $hasGrid = $cells !== [];
@endphp

@if($hasIntro || $hasGrid)
  <section
    class="info-block {{ esc_attr($root) }} relative bg-white py-12 text-deep-moss lg:py-16"
    data-component-root
    data-info-block>
    <div class="relative z-10 {{ LayoutShell::INNER_MAX_GUTTERED }}">
      @if($hasIntro)
        <header class="mx-auto max-w-[52rem] text-center">
          @if($heading !== '')
            {{-- Section H2: 64px desktop / 48px mobile (Component::sectionHeadingClasses). --}}
            <{{ $headingTag }} class="{{ Component::sectionHeadingClasses('text-faded-olive') }}">
              {{ esc_html($heading) }}
            </{{ $headingTag }}>
          @endif
          @if($subheading !== '')
            <p class="mt-4 font-sans text-xl font-light text-deep-moss md:text-2xl">
              {!! nl2br(e($subheading)) !!}
            </p>
          @endif
          @if(trim(strip_tags($body)) !== '')
            <div
              class="info-block__body prose prose-lg mx-auto mt-6 max-w-[36.75rem] text-left font-light md:text-center text-deep-moss prose-headings:text-deep-moss prose-p:font-sans prose-p:text-xl prose-p:font-light prose-li:text-deep-moss prose-strong:text-deep-moss [&_a]:text-deep-moss [&_a]:underline [&_a]:decoration-glowleaf [&_a]:underline-offset-4 hover:[&_a]:decoration-deep-moss {{ esc_attr($tone) }}">
              {!! $body !!}
            </div>
          @endif
          @if($ctaLabel !== '' && $ctaUrl !== '')
            <div class="mt-8 flex justify-center md:mt-10">
              {{-- Banner-scale CTA: `size=large` keeps the canonical Figma hover-widen
                   (40px → 56px on hover) instead of being killed by an inline `px-*`. --}}
              @include('components.button', [
                  'label' => $ctaLabel,
                  'href' => $ctaUrl,
                  'size' => 'large',
              ])
            </div>
          @elseif($ctaLabel !== '' && $ctaUrl === '' && current_user_can('edit_posts'))
            <p class="mt-6 font-sans text-base text-deep-moss/60">
              {{ __('Add a CTA URL to show the button.', 'culvers') }}
            </p>
          @endif
        </header>
      @endif

      @if($hasGrid)
        <div
          class="{{ $hasIntro ? 'mt-12 md:mt-16' : '' }} mx-auto grid w-full max-w-7xl grid-cols-1 gap-px bg-deep-moss/15 md:grid-cols-2 lg:grid-cols-4">
          @foreach($cells as $cell)
            <article
              class="flex aspect-square flex-col items-center justify-center gap-4 bg-white px-5 py-8 text-center sm:px-8 sm:py-10 lg:px-10">
              @if($cell['image'] !== null)
                <div class="flex h-[7rem] w-full shrink-0 items-center justify-center sm:h-[8rem]">
                  {!! Image::render($cell['image'], [
                      'class' => 'max-h-full max-w-[min(100%,12rem)] object-contain text-deep-moss sm:max-w-[min(100%,13rem)]',
                  ]) !!}
                </div>
              @endif
              {{-- Figma cells: Canela ~42px → text-3xl; label Commuters 12px / lh 24 / tracking 1px. --}}
              <h3 class="font-heading text-4xl text-faded-olive">
                {{ esc_html($cell['title']) }}
              </h3>
              @if($cell['description'] !== '')
                <p
                  class="mt-2 max-w-[min(100%,18rem)] font-sans text-xs font-semibold uppercase leading-6 tracking-widest text-faded-olive md:max-w-[min(100%,22rem)]">
                  {!! nl2br(e($cell['description'])) !!}
                </p>
              @endif
            </article>
          @endforeach
        </div>
      @endif
    </div>
  </section>
@elseif(current_user_can('edit_posts'))
  @include('partials.component-editor-placeholder', [
      'wrapperClasses' => $root,
      'message' => __('Add heading or info cells to this block.', 'culvers'),
  ])
@endif
