#!/usr/bin/env python3
"""
Genera las variantes responsive de las fotos y logos (AVIF, WebP y JPG/PNG)
y escribe public/assets/img/manifest.json, que lee el helper PHP img().

Uso (en local, no en el hosting):
    pip install pillow pillow-avif-plugin
    python3 tools/build-images.py

Fuente: docs/entrega/recursos/{fotos,marca}. Nunca se amplía una imagen:
las anchuras de srcset que superan el original se descartan y se añade la
anchura original.
"""
import json
import os
import sys

from PIL import Image

try:  # Pillow >= 11.2 trae AVIF; si no, el plugin lo registra
    import pillow_avif  # noqa: F401
except ImportError:
    pass

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SRC = os.path.join(ROOT, "docs", "entrega", "recursos")
OUT = os.path.join(ROOT, "public", "assets", "img")
WIDTHS = [480, 768, 1200, 1600]

# Logos: solo los que usa el sitio, reducidos (los originales de 4500 px no se publican)
BRAND = {
    "isotipo-negativo.png": [80, 160],
    "isotipo-salmon.png": [120, 240],
    "isotipo-nude.png": [360],
    "logo-nude.png": [336, 700],
    "logo-salmon.png": [336, 700],
    "wordmark-nude.png": [450, 900],
    "wordmark-salmon.png": [450, 900],
}


def build_photo(name, manifest):
    src = os.path.join(SRC, "fotos", name)
    im = Image.open(src).convert("RGB")
    w0, h0 = im.size
    stem = os.path.splitext(name)[0]
    widths = sorted({w for w in WIDTHS if w < w0} | {w0})
    for w in widths:
        h = round(h0 * w / w0)
        r = im if w == w0 else im.resize((w, h), Image.LANCZOS)
        base = os.path.join(OUT, "fotos", f"{stem}-{w}")
        r.save(base + ".jpg", "JPEG", quality=80, optimize=True, progressive=True)
        r.save(base + ".webp", "WEBP", quality=78, method=6)
        r.save(base + ".avif", "AVIF", quality=60, speed=4)
    manifest[f"fotos/{stem}"] = {"w": w0, "h": h0, "widths": widths, "ext": "jpg"}


def build_brand(name, widths, manifest):
    src = os.path.join(SRC, "marca", name)
    im = Image.open(src).convert("RGBA")
    w0, h0 = im.size
    stem = os.path.splitext(name)[0]
    widths = sorted({min(w, w0) for w in widths})
    for w in widths:
        h = round(h0 * w / w0)
        r = im if w == w0 else im.resize((w, h), Image.LANCZOS)
        base = os.path.join(OUT, "marca", f"{stem}-{w}")
        r.save(base + ".png", "PNG", optimize=True)
        r.save(base + ".webp", "WEBP", quality=88, method=6)
    manifest[f"marca/{stem}"] = {"w": w0, "h": h0, "widths": widths, "ext": "png"}


def main():
    os.makedirs(os.path.join(OUT, "fotos"), exist_ok=True)
    os.makedirs(os.path.join(OUT, "marca"), exist_ok=True)
    manifest = {}
    for name in sorted(os.listdir(os.path.join(SRC, "fotos"))):
        if name.lower().endswith((".jpg", ".jpeg", ".png")):
            build_photo(name, manifest)
            print("foto", name)
    for name, widths in BRAND.items():
        build_brand(name, widths, manifest)
        print("marca", name)
    # favicon / apple-touch a partir del isotipo salmón
    iso = Image.open(os.path.join(SRC, "marca", "isotipo-salmon.png")).convert("RGBA")
    iso.resize((180, 180), Image.LANCZOS).save(os.path.join(OUT, "apple-touch-icon.png"), optimize=True)
    iso.resize((32, 32), Image.LANCZOS).save(os.path.join(OUT, "favicon-32.png"), optimize=True)
    iso.save(os.path.join(ROOT, "public", "favicon.ico"), sizes=[(16, 16), (32, 32), (48, 48)])
    # imagen Open Graph 1200x630 con la foto del hero
    hero = Image.open(os.path.join(SRC, "fotos", "catalogo-modelo-hero.jpg")).convert("RGB")
    hw, hh = hero.size
    crop_h = round(hw * 630 / 1200)
    top = round(hh * 0.18)
    og = hero.crop((0, top, hw, top + crop_h)).resize((1200, 630), Image.LANCZOS)
    og.save(os.path.join(OUT, "og-default.jpg"), "JPEG", quality=82, optimize=True)
    with open(os.path.join(OUT, "manifest.json"), "w", encoding="utf-8") as fh:
        json.dump(manifest, fh, indent=1, sort_keys=True)
    print("manifest", len(manifest))


if __name__ == "__main__":
    sys.exit(main())
