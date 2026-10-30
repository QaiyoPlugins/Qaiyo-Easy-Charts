# Qaiyo Easy Charts

> Simple, beautiful, animated and accessible charts for WordPress. Build them in the admin with a live preview, then place them with a shortcode, a Gutenberg block, Elementor or Bricks Builder.

[![WordPress 6.2+](https://img.shields.io/badge/WordPress-6.2%2B-21759b.svg)](https://wordpress.org/)
[![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://www.php.net/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![Free](https://img.shields.io/badge/free-1.0.0-6c5ce7.svg)](#features)
[![WCAG 2.2 AA](https://img.shields.io/badge/WCAG-2.2%20AA-2e7d32.svg)](#accessibility)

- **Website:** [qaiyo-plugins.com](https://qaiyo-plugins.com)
- **Support:** info@qaiyo-plugins.com
- **Issues:** [GitHub Issues](../../issues)

---

## Charts

### Bar / column chart with ranges

Each bar has a solid **value** and an optional **range end** that draws a highlighted range on top — perfect for "up to 5 days", "10–15 days", price ranges, delivery times or scores.

| Orientation | What it does |
|---|---|
| **Horizontal bars** | Label · bar · value in one row; labels move above the bar when the chart is narrow. |
| **Vertical columns** | Columns with values on top, labels below and a value axis on the left. |
| **Columns, bars on narrow screens** | Columns on wide layouts, automatically horizontal bars below 560 px. |

More chart types will follow.

## Features

- **Admin chart builder** — rows with label, value, range end, custom value text and per-bar colours; reorder with buttons (keyboard friendly); live preview with desktop / mobile toggle; copyable shortcode.
- **Every way to place a chart**
  - Shortcode: `[qaiyo_chart id="12"]` or fully inline: `[qaiyo_chart data="Landing page;5;;up to 5|Business site;10;15" unit="days" orientation="vertical"]`
  - Gutenberg block **Qaiyo Easy Chart** — saved chart or bars entered in the block, colour panel, block typography (font family, size), spacing, wide / full alignment.
  - Elementor widget and Bricks element — saved chart or inline repeater, full Style tab: chart font and per-text typography, colours, bar thickness, column height, radius, gaps, animation timing.
- **Responsive by the chart's own width** (container queries) — works in columns and sidebars, not only on phones. Colliding axis ticks are thinned out automatically.
- **Animated** — bars grow when scrolled into view, ranges follow; respects `prefers-reduced-motion`; static without JavaScript.
- **Adjustable everything** — colours, font, sizes and defaults in **Easy Charts → Settings**; every chart, block and builder element can override them. All styling is CSS custom properties.

## Accessibility

- Every chart is a `<figure>` with an accessible name (its title).
- An accessible, visually hidden **data table** carries the values for screen readers (on by default, switchable).
- Forced-colours (Windows high contrast) support, reduced-motion support, no information by colour alone (values are always in text).
- Admin screens: labelled fields, fieldsets with legends, keyboard row reordering, screen-reader announcements (`wp.a11y.speak`), AA contrast.

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
templates/admin/               admin markup
assets/                        chart.css / chart.js (front end), admin + editor + block scripts
languages/                     .pot, .po, .mo and the build script
```

No build step and no runtime dependencies. Hooks: [HOOKS.md](HOOKS.md). Security: [SECURITY.md](SECURITY.md).

## License

GPL-2.0-or-later.
