"""Compile scoped dark color overrides without changing existing light styles.

Developer utility: requires tinycss2. Run after adding fixed colors to page CSS.
No network, database, uploads, or deployment changes.
"""
from pathlib import Path
import colorsys
import re
import tinycss2

ROOT = Path(__file__).resolve().parents[1]
CSS = ROOT / 'Assets/css'
HEX = re.compile(r'#[0-9a-fA-F]{3,8}\b')
SHARED = {'index.css', 'root.css', 'app.css', 'app-dialog.css'}


def color(value, property_name):
    if len(value) not in (4, 7):
        return value
    digits = value[1:]
    if len(digits) == 3:
        digits = ''.join(c * 2 for c in digits)
    rgb = tuple(int(digits[i:i+2], 16) / 255 for i in (0, 2, 4))
    hue, light, saturation = colorsys.rgb_to_hls(*rgb)
    background = property_name.startswith('background')
    border = property_name.startswith('border') or property_name in ('outline', 'outline-color')
    if background:
        # Keep photographic/media black and strong brand/semantic fills intact.
        if light < .65:
            return value
        if saturation < .25 or max(rgb) - min(rgb) < .10:
            return 'var(--theme-surface)' if light > .94 else 'var(--theme-raised)'
        if min(rgb) > .65:
            return 'var(--theme-warm)' if hue < .12 or hue > .9 else 'var(--theme-raised)'
        return value
    if border:
        return 'var(--theme-border)' if saturation < .25 or light > .8 else value
    if property_name not in ('color', 'fill', 'stroke'):
        return value
    if light > .9:  # White labels on existing maroon/semantic buttons stay white.
        return value
    if saturation < .3 or max(rgb) - min(rgb) < .10:
        return 'var(--theme-text)' if light < .3 else 'var(--theme-muted)'
    if light > .72:
        return value
    if hue < .06 or hue > .9:
        return 'var(--theme-brand-text)'
    if hue < .16:
        return '#edb47e'
    if hue < .48:
        return '#a4d9b7'
    if hue < .75:
        return '#a9cfee'
    return '#d0bdeb'


def transform(tokens, property_name):
    result = []
    for token in tokens:
        # Existing variables resolve through dark root tokens, preserving fallbacks.
        if token.type == 'function' and token.lower_name == 'var':
            variable = tinycss2.serialize(token.arguments).strip()
            if property_name in ('color', 'fill', 'stroke') and re.match(r'--(?:primary-red|workspace-maroon)(?:-hover|-dark)?(?:\s*,|$)', variable):
                result.append('var(--theme-brand-text)')
            else:
                result.append(tinycss2.serialize([token]))
        elif token.type == 'hash':
            result.append(color('#' + token.value, property_name))
        elif token.type == 'ident' and token.value.lower() in ('white', 'black'):
            result.append(color('#ffffff' if token.value.lower() == 'white' else '#000000', property_name))
        elif token.type == 'function' and token.lower_name in ('rgb', 'rgba') and property_name.startswith('background'):
            numbers = [part.value for part in token.arguments if part.type == 'number']
            if len(numbers) in (3, 4) and all(0 <= part <= 255 for part in numbers[:3]) and (len(numbers) == 3 or numbers[3] >= .5):
                original_color = '#' + ''.join(f'{round(part):02x}' for part in numbers[:3])
                mapped = color(original_color, property_name)
                result.append(mapped if mapped != original_color else tinycss2.serialize([token]))
            else:
                result.append(tinycss2.serialize([token]))
        elif token.type == 'function' and token.lower_name not in ('url', 'rgb', 'rgba', 'hsl', 'hsla'):
            result.append(token.name + '(' + transform(token.arguments, property_name) + ')')
        else:
            result.append(tinycss2.serialize([token]))
    return ''.join(result)


