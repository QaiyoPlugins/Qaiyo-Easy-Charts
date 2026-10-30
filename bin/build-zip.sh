#!/usr/bin/env bash
# Builds the WordPress.org release: build/qaiyo-easy-charts/ and build/qaiyo-easy-charts.zip.
# Development files listed in .distignore are left out.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SLUG="qaiyo-easy-charts"
OUT="$ROOT/build"
DEST="$OUT/$SLUG"

rm -rf "$OUT"
mkdir -p "$DEST"

# Copy everything except the build folder itself, then drop the ignored paths.
( cd "$ROOT" && tar --exclude=./build --exclude=./.git -cf - . ) | ( cd "$DEST" && tar -xf - )

while IFS= read -r line; do
	[[ -z "$line" || "$line" == \#* ]] && continue
	rm -rf "${DEST:?}/${line#/}"
done < "$ROOT/.distignore"

( cd "$OUT" && zip -qr "$SLUG.zip" "$SLUG" )
echo "Built $OUT/$SLUG.zip"
