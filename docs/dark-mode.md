# Dark mode

Added October 6, 2026. The shared sidebar has a theme button; authentication pages have a compact button in the upper-right corner. Mobile users open the navigation menu to find the sidebar button.

The default theme is light regardless of the device theme. An explicit choice is saved in this browser under `olshco-theme`, applies before the page styles paint, and synchronizes across tabs on the same origin. Existing saved dark preferences are respected. If browser storage is blocked, toggling still works for the current page. Localhost and the public tunnel are different origins and keep separate preferences.

Maroon buttons, orange accents, photographs, and strong branded panels retain their colors. Dark surfaces, muted text, borders, and readable brand text are scoped to `html[data-theme="dark"]`. Light-mode page styles are unchanged. No database or account setting is involved.

Pressing the toggle crossfades the page over 520ms when browser View Transitions are available, with a 480ms color-transition fallback and a subtle icon turn/button press. Initial page load does not animate. Reduced-motion users switch immediately. Repeated clicks cancel the previous transition and preserve the latest selected theme.

## Maintenance

`Assets/js/theme.js` handles preference and accessible button state. `Assets/css/theme.css` contains shared tokens plus generated color overrides, scoped to the current page's CSS filename. To regenerate after changing fixed page colors, run `python scripts/build_theme_styles.py` (developer dependency: `tinycss2`). The generator excludes keyframes and image URLs; review new semantic fills and inline colors visually.

## Verification

- PHP syntax: shared front controller and sidebar passed; JavaScript syntax passed.
- Eighteen Node checks passed for the light default, independence from device-theme changes, persistence, invalid stored values, cross-tab updates, blocked storage, accessible control labels/states, reduced motion, cleanup, and repeated View Transition clicks.
- Six local public routes rendered the early initialization, stylesheet, and theme button. Both assets returned HTTP 200.
- Existing post navigation, card interaction, and registration preservation simulations passed.
- Generated CSS parsed without errors. Main text, muted text, brand text on dark surfaces, and white text on maroon exceeded 4.5:1 contrast.
- Headless Chrome desktop login/home previews and actual 390px mobile login/contact/home routes were inspected. Mobile viewport and document widths both measured 390px; no horizontal overflow in those checked pages.

This visual check does not cover every authenticated modal, chart, or table. Email rendering remains independent of the website theme.
