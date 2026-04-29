# Assets / JavaScript

Each file in this folder is scoped to one page. They are loaded by `index.php` only when the matching page is active (via `$pageJS`). `navbar.js` is the only file loaded on every page.

---

## navbar.js

**Loaded on:** Every page

Toggles the mobile hamburger menu open/closed.

- Reads `#menuBtn` (the hamburger button) and `#mobileMenu` (the nav drawer)
- Clicking `#menuBtn` toggles `mobileMenu` between `display: block` and `display: none`

---

## calendar.js

**Loaded on:** `?page=calendar`

**Depends on:** A global `EVENTS` object injected by `calendar.php` (a PHP-rendered JS variable keyed by `'YYYY-MM-DD'` date strings).

Handles all calendar interactivity:

**Day cell selection**
- Listens for clicks on `.day-cell` elements
- Removes `.selected` from the previously selected cell, adds it to the clicked one
- Calls `renderEvents(date)` to display that date's events in the side panel

**`renderEvents(date)`**
- Reads from the `EVENTS` global object
- Renders event mini-cards into `#eventList`
- Shows a "No Events" placeholder if the date has no events

**Add Event Modal**
- `openModal()` — shows `#eventModal`, displays the selected date. Requires a date to be selected first.
- `closeModal()` — hides the modal. Also triggered when clicking outside the modal.
- `saveEvent()` (exposed on `window`) — POSTs `{ date, title }` as JSON to `?page=event_store`, updates the local `EVENTS` object on success, and re-renders the event list without a page reload.

**Horizontal scroll**
- The `.event-container` supports mouse-wheel horizontal scrolling (vertical wheel input is redirected to `scrollLeft`).

---

## news.js

**Loaded on:** `?page=news`

Drives the announcement slideshow.

- Auto-advances slides every 5 seconds using `setInterval`
- `moveSlide(direction)` — moves forward (`+1`) or backward (`-1`), loops around at both ends
- After a manual navigation, the interval is reset so the 5-second timer restarts from zero
- Slides are `.slide` elements; the active one has the `.active` class

---

## posting.js

**Loaded on:** `?page=postings`

**Depends on:** SweetAlert2 (`Swal`) loaded via CDN in `postings.php`

Manages the post creation form's dynamic behaviour.

**`switchType(type, btn)`**
- Called when the user clicks a post-type toggle button (`announcement`, `event`, `document`)
- Removes `.active` from all `.type-btn` elements, adds it to the clicked one
- Updates the hidden `#post_type` input value
- Shows/hides `#event-specific-fields` (the date picker), which is only needed for event posts
- Updates the submit button label to match the selected type

**`toggleDepartment(value)`**
- Shows `#department-selection` when scope is `'departmental'`, hides it otherwise

**`confirmPost()`**
- Validates the form with native `checkValidity()` / `reportValidity()` before opening the dialog
- Shows a SweetAlert2 confirmation dialog with branded styling
- On confirm: programmatically clicks `#hiddenSubmit` to submit the form

---

## register.js

**Loaded on:** `?page=register`

**Depends on:** SweetAlert2 (`Swal`) loaded via CDN in `register.php`

**`confirmRegistration()`**
- Validates the form before showing the dialog
- Shows a SweetAlert2 confirmation asking the user to verify their information
- On confirm: clicks `#hiddenSubmit` to submit the registration form

---

## academic.js

**Loaded on:** `?page=academic`

Rotates the hero image every 5 seconds through a hardcoded list of four images:

```
Assets/images/college.jpg
Assets/images/seniorhigh.jpg
Assets/images/juniorhigh.jpg
Assets/images/elementary.jpg
```

Updates `#heroImg`'s `src` attribute on each interval tick.
