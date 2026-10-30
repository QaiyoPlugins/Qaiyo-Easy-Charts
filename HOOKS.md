# Qaiyo Easy Charts — hooks

All hooks are prefixed with `qaiyo_easy_charts_` and keep their parameter
contract across minor releases.

## Filters

### `qaiyo_easy_charts_default_config`

Filters the default chart configuration (seeded from **Easy Charts → Settings**)
before any chart, shortcode, block or builder element is built.

```php
add_filter( 'qaiyo_easy_charts_default_config', function ( array $defaults ): array {
	$defaults['unit']        = 'days';
	$defaults['orientation'] = 'auto';
	return $defaults;
} );
```

| Parameter   | Type  | Description                 |
|-------------|-------|-----------------------------|
| `$defaults` | array | Default configuration keys. |

Since 1.0.0.

### `qaiyo_easy_charts_config`

Filters the sanitized configuration right before a chart renders. The result is
validated again, so an invalid value can never reach the markup.

```php
add_filter( 'qaiyo_easy_charts_config', function ( array $config ): array {
	if ( is_front_page() ) {
		$config['animate'] = false;
	}
	return $config;
} );
```

| Parameter | Type  | Description          |
|-----------|-------|----------------------|
| `$config` | array | Chart configuration. |

Since 1.0.0.

### `qaiyo_easy_charts_chart_html`

Filters the final chart HTML.

| Parameter | Type   | Description          |
|-----------|--------|----------------------|
| `$html`   | string | Chart markup.        |
| `$config` | array  | Chart configuration. |

Since 1.0.0.

## Configuration keys

| Key | Values |
|---|---|
| `orientation` | `horizontal`, `vertical`, `auto` (columns, bars when the chart is narrower than 560 px) |
| `items` | list of `label`, `value`, `max` (optional range end), `text` (optional value text), `color`, `range_color` |
| `title`, `description`, `show_caption` | caption / accessible name |
| `scale_min`, `scale_max`, `step` | axis; empty `scale_max` is calculated |
| `unit`, `unit_position` | `last`, `all`, `none` |
| `show_axis`, `show_values`, `separator`, `prefix`, `suffix` | value display |
| `layout` | `auto`, `inline`, `stacked` (label placement of horizontal bars) |
| `animate`, `hover_focus`, `data_table` | behaviour and accessibility |
| `bar_color`, `range_color`, `track_color`, `label_color`, `value_color`, `axis_color`, `font_family`, `bar_size`, `chart_height`, `radius` | style |

## JavaScript

`window.qaiyoEasyChartsInit()` initialises charts inserted into the page after
load (AJAX, popups, infinite scroll). It is idempotent.

## CSS custom properties

Every visual setting is a custom property on `.qec-chart`, e.g.
`--qec-primary`, `--qec-secondary`, `--qec-track`, `--qec-bar-size`,
`--qec-chart-height`, `--qec-row-gap`, `--qec-column-gap`, `--qec-duration`.
Defaults use `:where()` (zero specificity), so any theme rule wins.

## Abilities API (WordPress 6.9+)

| Ability | Permission | Description |
|---|---|---|
| `qaiyo-easy-charts/list-charts` | `edit_posts` | Saved charts with ID, title, status and shortcode. |
| `qaiyo-easy-charts/get-chart` | `edit_post` on the chart | One chart's bars, unit, orientation and shortcode. |
| `qaiyo-easy-charts/create-chart` | `edit_posts` (+ `publish_posts` to publish) | Creates a chart and returns its shortcode. |
