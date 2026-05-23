#!/usr/bin/env python3
"""Normalize homepage brand SVGs to a shared optical vertical centre."""

from __future__ import annotations

import re
import subprocess
import tempfile
from pathlib import Path

from PIL import Image

TARGET_HEIGHT = 48
TARGET_COM_Y = (TARGET_HEIGHT - 1) / 2

SOURCES = {
    "schuh.svg": Path("/Users/admin/Work/Society/Culvers/app/public/wp-content/uploads/2026/05/schuh-v20260520.svg"),
    "accessorize-london.svg": Path("/Users/admin/Work/Society/Culvers/app/public/wp-content/uploads/2026/05/accessorize-london-figma.svg"),
    "hm.svg": Path("/Users/admin/Work/Society/Culvers/app/public/wp-content/uploads/2026/05/hm-v20260520.svg"),
    "pandora.svg": Path("/Users/admin/Work/Society/Culvers/app/public/wp-content/uploads/2026/05/pandora-v20260520.svg"),
    "tk-maxx.svg": Path("/Users/admin/Work/Society/Culvers/app/public/wp-content/uploads/2026/05/tk-maxx-v20260520.svg"),
}

OUT_DIR = Path(__file__).resolve().parent.parent / "resources/images/homepage-brands"


def center_of_mass(svg_path: Path) -> float:
    with tempfile.NamedTemporaryFile(suffix=".png", delete=False) as tmp:
        png = Path(tmp.name)
    subprocess.run(
        ["magick", "-background", "none", str(svg_path), "-resize", f"x{TARGET_HEIGHT}", str(png)],
        check=True,
        capture_output=True,
    )
    im = Image.open(png).convert("RGBA")
    png.unlink(missing_ok=True)
    w, h = im.size
    mass = 0.0
    cy = 0.0
    for y in range(h):
        for x in range(w):
            a = im.getpixel((x, y))[3]
            if a > 16:
                mass += a
                cy += y * a
    return cy / mass if mass else TARGET_COM_Y


def svg_inner_markup(text: str) -> str:
    inner = re.sub(r"^<svg[^>]*>", "", text, count=1, flags=re.S)
    return re.sub(r"</svg>\s*$", "", inner, flags=re.S).strip()


def write_normalized(src: Path, dest: Path) -> None:
    """Iteratively pan viewBox until optical centre lands on target."""
    text = src.read_text(encoding="utf-8")
    vb = [float(v) for v in re.search(r'viewBox="([^"]+)"', text).group(1).split()]
    inner = svg_inner_markup(text)
    pan_y = 0.0

    with tempfile.NamedTemporaryFile(suffix=".svg", delete=False, mode="w", encoding="utf-8") as tmp:
        tmp_path = Path(tmp.name)

    for _ in range(8):
        new_vb_y = vb[1] - pan_y
        tmp_path.write_text(
            f'<svg xmlns="http://www.w3.org/2000/svg" '
            f'viewBox="{vb[0]:.4f} {new_vb_y:.4f} {vb[2]:.4f} {vb[3]:.4f}" '
            f'width="{vb[2]:.2f}" height="{vb[3]:.2f}" fill="none" overflow="visible">\n'
            f"{inner}\n</svg>\n",
            encoding="utf-8",
        )
        com = center_of_mass(tmp_path)
        dy = TARGET_COM_Y - com
        if abs(dy) < 0.35:
            break
        pan_y += dy * (vb[3] / TARGET_HEIGHT)

    final_vb_y = vb[1] - pan_y
    dest.write_text(
        f'<svg xmlns="http://www.w3.org/2000/svg" '
        f'viewBox="{vb[0]:.4f} {final_vb_y:.4f} {vb[2]:.4f} {vb[3]:.4f}" '
        f'width="{vb[2]:.2f}" height="{vb[3]:.2f}" fill="none" overflow="visible" aria-hidden="true">\n'
        f"{inner}\n</svg>\n",
        encoding="utf-8",
    )
    return center_of_mass(src), center_of_mass(dest)


def main() -> None:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    print(f"Target optical centre y={TARGET_COM_Y}px\n")
    for name, src in SOURCES.items():
        dest = OUT_DIR / name
        before, after = write_normalized(src, dest)
        print(f"{name:24} com {before:5.2f} -> {after:5.2f}")


if __name__ == "__main__":
    main()