def compile_rules(rules, prefix):
    output = []
    for rule in rules:
        if rule.type == 'at-rule' and rule.lower_at_keyword in ('media', 'supports', 'container', 'layer') and rule.content:
            inner = compile_rules(tinycss2.parse_rule_list(rule.content, skip_comments=True, skip_whitespace=True), prefix)
            if inner:
                output.append('@' + rule.at_keyword + ' ' + tinycss2.serialize(rule.prelude).strip() + '{\n' + inner + '\n}')
        elif rule.type == 'qualified-rule':
            declarations = []
            for declaration in tinycss2.parse_declaration_list(rule.content, skip_comments=True, skip_whitespace=True):
                if declaration.type != 'declaration' or declaration.name.startswith('--'):
                    continue
                if not (declaration.lower_name.startswith(('background', 'border')) or declaration.lower_name in ('color', 'fill', 'stroke', 'outline', 'outline-color')):
                    continue
                original = tinycss2.serialize(declaration.value).strip()
                changed = transform(declaration.value, declaration.lower_name).strip()
                if changed != original:
                    declarations.append(declaration.name + ':' + changed + ('!important' if declaration.important else '') + ';')
            if declarations:
                # Token-level splitting keeps commas inside :is()/attribute selectors intact.
                groups, group = [], []
                for token in rule.prelude:
                    if token.type == 'literal' and token.value == ',':
                        groups.append(tinycss2.serialize(group).strip()); group = []
                    else:
                        group.append(token)
                groups.append(tinycss2.serialize(group).strip())
                selectors = []
                for selector in groups:
                    if selector == ':root':
                        continue
                    if ' body[data-page-css=' in prefix and re.match(r'^body\b', selector):
                        selector = re.sub(r'^body\b', prefix, selector)
                    elif prefix.endswith(']') and selector.startswith('html'):
                        selector = re.sub(r'^html\b', prefix, selector)
                    else:
                        selector = prefix + ' ' + selector
                    selectors.append(selector)
                if selectors:
                    output.append(','.join(selectors) + '{' + ''.join(declarations) + '}')
    return '\n'.join(output)


BASE = '''/* Generated color rules: python scripts/build_theme_styles.py */
:root { color-scheme: light; }
html[data-theme="dark"] {
 color-scheme: dark;
 --theme-surface:#1b2027; --theme-raised:#252c35; --theme-warm:#352329;
 --theme-text:#edf0f5; --theme-muted:#b8c0cc; --theme-border:#414b58;
 --theme-brand-text:#ffb4b4;
 --bg-main:#13171d; --bg-alt:#13171d; --bg-card:var(--theme-surface);
 --bg-input:var(--theme-raised); --bg-soft-red:var(--theme-warm);
 --text-dark:var(--theme-text); --text-black:var(--theme-text);
 --text-gray:var(--theme-muted); --text-light-gray:var(--theme-muted);
 --border-color:var(--theme-border);
 --shadow-sm:0 4px 20px rgba(0,0,0,.16); --shadow-md:0 8px 25px rgba(0,0,0,.22);
}
html[data-theme="dark"] body { background:var(--bg-main); color:var(--theme-text); }
.theme-toggle { display:flex; align-items:center; justify-content:center; gap:10px; width:100%; min-height:44px; margin-bottom:12px; padding:10px 14px; border:1px solid var(--border-color); border-radius:10px; background:var(--bg-card); color:var(--text-dark); font:inherit; font-size:13px; font-weight:600; cursor:pointer; }
.theme-toggle:hover { background:var(--bg-soft-red); }
.theme-toggle:focus-visible { outline:3px solid var(--accent-orange); outline-offset:3px; }
.theme-toggle i { color:var(--accent-orange); }
.theme-toggle { transition:transform 180ms ease; }
.theme-toggle:active { transform:scale(.96); }
html[data-theme-animating="true"] .theme-toggle i { animation:theme-icon-turn 520ms cubic-bezier(.2,.7,.2,1); }
@keyframes theme-icon-turn { from { transform:rotate(-65deg) scale(.7); opacity:.35; } to { transform:rotate(0) scale(1); opacity:1; } }
@keyframes theme-fade-out { to { opacity:0; } }
@keyframes theme-fade-in { from { opacity:0; } }
::view-transition-old(root) { animation:theme-fade-out 520ms ease both; }
::view-transition-new(root) { animation:theme-fade-in 520ms ease both; }
html[data-theme-transition="colors"] body,html[data-theme-transition="colors"] body * { transition-property:background-color,color,border-color,box-shadow; transition-duration:480ms; transition-timing-function:ease; }
@media(prefers-reduced-motion:reduce) {
 .theme-toggle { transition:none; }
 html[data-theme-animating="true"] .theme-toggle i,::view-transition-old(root),::view-transition-new(root) { animation:none; }
 html[data-theme-animating="true"] body,html[data-theme-animating="true"] body * { transition:none; }
}
.theme-toggle-auth { position:fixed; top:16px; right:20px; z-index:20; width:auto; box-shadow:var(--shadow-sm); }
@media(min-width:851px) { .sidebar.is-collapsed .theme-toggle { padding:10px 0; } .sidebar.is-collapsed .theme-toggle span { display:none; } }
@media(max-width:600px) { .theme-toggle-auth { top:10px; right:12px; width:44px; padding:10px; } .theme-toggle-auth span { display:none; } }
'''


