# Contact (`contact`)

Two-column band: left "Getting here / Contact Us" panel sourced from
`FooterCustomizer` (single source of truth for address, phone, email,
social URLs); right contact form (first / last / email / reason /
message) posting to `wp-json/culvers/v1/contact-form`. Optional Maps
Embed below the band.

| | |
| --- | --- |
| Layout key | `contact` |
| ACF schema | [`app/Components/contact.php`](../../app/Components/contact.php) |
| Blade view | [`resources/views/components/contact.blade.php`](../../resources/views/components/contact.blade.php) |
| CSS partial | _(none)_ |
| Alpine module | [`resources/scripts/alpine/contact.js`](../../resources/scripts/alpine/contact.js) |
| BEM root | `.contact` |
| Figma reference | `51:9378` |
| External | REST endpoint, `wp_mail`, Customizer, Google Maps Embed |

## When to use

The site contact page. Editors get a fully-themed form with no extra
plugin needed. The "Getting here / Contact Us" panel is intentionally
sourced from the footer customizer so the contact panel and the site
footer always agree — no double-maintenance of the address, phone,
email, etc.

## Editor fields

### Layout

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `contact_heading` | text | — | Optional. Renders above the band when set. |
| `contact_heading_level` | select | `h2` | |
| `contact_show_panel` | true_false | on | Toggle the left "Getting here" panel. |
| `contact_show_map` | true_false | on | Toggle the Google Maps Embed below the band. |

### Form labels & placeholders

Each form input has a paired label / placeholder text field, all
translatable, all defaulting to sensible English copy. See
[`app/Components/contact.php`](../../app/Components/contact.php) for
the full list (`contact_form_first_name_*` through
`contact_form_submit_label`).

### Other

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `contact_form_success_message` | text | `Thanks — your message is on its way.` | Shown next to the submit button after success. |
| `contact_form_reasons` | repeater (0–12, `table` layout) | — | When empty, "reason for enquiry" renders as a free-text input; otherwise as a select. |
| `contact_after_submit` | wysiwyg | — | Optional copy rendered under the submit button (e.g. Lost Property / Management Office hours). |

## Wiring (one-time setup)

### 1. Where the contact details come from

All visible centre-info on the panel (address, "view on map" link,
phone, email, social URLs) is sourced from
**Appearance → Customize → Culver Square footer**:

| Customizer setting               | Used by              |
| -------------------------------- | -------------------- |
| Footer — address                 | Footer + Contact     |
| Footer — map link URL / label    | Footer + Contact     |
| Footer — phone                   | Footer + Contact     |
| Footer — email                   | Footer + Contact     |
| Social — Instagram URL           | Footer + Contact     |
| Social — Facebook URL            | Footer + Contact     |

### 2. Where submissions go

Set **Contact form — recipient email** in
Appearance → Customize → Culver Square footer.
If left blank, submissions fall back to the WordPress **admin email**
(Settings → General → Administration Email Address). The form silently
503s for the public if neither is set, and shows a "form not
configured" toast for editors.

The recipient is intentionally a **Customizer-only** setting (not ACF)
so non-admin editors can't redirect form submissions to themselves.

### 3. Map (optional)

The map iframe uses the **Maps Embed API** with the same key +
destination configured at Appearance → Customize → Google Maps
(see [TRAVEL-CALCULATOR.md](TRAVEL-CALCULATOR.md) for the API key +
Place ID setup). Place ID is preferred when set; otherwise it falls
back to the configured destination address.

## Anti-abuse

The endpoint stacks four protections so spam doesn't reach `wp_mail`:

1. **Nonce** — every form submit sends `X-WP-Nonce: wp_rest`. Stale
   nonces return `403`.
2. **Honeypot** — a visually-hidden `website` field. If filled, the
   request is silently rejected with `422` (bots fill every input).
3. **Per-IP rate limit** — 5 submissions per IP per hour
   (transient-backed). Over the limit returns `429`.
4. **Server-side validation** — required fields, valid email, length
   caps (first/last/email/reason ≤ 100/100/200/100, message ≤ 5000).

## Email composition

Subject:

```
[<site name>] <reason or "New enquiry"> from <First> <Last>
```

Body (plain text):

```
From: <First> <Last> <email>
Reason: <reason if set>

Message:
<message body>

— Sent via <home_url>
```

`Reply-To` is set to the submitter so you can reply directly from the
inbox.

## Behaviour notes

- After a successful submission the form **stays usable** — the
  status banner clears the moment the user types or changes a field
  (`onFieldInput()` in the Alpine module).
- Form inputs use the standard `.btn`/focus-visible pattern.

## Local testing

```bash
# From app/public:
./wp-content/themes/culvers/scripts/with-local-env.sh wp option get admin_email
./wp-content/themes/culvers/scripts/with-local-env.sh wp eval \
  'echo App\\Customizer\\FooterCustomizer::contactFormRecipient();'
```

Smoke-test the endpoint (replace the nonce):

```bash
NONCE=$(./wp-content/themes/culvers/scripts/with-local-env.sh \
  wp eval 'echo wp_create_nonce("wp_rest");')
curl -X POST 'https://culvers.local/wp-json/culvers/v1/contact-form' \
  -H "Content-Type: application/json" -H "X-WP-Nonce: $NONCE" \
  -d '{"first_name":"Test","last_name":"User","email":"test@example.com","reason":"Lost & found","message":"Hello"}'
```

Local intercepts mail by default — open Local → MailHog to inspect the
message.

## Related components

- [`travel_calculator`](TRAVEL-CALCULATOR.md) — uses the same Google
  Maps Customizer configuration.
- [`section_header`](SECTION-HEADER.md) — sits above `contact` on the
  Contact page intro.
