{{--
  Text-only hero backdrop — Figma policy / legal headers (`51:6558` → `51:6560`–`51:6561`):
    • Faded Olive base + 50% black scrim
    • ~932px white square at 20% opacity, mix-blend overlay, rotated −45° (upper-left)
  Styles: `.text-hero-viewport__backdrop*` in resources/styles/app.css
--}}
<div class="text-hero-viewport__backdrop" aria-hidden="true">
  <div class="text-hero-viewport__backdrop-base"></div>
  <div class="text-hero-viewport__backdrop-scrim"></div>
  <div class="text-hero-viewport__backdrop-diamond">
    <div class="text-hero-viewport__backdrop-diamond-inner"></div>
  </div>
</div>
