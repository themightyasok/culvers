# Travel Calculator (`travel_calculator`)

Faded-olive band: Canela title + Halyard Display subtitle, 3-up
destination / mode / search controls, inline result strip, and a
route-preview map below. Backed by the Distance Matrix endpoint
`wp-json/culvers/v1/travel-calculator` and the Maps Embed API.

| | |
| --- | --- |
| Layout key | `travel_calculator` |
| ACF schema | [`app/Components/travel_calculator.php`](../../app/Components/travel_calculator.php) |
| Blade view | [`resources/views/components/travel-calculator.blade.php`](../../resources/views/components/travel-calculator.blade.php) |
| CSS partial | _(none)_ |
| Alpine module | [`resources/scripts/alpine/travel-calculator.js`](../../resources/scripts/alpine/travel-calculator.js) |
| BEM root | `.travel-calculator` |
| Figma reference | `51:7970` (band) + `51:5952` (map context) |
| External | Google Distance Matrix API + Maps Embed API |

## When to use

The "How long does it take to get to Culver Square?" widget on Plan
My Visit / Contact / Travel pages. The Google API key never leaves
the server for Distance Matrix — only the Maps Embed iframe sees it
(and it's HTTP-referrer-restricted, see Setup).

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `tc_heading` | text | `Travel Calculator` | |
| `tc_intro` | textarea | `Find out how close Culver is to your work…` | |
| `tc_destination_label` | text | `Your destination` | Label on the destination input. |
| `tc_destination_placeholder` | text | `Type your destination here` | |
| `tc_mode_label` | text | `Travel by` | |
| `tc_mode_placeholder` | text | `Select` | |
| `tc_modes` | repeater (1–4, `table` layout, required) | — | Which travel modes appear in the dropdown. |
| `tc_button_label` | text | `Search` | |
| `tc_show_map` | true_false | on | Render the Google Maps Embed below the form. |
| `tc_map_initial_image` | image | — | Optional static image shown before the user runs a search (or as a fallback if the API key is missing). |

### Mode sub-fields

| Sub-field | Type | Notes |
| --- | --- | --- |
| `item_mode` | select (`driving`, `transit`, `walking`, `bicycling`) | API mode value. |
| `item_label` | text | Display label shown to users. |

## Setup — Google Cloud (one-time)

### 1. Project + billing

1. Open [Google Cloud Console](https://console.cloud.google.com/).
2. Create a new project (or pick an existing one): **Culver Square**.
3. **Billing** must be enabled — Distance Matrix is paid-tier
   (the first $200/month of Maps Platform usage is free; typical
   lookup is a fraction of a cent).
4. **APIs & Services → Library** — enable both:
   - **Distance Matrix API**
   - **Maps Embed API** (free, required for the iframe)

### 2. Create + restrict the API key

1. **APIs & Services → Credentials → + Create credentials → API key**.
2. Copy the key.
3. Click the new key to edit restrictions:
   - **Application restrictions → HTTP referrers (websites)** — add
     the live host (`https://culver-square.com/*`), staging host,
     and Local dev host (`https://culvers.local/*`).
   - **API restrictions → Restrict key** — pick **Distance Matrix
     API** and **Maps Embed API**. Nothing else.
4. Save.

> The HTTP-referrer restriction stops anyone scraping the key out of
> the iframe URL and using it elsewhere.

### 3. Get the destination Place ID (recommended)

1. Open the
   [Place ID Finder](https://developers.google.com/maps/documentation/places/web-service/place-id#find-id).
2. Search **Culver Square Colchester**.
3. Copy the `ChIJ…` Place ID.

### 4. Configure WordPress

Appearance → Customize → **Google Maps**:

| Setting                          | Value |
| -------------------------------- | ----- |
| **Google Maps API key**          | _paste from step 2_ |
| **Destination address**          | `Culver Square, Colchester CO1 1JG, United Kingdom` |
| **Destination Google Place ID**  | _paste from step 3 (optional but preferred)_ |
| **Destination short label**      | `Culver Square` |

Publish. The Travel Calculator on any page now resolves real
distances and durations.

## Adding the component to a page

1. Edit a page → **Page Components**.
2. Add component → **Travel Calculator**.
3. Configure heading, intro, available modes, and button label in
   the General tab.
4. Optionally upload a static **Map placeholder image**.
5. Save / publish.

## Cost & rate-limit safeguards

| Layer | Limit |
| --- | --- |
| Server-side response cache | 24 hours per `(origin, destination, mode)` tuple |
| Per-IP rate limit | 10 requests / 60 seconds |
| Origin length cap | 200 chars (longer is rejected before any API call) |
| Mode allowlist | `driving` / `transit` / `walking` / `bicycling` only |
| Nonce | `wp_rest` — required on every POST |

A cache hit is free. A cold lookup is one Distance Matrix call
(~$0.005 at the time of writing).

## REST contract

`POST /wp-json/culvers/v1/travel-calculator`

```http
POST /wp-json/culvers/v1/travel-calculator
Content-Type: application/json
X-WP-Nonce: <wp_create_nonce('wp_rest')>

{ "origin": "Liverpool Street, London", "mode": "transit" }
```

Success (200):

```json
{
  "mode": "transit",
  "distance": { "text": "62.4 mi", "value": 100387 },
  "duration": { "text": "1 hour 38 mins", "value": 5880 },
  "origin": "Liverpool Street, London EC2M 7PD, UK",
  "destination": {
    "address": "Culver Square, Colchester CO1 1JG, UK",
    "label": "Culver Square"
  },
  "message": "Your journey by public transport is 62.4 mi and it will take approximately 1 hour 38 mins."
}
```

Error (`WP_Error` JSON):

```json
{
  "code": "culvers_travel_failed",
  "message": "We couldn't find that address — please be more specific.",
  "data": { "status": 422 }
}
```

Status codes:

| HTTP | Meaning |
| --- | --- |
| 200 | Route resolved |
| 400 | Invalid origin / mode / origin too long |
| 403 | Missing or stale nonce |
| 422 | Google rejected the request (`NOT_FOUND` / `ZERO_RESULTS` / etc.) |
| 429 | Per-IP rate limit hit |
| 502 | Network or malformed upstream response |
| 503 | API key missing |

## What the editor sees if it's not configured

If the API key is empty:

- The form still renders (so the design is preview-able).
- A yellow editor-only notice appears beneath the heading: _"Add a
  Google Maps API key at Appearance → Customize → Google Maps to
  enable live travel lookups."_
- The map area falls back to the placeholder image (or a "Configure
  a Google Maps API key…" placeholder visible only to logged-in
  editors).

## Troubleshooting

- **"Travel Calculator is not configured yet."** — API key missing.
  Set it in Customizer → Google Maps.
- **"Couldn't reach the travel service."** — server can't reach
  `maps.googleapis.com`. Check outbound firewall.
- **"Too many lookups — please wait a minute."** — IP-level
  rate-limit; resets after 60 seconds.
- **Map iframe is blank** — likely an HTTP-referrer mismatch in the
  key restrictions. Open browser DevTools → console for the Google
  Maps error.

## Related components

- [`contact`](CONTACT.md) — uses the same Google Maps Customizer
  configuration for its embedded map.
