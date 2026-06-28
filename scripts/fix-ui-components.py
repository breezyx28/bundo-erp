#!/usr/bin/env python3
import pathlib

for f in pathlib.Path("app/View/Components/Ui").glob("*.php"):
    t = f.read_text(encoding="utf-8")
    if "namespace Mary" in t:
        t = t.replace("namespace Mary\\View\\Components", "namespace App\\View\\Components\\Ui")
        t = t.replace("Mary\\View\\Components\\", "App\\View\\Components\\Ui\\")
    t = t.replace("x-mary-", "x-ui-")
    if '"mary"' in t and "uuid" in t:
        t = t.replace('"mary"', '"ui"')
    f.write_text(t, encoding="utf-8")

print("done")
