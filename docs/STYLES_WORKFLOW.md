# Styles workflow (Mazin Shoes)

## Where CSS comes from

| Source | Path | What it provides |
|--------|------|------------------|
| **FlyonUI plugin** | `@plugin "flyonui"` in `resources/css/app.css` | `.input`, `.btn`, `.table`, `.card`, `.menu`, `.dropdown`, etc. (compiled into `public/build/assets/app-*.css`) |
| **FlyonUI theme** | `@plugin "flyonui/theme"` (name: `mazin`) | Brand colors, radii, `--color-primary`, etc. |
| **FlyonUI variants** | `node_modules/flyonui/variants.css` | Theme variant utilities |
| **Iconify** | `@plugin "@iconify/tailwind4"` | `icon-[tabler--*]` classes |
| **Fonts** | `@font-face` in app.css + Vite Bunny plugin | Roboto (LTR), Cairo (RTL) |
| **Tenant override** | Inline `<style>` in `layouts/app.blade.php` | Dynamic `--color-primary` / `--color-secondary` |
| **ApexCharts vendor** | `flyonui/src/vendor/apexcharts.css` | Chart tooltips only |

There is **no other app CSS file**. If DevTools shows `.input { … }`, it is from the compiled FlyonUI bundle, not missing from `app.css`.

## Build pipeline

```
resources/css/app.css  →  Vite + Tailwind v4  →  public/build/assets/app-*.css
resources/js/app.js    →  Vite               →  public/build/assets/app-*.js (+ flyonui/flyonui JS)
```

**Required:** run `npm run dev` (development) or `npm run build` (production) alongside `php artisan serve`.

Clear caches when stale:

```bash
php artisan optimize:clear
npm run build
```

Hard refresh browser (`Ctrl+Shift+R`).

## UI components (`app/View/Components/Ui/`)

PHP wrappers render FlyonUI **class names** in Blade. They must not add Tailwind utilities that override FlyonUI sizing, borders, or shadows on semantic classes (`.input`, `.btn`, `.modal-box`, etc.).

Use FlyonUI markup patterns from https://flyonui.com/docs — e.g. `<div class="input"><input class="grow" /></div>`, not `<label class="input flex h-10 border-…">`.

## Legacy classes removed (no CSS in app.css)

These were Mary-era or custom overrides. They are **not** FlyonUI and must not be used:

| Class / pattern | Was used in | Replaced with |
|-----------------|-------------|---------------|
| `ui-active-menu` | MenuItem | `dropdown-item` + `menu-active` |
| `ui-hideable` | ListItem, MenuItem | removed (no stylesheet) |
| `ui-page-header` | Header (removed) | FlyonUI page layout utilities |
| `mazin-card`, `mazin-stat`, `mazin-surface` | Card, Stat (removed) | `card`, FlyonUI stat block |
| `mary-modal-open` event | data-tools | `document.getElementById(id).showModal()` |
| Extra utilities on `.input` / `.btn` | topbar search, forms | semantic FlyonUI class only + `grow` on inner control |

## Mary-era PHP wrappers (`app/View/Components/Ui/`)

Components still render FlyonUI **class names**  but markup must match FlyonUI docs:

- **Input / Password / Select**: `div.input` or `div.select` wrapper, inner `input.grow` / `select.grow`
- **Dropdown**: `dropdown` + `dropdown-toggle` on trigger + `dropdown-menu` list
- **MenuItem** (in dropdowns): `dropdown-item` on `<a>` or `<button>`

Do not stack Tailwind border/height/flex utilities on semantic FlyonUI classes — that overrides the theme.
