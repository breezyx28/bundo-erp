#!/usr/bin/env python3
import pathlib
import re

ROOT = pathlib.Path(__file__).resolve().parents[1]

# Fix Ui PHP components
ui_dir = ROOT / "app/View/Components/Ui"
for f in ui_dir.glob("*.php"):
    t = f.read_text(encoding="utf-8")
    t = t.replace('"mary"', '"ui"')
    t = t.replace("'mary'", "'ui'")
    t = t.replace("mary-toaster", "ui-toaster")
    t = t.replace("mary-toast", "ui-toast")
    t = t.replace("mary-table-pagination", "ui-table-pagination")
    t = t.replace("mary-choices-element", "ui-choices-element")
    t = t.replace("maryCrop", "uiCrop")
    t = t.replace("mary-header-anchor", "ui-header-anchor")
    t = t.replace("mary-hideable", "ui-hideable")
    t = t.replace("mary-active-menu", "ui-active-menu")
    f.write_text(t, encoding="utf-8")

HEROICON = re.compile(
    r"<x-heroicon-o-([\w-]+)([^>]*)/>",
    re.IGNORECASE,
)


def migrate_blade(content: str) -> str:
    content = re.sub(r"x-mary-([\w-]+)", r"x-ui.\1", content)
    content = content.replace("Mary\\Traits\\Toast", "App\\Traits\\UiToast")
    content = content.replace("use Toast, ", "use UiToast, ")
    content = content.replace("use Toast;", "use UiToast;")
    content = HEROICON.sub(r'<x-ui.icon name="o-\1"\2/>', content)
    content = content.replace(
        "<x-dynamic-component :component=\"'heroicon-o-' . $item['icon']\" class=\"size-4 shrink-0\" />",
        "<x-ui.icon :name=\"$item['icon']\" class=\"size-4 shrink-0\" />",
    )
    return content


for path in (ROOT / "resources/views").rglob("*.blade.php"):
    original = path.read_text(encoding="utf-8")
    updated = migrate_blade(original)
    if updated != original:
        path.write_text(updated, encoding="utf-8")
        print("updated", str(path.relative_to(ROOT)).encode("ascii", "replace").decode())

print("migration done")
