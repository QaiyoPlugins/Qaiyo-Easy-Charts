# Qaiyo Easy Charts

> Simple, beautiful, animated and accessible charts for WordPress. Build them in the admin with a live preview, then place them with a shortcode, a Gutenberg block, Elementor or Bricks Builder.

[![WordPress 6.2+](https://img.shields.io/badge/WordPress-6.2%2B-21759b.svg)](https://wordpress.org/)
[![PHP 8.0+](https://img.shields.io/badge/PHP-8.0%2B-777bb4.svg)](https://www.php.net/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![Free](https://img.shields.io/badge/free-1.0.0-6c5ce7.svg)](#features)
[![WCAG 2.2 AA](https://img.shields.io/badge/WCAG-2.2%20AA-2e7d32.svg)](#accessibility)

- **Website:** [qaiyo-plugins.com](https://qaiyo-plugins.com)
- **Support:** info@qaiyo-plugins.com
- **Issues:** [GitHub Issues](../../issues)

---

## Why Qaiyo Easy Charts

Built against the most common complaints about WordPress chart plugins:

| Common complaint | How this plugin answers it |
|---|---|
| Basic features locked behind Pro | Every chart type and feature in this plugin is free: animations, lazy loading, tooltips, export, view statistics. |
| Charts break after updates | No third-party chart library to change under you. Every saved chart goes through one validating config with defaults, so older charts keep rendering the same; shortcode, block, element and meta names are frozen and covered by backward-compatibility tests. |
| Heavy JavaScript (90 KB+) | ~6 KB JS and ~5.5 KB CSS gzipped, loaded only on pages that show a chart; the chart itself is HTML/CSS and works without JavaScript. |
| GDPR trouble with Google's CDN | No external request on the front end: no CDN, no Google, no fonts or scripts from elsewhere. View statistics are anonymous and cookie-free. |
| Poor mobile canvas rendering | No canvas: real HTML that adapts to the chart's own width (container queries), crisp at any zoom, stacking labels on narrow screens. |
| Inaccessible charts | Figure + hidden data table, keyboard-focusable tooltips, patterns besides colours, reduced-motion and forced-colours support; axe-core reports no violations. |

## Charts

### Bar / column chart with ranges

Each bar has a solid **value** and an optional **range end** that draws a highlighted range on top — perfect for "up to 5 days", "10–15 days", price ranges, delivery times or scores.

| Orientation | What it does |
|---|---|
| **Horizontal bars** | Label · bar · value in one row; labels move above the bar when the chart is narrow. |
| **Vertical columns** | Columns with values on top, labels below and a value axis on the left. |
| **Columns, bars on narrow screens** | Columns on wide layouts, automatically horizontal bars below 560 px. |

### More chart types

| Type (`type=`) | What it shows | Second value means |
|---|---|---|
| **Comparison bars** (`compare`) | Two series per row with a legend, e.g. your price vs. the market | the second series |
| **KPI rings** (`ring`) | One progress ring per item with the value in the middle | the goal (default: scale maximum or 100) |
| **Timeline** (`timeline`) | Bars from start to finish on a shared axis; an empty finish is a milestone | the finish |
| **Bullet chart** (`bullet`) | The value as a bar, the target as a line, over qualitative bands (`bands="50,75,100"`) | the target |
| **100% stacked bar** (`stacked`) | One bar split into parts, legend with values and shares | — |
| **Rating distribution** (`rating`) | Weighted average with stars, total, and the share of every rating level | — |

Every type uses the same rows (label, value, second value, value text, colours), so a chart can switch type without re-entering data.

## Features

- **Admin chart builder** — rows with label, value, range end, custom value text and per-bar colours; reorder with buttons (keyboard friendly); live preview with desktop / mobile toggle; copyable shortcode.
- **Every way to place a chart**
  - Shortcode: `[qaiyo_chart id="12"]` or fully inline: `[qaiyo_chart data="Landing page;5;;up to 5|Business site;10;15" unit="days" orientation="vertical"]`
  - Gutenberg block **Qaiyo Easy Chart** — saved chart or bars entered in the block, colour panel, block typography (font family, size), spacing, wide / full alignment.
  - Elementor widget and Bricks element — saved chart or inline repeater, full Style tab: chart font and per-text typography, colours, bar thickness, column height, radius, gaps, animation timing.
- **Responsive by the chart's own width** (container queries) — works in columns and sidebars, not only on phones. Colliding axis ticks are thinned out automatically.
- **Animated, switchable per chart** — effects `grow` / `fade` / `rise`, motion curves smooth / even / springy, duration and delay between bars, values counting up, optional replay on every scroll, a *Play animation* button in the builder preview. Respects `prefers-reduced-motion`; static without JavaScript.
  Shortcode: `effect="rise" easing="spring" duration="900" delay="80" count="1" replay="0" animate="1"`.
- Shortcode for the other types and features: `type="compare" series_a="Us" series_b="Market"`, `type="bullet" bands="50,75,100"`, `tooltips="1" patterns="1" export="1"`.
- **Lazy loading** — the chart is drawn when it scrolls within 300 px of the viewport (IntersectionObserver), with a size-matched placeholder so nothing jumps. The data table is always in the HTML, and a `<noscript>` copy keeps it visible without JavaScript. `lazy="0"` turns it off.
- **Tooltips** — on hover *and* keyboard focus (every item is focusable), dismissible with Escape (WCAG 1.4.13).
- **Patterns besides colours** — stripes, dots and lines on second series, ranges and stacked parts, for colour-blind readers and greyscale prints.
- **Paste from a spreadsheet** — copy rows from Excel / Google Sheets or paste CSV into the chart builder or the block; tabs, semicolons and commas are detected, header rows skipped, `1 200,5` style numbers understood.
- **Copy the style of another chart** — colours, font, sizes, patterns and animation in one click; the data stays.
- **PNG / SVG export** — download buttons in the builder preview, and optionally under the chart on the site. The export embeds the chart's stylesheet and fonts, so it looks exactly like the page, in its final drawn state.
- **View statistics** — like Qaiyo Content Slider: the chart list shows how many visitors saw each chart (sortable), and the chart editor shows the total, the last 7 and 30 days and a 30-day graph, with a reset button. A view is counted only when the chart actually scrolls into view, once per visitor per 30 minutes; bots and chart editors are skipped. Anonymous by design: no cookies, no stored IP (a salted hash lives 30 minutes as a transient), and a suggested privacy-policy text is added to WordPress. Switchable in the settings.
- **Every chart styled on its own** — colours, font and sizes live in each chart, block and builder element (there is no site-wide colour setting, so many different charts can sit side by side). New charts start from the default palette and show it as editable values. All styling is CSS custom properties.

## Accessibility (WCAG 2.2 AA)

Charts and admin screens are built and tested against WCAG 2.2 level AA.

| Criterion | How it is met |
|---|---|
| 1.1.1 Non-text content | Every chart is a `<figure>` with an accessible name; the data is in a real (visually hidden) table; decorative drawing is hidden from assistive technology. |
| 1.3.1 Info and relationships | Data table with caption, column and row headers; with tooltips or without the table the drawing becomes a labelled list. |
| 1.4.1 Use of colour | Values are always text. Where colour would be the only cue (comparison series, stacked parts) patterns are always drawn; optional patterns for ranges too. |
| 1.4.3 Contrast (text) | Default colours 5.4:1 and higher; tooltips use fixed 18:1 colours; hover dimming never fades text; the chart builder warns live when a chosen text colour is below 4.5:1. Admin accent text 5.8:1. |
| 1.4.4 Resize text | Sizes in `em`; rings and the rating summary grow and stack with larger text (container queries in `em`). Tested at 200 %. |
| 1.4.10 Reflow | No horizontal scrolling at 320 CSS px, front end and chart builder (tested). |
| 1.4.11 Non-text contrast | Stacked-bar palette at least 3:1, focus outlines in the label colour, export buttons with a 5:1 border. |
| 1.4.12 Text spacing | No clipping or overflow with WCAG text-spacing overrides (tested). |
| 1.4.13 Content on hover or focus | Tooltips appear on hover *and* focus, can be hovered, stay until dismissed, close with Escape, and are kept inside the chart and window. |
| 2.1.1 Keyboard | Every tooltip item, button and builder control is reachable and operable by keyboard; rows reorder with buttons. |
| 2.2.2 Pause, stop, hide | The entrance animation (counting included) always ends within 5 seconds: long charts shorten the delay automatically. |
| 2.3.3 Animation from interactions | `prefers-reduced-motion` turns all motion off (charts are drawn at once). |
| 2.4.7 Focus visible | 2 px outline with offset on chart items and buttons. |
| 2.5.3 Label in name | Visible button text ("PNG") is part of the accessible name ("Download the chart as PNG"). |
| 2.5.8 Target size | Buttons at least 24 × 24 px (export buttons 44 × 32 px). |
| 4.1.2 Name, role, value | Native elements, `aria-pressed` toggles, labelled fields and fieldsets. |
| 4.1.3 Status messages | Live preview, row changes, imports, style copy and contrast warnings are announced (`wp.a11y.speak`, `role="status"`). |

Also supported: forced colours (Windows high contrast). Verified with axe-core (0 violations on every chart type and on the plugin's admin screens) and scripted checks for reflow, text spacing, 200 % text, hoverable tooltips, focus and reduced motion.

Colours are chosen per chart: the builder's contrast check helps, but contrast against your own page background stays your choice.

## SEO & AI friendly

- Real HTML text and a semantic `<table>` instead of a canvas image — search engines and AI crawlers read the numbers.
- Elementor `render_plain_content()` returns the data as plain text.
- **WordPress Abilities API** (6.9+): `qaiyo-easy-charts/list-charts`, `get-chart` and `create-chart` let AI agents and MCP clients discover, read and create charts with typed JSON schemas and permission checks. See [HOOKS.md](HOOKS.md).

## Qaiyo ecosystem

- Shares the **Qaiyo Plugins** admin menu group with other Qaiyo plugins (one brand chip, no duplicates).
- **Discover Qaiyo** page: free Qaiyo plugins from the official WordPress.org API and the Pro extensions — no request to any Qaiyo server.
- Qaiyo Design System v2 admin screens.

## Translations

Bundled: English, Hungarian, German, French, Spanish, Japanese, Portuguese, Italian, Russian, Turkish, Polish (regional variants map to their base language). Compatible with WPML, Polylang and TranslatePress.

Developers: strings live in `languages/i18n/<locale>.json`; `python3 languages/build-translations.py` regenerates the `.pot`, `.po` and `.mo` files and reports missing strings.

## Development

```
qaiyo-easy-charts.php          bootstrap: header, autoloader, requirements, translations
src/                           PSR-4 (Qaiyo\EasyCharts\), one class per file
  Plugin.php                   composition root
  Schema.php                   every shared identifier
  Chart/                       ChartConfig (the one validation point), Renderer, Repository
  Frontend/                    assets, shortcode
  Blocks/                      Gutenberg block (blocks/chart/block.json)
  Integrations/Elementor|Bricks
  Admin/                       post type, chart editor, settings, brand menu, Discover Qaiyo
  Ai/                          Abilities API
  Stats/                       anonymous chart views (REST endpoint, counters)
templates/admin/               admin markup
assets/                        chart.css / chart.js (front end), admin + editor + block scripts
languages/                     .pot, .po, .mo and the build script
```

No build step and no runtime dependencies. Hooks: [HOOKS.md](HOOKS.md). Security: [SECURITY.md](SECURITY.md).

### Quality checks

```bash
composer install
composer check      # PHPCS (WPCS + PHPCompatibility), PHPStan level max, PHPUnit + Brain Monkey, translation completeness
composer build      # release zip in build/ (development files from .distignore left out)
```

| Tool | Configuration | Status |
|---|---|---|
| PHP_CodeSniffer + WordPress Coding Standards 3 + PHPCompatibilityWP (PHP 8.0+; the code uses PHP 8 features: `match`, native `mixed` and union types, arrow functions, `str_contains` / `str_starts_with`) | `phpcs.xml.dist` | 0 errors, 0 warnings |
| PHPStan 2 + `szepeviktor/phpstan-wordpress`, **level max** | `phpstan.neon.dist` | 0 errors |
| PHPUnit 9 + Brain Monkey (unit tests without WordPress), incl. backward-compatibility tests | `phpunit.xml.dist`, `tests/` | passing |
| Plugin Check (WordPress.org), `--mode=new`, static + runtime, experimental and low-severity checks | release build | no errors, no warnings |

Elementor and Bricks are analysed against minimal stubs in `tests/stubs/`.

## Requirements

| | Minimum |
|---|---|
| WordPress | 6.2 |
| PHP | 8.0 |
| Elementor (for the widget) | 3.5 |
| Bricks Builder (for the element) | 2.0 — older versions get an admin notice instead of the element |

## License

GPL-2.0-or-later.
