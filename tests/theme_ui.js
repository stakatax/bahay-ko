const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('Assets/js/theme.js', 'utf8');
let checks = 0;
function fixture(saved, dark = false, blocked = false, reduced = false, viewTransitions = false) {
    const events = {}, windowEvents = {}, mediaEvents = {}, attributes = {};
    const label = {}, icon = {}, root = { dataset: {} };
    const button = { setAttribute: (k, v) => { attributes[k] = v; }, querySelector: s => s === 'i' ? icon : label };
    const media = { matches: dark, addEventListener: (k, fn) => { mediaEvents[k] = fn; } };
    const timers = [];
    const storage = { value: saved, getItem() { if (blocked) throw Error('blocked'); return this.value; }, setItem(k, v) { if (blocked) throw Error('blocked'); this.value = v; } };
    let skipped = 0;
    const document = { documentElement: root, querySelectorAll: () => [button], addEventListener: (k, fn) => { events[k] = fn; } };
    if (viewTransitions) document.startViewTransition = callback => {
        callback();
        return { finished: Promise.resolve(), skipTransition: () => { skipped++; } };
    };
    vm.runInNewContext(source, {
        document,
        window: { matchMedia: query => query.includes('reduced-motion') ? { matches: reduced } : media, addEventListener: (k, fn) => { windowEvents[k] = fn; }, setTimeout: fn => { timers.push(fn); return timers.length; }, clearTimeout: () => {} },
        localStorage: storage
    });
    return { root, attributes, label, icon, storage, media, events, windowEvents, mediaEvents, timers, skipped: () => skipped };
}
function check(ok) { assert.ok(ok); checks++; }
let f = fixture(null, true);
check(f.root.dataset.theme === 'light');
check(f.attributes['aria-pressed'] === 'false');
check(f.label.textContent === 'Dark mode');
f.events.click({ target: { closest: () => ({}) } });
check(f.root.dataset.theme === 'dark' && f.storage.value === 'dark');
f.media.matches = false;
check(!f.mediaEvents.change && f.root.dataset.theme === 'dark');
f.windowEvents.storage({ key: 'olshco-theme', newValue: 'dark' });
check(f.root.dataset.theme === 'dark');
f.windowEvents.storage({ key: 'unrelated', newValue: 'light' });
check(f.root.dataset.theme === 'dark');
f = fixture('dark', false);
check(f.root.dataset.theme === 'dark');
f = fixture('invalid', false);
check(f.root.dataset.theme === 'light');
f.media.matches = true;
check(!f.mediaEvents.change && f.root.dataset.theme === 'light');
f = fixture(null, true, true);
f.events.click({ target: { closest: () => ({}) } });
check(f.root.dataset.theme === 'dark');
f.events.DOMContentLoaded();
check(f.attributes['aria-label'] === 'Switch to light mode');
check(f.root.dataset.themeAnimating === 'true');
f.timers.at(-1)();
check(f.root.dataset.themeAnimating === undefined);
f = fixture('light', false, false, true);
f.events.click({ target: { closest: () => ({}) } });
check(f.root.dataset.theme === 'dark' && f.root.dataset.themeAnimating === undefined && f.timers.length === 0);
f = fixture('light', false, false, false, true);
f.events.click({ target: { closest: () => ({}) } });
check(f.root.dataset.theme === 'dark' && f.root.dataset.themeTransition === undefined);
f.events.click({ target: { closest: () => ({}) } });
check(f.root.dataset.theme === 'light' && f.skipped() === 1);
f.windowEvents.storage({ key: 'olshco-theme', newValue: null });
check(f.root.dataset.theme === 'light');
console.log(`PASS: ${checks} theme preference, storage failure, system change and accessible-toggle checks.`);
