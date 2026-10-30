=== Qaiyo Easy Charts ===
Contributors: qaiyo
Tags: chart, bar chart, graph, elementor, bricks
Requires at least: 6.2
Tested up to: 7.1
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Simple, beautiful, animated and accessible bar and column charts. Shortcode, Gutenberg block, Elementor and Bricks Builder.

== Description ==

Qaiyo Easy Charts makes clean, modern charts that look designed, not generated. Build a chart in the admin with a live preview, then place it anywhere.

Seven chart types, all from the same simple rows:

* **Bars with ranges** — horizontal bars or vertical columns, every bar with a solid value and an optional highlighted range ("up to 5 days", "10–15 days").
* **Comparison bars** — two series per row, e.g. your price vs. the market average.
* **KPI rings** — progress rings with the value in the middle.
* **Timeline** — bars from start to finish, milestones as diamonds.
* **Bullet chart** — value against a target over qualitative bands.
* **100% stacked bar** — how a whole splits into parts.
* **Rating distribution** — average, stars and the share of every rating level.

**Key features:**

* **Horizontal bars or vertical columns** — or columns that switch to bars on narrow screens automatically.
* **Admin chart builder** — rows, custom value text, per-bar colours, keyboard-friendly reordering, live desktop / mobile preview, copyable shortcode.
* **Shortcode** — `[qaiyo_chart id="12"]`, or fully inline with the `data` attribute.
* **Gutenberg block** — saved chart or bars entered in the block, colours, block typography and spacing.
* **Elementor widget and Bricks element** — saved chart or inline bars, with full typography (font family included), colours, sizes and animation controls.
* **Responsive by the chart's own width** — container queries, so charts adapt in columns and sidebars too. Colliding axis labels are thinned out automatically.
* **Animated, switchable per chart** — grow, fade or rise effects, smooth / even / springy motion, custom duration and delay between bars, values counting up, optional replay on every scroll. Respects reduced-motion preferences; works without JavaScript.
* **Lazy loading** — charts are drawn only when they scroll near; the accessible data table stays in the HTML for search engines and screen readers.
* **Tooltips on hover and keyboard focus**, dismissible with Escape.
* **Patterns besides colours** for colour-blind readers.
* **Paste from Excel, Google Sheets or CSV** into the builder or the block.
* **Copy the style of another chart** in one click.
* **PNG and SVG export** from the builder and, optionally, from the page.
* **Accessible** — a figure with an accessible name and a visually hidden data table for screen readers, forced-colours support, WCAG 2.2 AA admin screens.
* **SEO and AI friendly** — real HTML text and a semantic data table instead of an image; WordPress Abilities API support so AI agents can list, read and create charts.
* **Every chart styled on its own** — colours, font and sizes are set per chart, block or builder element, starting from a clean default palette.
* **Translation ready** — ships with English, Hungarian, German, French, Spanish, Japanese, Portuguese, Italian, Russian, Turkish and Polish.
* **WordPress standards** — nonces, capability checks, strict sanitization, escaped output, no external requests on the front end.

== Installation ==

1. Upload the `qaiyo-easy-charts` folder to `/wp-content/plugins/`.
2. Activate the plugin from the Plugins screen.
3. Open **Easy Charts → Add chart**, enter the bars and check the live preview.
4. Copy the shortcode, or add the **Qaiyo Easy Chart** block, Elementor widget or Bricks element.

== Frequently Asked Questions ==

= Can I change the colours and the font? =

Yes, for every chart separately: in the chart builder, in the block's colour and typography panels, and in the Elementor and Bricks style controls. Single bars can have their own colours too.

= Can I use it without a page builder? =

Yes — with the shortcode or the Gutenberg block.

= What is the shortcode format for inline data? =

Rows are separated by `|`, fields by `;`: label;value;range end;value text. Example: `[qaiyo_chart data="Landing page;5;;up to 5|Business site;10;15" unit="days"]`. Easy Charts → Settings lists every attribute.

= Can I switch the animation off? =

Yes, per chart (builder, block, Elementor, Bricks, or `animate="0"` in the shortcode), and site-wide as a default in Easy Charts → Settings. Visitors who prefer reduced motion never see it.

= Is my data sent anywhere? =

No. The only external request is the optional "Discover Qaiyo" admin page, which reads the official WordPress.org plugin directory when an administrator opens it.

= What happens on uninstall? =

Charts are kept unless you switch on "Delete all charts and settings when the plugin is deleted" in the settings.

== Screenshots ==

1. Horizontal bars with ranges.
2. Vertical columns.
3. Chart builder with live preview.
4. Settings.

== Changelog ==

= 1.0.0 =
* First release: seven chart types (bars with ranges, comparison bars, KPI rings, timeline, bullet chart, 100% stacked bar, rating distribution), tooltips, patterns, spreadsheet paste, style copy, PNG / SVG export, switchable animations, lazy loading, chart builder, shortcode, Gutenberg block, Elementor widget, Bricks element, Abilities API, 10 translations.
