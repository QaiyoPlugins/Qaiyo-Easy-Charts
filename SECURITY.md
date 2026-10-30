# Security

## Reporting a vulnerability

Please report security issues privately to **info@qaiyo-plugins.com**, not in
public GitHub issues. We acknowledge reports within 3 working days.

## How the plugin handles data

- **Single validation point.** Every input path — chart editor, shortcode, block
  attributes, Elementor and Bricks settings, Abilities API — goes through
  `ChartConfig::sanitize()`. Text is `sanitize_text_field()`-ed and length
  capped, numbers are parsed strictly, choices are allowlisted, and CSS values
  (colours, lengths, font families) must match strict patterns, so a value can
  never break out of the inline style it is written into.
- **Escaping on output.** All markup is escaped at the point of output
  (`esc_html`, `esc_attr`, `esc_url`).
- **Capabilities and nonces.** Saving a chart checks a nonce and
  `edit_post`; the live-preview AJAX endpoint checks a nonce and `edit_post`;
  settings use the Settings API (`options.php`, `manage_options`). Abilities
  have permission callbacks (`edit_posts`, `edit_post`, `publish_posts`).
- **Visibility.** Unpublished charts render only for users who can edit them.
- **Bounded work.** A chart holds at most 100 bars and 50 axis ticks; chart
  lists are capped.
- **No external requests** except the optional "Discover Qaiyo" page, which
  queries the official WordPress.org plugin API only when an administrator
  opens it (cached for 12 hours). No data is sent to Qaiyo.
- **Templates** are resolved with `realpath()` inside `templates/admin/` only.
- **Uninstall** keeps charts unless "Delete all charts and settings" is on;
  multisite aware.
