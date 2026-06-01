@php
  ob_start();
  the_content();
  $pageBody = ob_get_clean();
  $pagination = wp_link_pages([
      'echo' => 0,
      'before' => '<p>' . esc_html__('Pages:', 'culvers'),
      'after' => '</p>',
  ]);
@endphp

<div class="mx-auto w-full max-w-3xl px-4 pb-16 prose prose-lg max-w-none text-deep-moss prose-headings:text-deep-moss prose-p:text-deep-moss prose-li:text-deep-moss prose-strong:text-deep-moss rt-link-prose lg:px-8 lg:pb-24">
  {!! $pageBody !!}
</div>

@if(! empty($pagination))
  <nav class="page-nav px-4" aria-label="{{ esc_attr__('Page', 'culvers') }}">
    {!! $pagination !!}
  </nav>
@endif
