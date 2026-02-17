# Universal UI System

Use this design system for all public/dashboard pages that need the futuristic glassmorphism + neumorphism style.

## Files

- `css/ui-universal.css`: Main shared design system (use this in new pages).
- `css/future-ui.css`: Backward-compatible wrapper that imports `ui-universal.css`.
- `js/future-ui.js`: Shared interactions (theme switcher, tilt/glow, press depth, orb parallax).

## Minimum Page Setup

```html
<link href="../css/bootstrap.min.css" rel="stylesheet">
<link href="../bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="../css/ui-universal.css" rel="stylesheet">
<script src="../js/future-ui.js" defer></script>
```

```html
<body class="future-page" data-theme="nebula">
  <div class="future-grid" aria-hidden="true"></div>
  <div class="future-orb future-orb-a" aria-hidden="true"></div>
  <div class="future-orb future-orb-b" aria-hidden="true"></div>
  <div class="future-orb future-orb-c" aria-hidden="true"></div>
</body>
```

## Common Reusable Classes

- Layout: `ui-container`, `ui-stack`, `ui-grid`
- Surface: `glass-panel`, `neo-panel`
- Buttons: `ui-btn`, `ui-btn ghost`, `pressable`
- Motion: `tilt-surface` with `data-tilt="6|7|8"`
- Inputs: `ui-input-group`, `ui-input`
- Theme switcher: `theme-switcher`, `theme-chip`, `data-theme-choice="nebula|ember|aurora"`

## Existing Page Scopes

- `future-home`: Home page styles used by `public/index.php`
- `future-login`: Login page styles used by `public/login.php`
- `future-contact`: Contact form styles used by `public/contact.php`
- `future-forgot`: Forgot password styles used by `public/forgot_password.php`
- `future-dashboard`: Shared dashboard shell used by pages in `dashboard/` via `includes/sidebar_styles.php` + `includes/sidebar_scripts.php`

## Dashboard Notes

- Sidebar variants (`admin_siderbar.php`, `hr_sidebar.php`, `leader_sidebar.php`, `employee_sidebar.php`) should keep the same theme switcher markup.
- `includes/sidebar_scripts.php` auto-adds `future-page future-dashboard` to `<body>` and injects fallback mobile controls (`#sidebarToggleBtn`, `#sidebarOverlay`) if a legacy page is missing them.

When styling new pages, prefer reusable utility/component classes first and add page scopes only when needed.