def build():
    parts = [BASE]
    # Match the existing head order; page rules apply only to their own page.
    names = ['index.css', 'root.css', 'app-dialog.css', 'app.css']
    names += sorted(p.name for p in CSS.glob('*.css') if p.name not in SHARED | {'theme.css', 'request-error.css'})
    for name in names:
        prefix = 'html[data-theme="dark"]'
        if name not in SHARED:
            prefix += ' body[data-page-css="' + name + '"]'
        source = (CSS / name).read_text(encoding='utf-8-sig')
        compiled = compile_rules(tinycss2.parse_stylesheet(source, skip_comments=True, skip_whitespace=True), prefix)
        parts.append('/* ' + name + ' */\n' + compiled)
    parts.append('''html[data-theme="dark"] :is(input,textarea,select) { color:var(--theme-text); border-color:var(--theme-border); }
/* The school crest is artwork with a white backing, not a themed page surface. */
html[data-theme="dark"] body :is(.auth-logo img,.public-home-brand-card img,.sidebar-logo img) { background:#fff!important; }
/* Switch thumbs must contrast with both enabled and disabled tracks. */
html[data-theme="dark"] body[data-page-css="notifications.css"] .notifications-toggle-control,
html[data-theme="dark"] body[data-page-css="notifications.css"] .notification-category-channel > span { background:#747e8b; }
html[data-theme="dark"] body[data-page-css="notifications.css"] .notifications-toggle-control::after,
html[data-theme="dark"] body[data-page-css="notifications.css"] .notification-category-channel > span::after { background:#fff!important; }
html[data-theme="dark"] body[data-page-css="notifications.css"] .notifications-toggle input:checked + .notifications-toggle-control,
html[data-theme="dark"] body[data-page-css="notifications.css"] .notification-category-channel > input[type="checkbox"]:checked + span { background:var(--primary-red); border-color:var(--theme-brand-text); }
html[data-theme="dark"] :is(input,textarea)::placeholder { color:var(--theme-muted); opacity:1; }
html[data-theme="dark"] :is(a,button,input,textarea,select,summary):focus-visible { outline-color:var(--accent-orange); }
html[data-theme="dark"] .sidebar-brand-text strong { color:var(--theme-brand-text); }
@media print { html[data-theme="dark"] { color-scheme:light; } }
''')
    (CSS / 'theme.css').write_text('\n'.join(parts), encoding='utf-8')
    print('Built scoped dark-mode styles; light CSS unchanged.')


if __name__ == '__main__':
    build()
