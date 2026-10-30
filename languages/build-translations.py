#!/usr/bin/env python3
"""
Qaiyo Easy Charts — .pot / .po / .mo builder (Qaiyo i18n standard).

Extracts every translatable string from the plugin's PHP files and block.json,
writes the .pot template, then a .po and a binary .mo for every supported
locale from languages/i18n/<locale>.json. msgfmt is not needed.

Run from anywhere:  python3 languages/build-translations.py
Exit code 1 when a locale is missing a translation.
"""

import json
import os
import re
import struct
import sys
from collections import OrderedDict

DOMAIN = "qaiyo-easy-charts"
HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)

LOCALES = OrderedDict([
    ("hu_HU", ("hu", "nplurals=2; plural=(n != 1);")),
    ("de_DE", ("de", "nplurals=2; plural=(n != 1);")),
    ("fr_FR", ("fr", "nplurals=2; plural=(n > 1);")),
    ("es_ES", ("es", "nplurals=2; plural=(n != 1);")),
    ("ja", ("ja", "nplurals=1; plural=0;")),
    ("pt_PT", ("pt", "nplurals=2; plural=(n != 1);")),
    ("it_IT", ("it", "nplurals=2; plural=(n != 1);")),
    ("ru_RU", ("ru", "nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);")),
    ("tr_TR", ("tr", "nplurals=2; plural=(n > 1);")),
    ("pl_PL", ("pl", "nplurals=3; plural=(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);")),
])

FUNCS = r"(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)"
CALL = re.compile(FUNCS + r"\(\s*'((?:[^'\\]|\\.)*)'\s*,\s*'" + re.escape(DOMAIN) + r"'\s*\)")
CTX_SEP = "\x04"


def version():
    with open(os.path.join(ROOT, "qaiyo-easy-charts.php"), encoding="utf-8") as fh:
        match = re.search(r"Version:\s*([0-9.]+)", fh.read())
    return match.group(1) if match else "0.0.0"


def unescape_php(value):
    return value.replace("\\'", "'").replace("\\\\", "\\")


def extract():
    """msgid (or ctx\\x04msgid) -> list of 'file:line' references, in source order."""
    found = OrderedDict()
    for base, dirs, files in os.walk(ROOT):
        dirs[:] = sorted(d for d in dirs if d not in (".git", "node_modules", "vendor", "languages"))
        for name in sorted(files):
            if not name.endswith(".php"):
                continue
            path = os.path.join(base, name)
            rel = os.path.relpath(path, ROOT)
            with open(path, encoding="utf-8") as fh:
                source = fh.read()
            for match in CALL.finditer(source):
                line = source.count("\n", 0, match.start()) + 1
                found.setdefault(unescape_php(match.group(1)), []).append("%s:%d" % (rel, line))

    # block.json: WordPress translates these with fixed contexts.
    block = os.path.join(ROOT, "blocks", "chart", "block.json")
    with open(block, encoding="utf-8") as fh:
        meta = json.load(fh)
    rel = os.path.relpath(block, ROOT)
    found.setdefault("block title" + CTX_SEP + meta["title"], []).append(rel)
    found.setdefault("block description" + CTX_SEP + meta["description"], []).append(rel)
    for keyword in meta.get("keywords", []):
        found.setdefault("block keyword" + CTX_SEP + keyword, []).append(rel)
    return found


def po_escape(value):
    return value.replace("\\", "\\\\").replace('"', '\\"').replace("\n", "\\n").replace("\t", "\\t")


def header(lang, plural, ver):
    lines = [
        "Project-Id-Version: %s %s" % (DOMAIN, ver),
        "Report-Msgid-Bugs-To: https://qaiyo-plugins.com",
        "MIME-Version: 1.0",
        "Content-Type: text/plain; charset=UTF-8",
        "Content-Transfer-Encoding: 8bit",
        "X-Generator: Qaiyo build-translations.py",
        "X-Domain: %s" % DOMAIN,
    ]
    if lang:
        lines.insert(2, "Language: %s" % lang)
    if plural:
        lines.append("Plural-Forms: %s" % plural)
    return "\n".join(lines) + "\n"


def write_po(path, strings, translations, lang, plural, ver):
    out = ['msgid ""', 'msgstr ""']
    out += ['"%s\\n"' % po_escape(line) for line in header(lang, plural, ver).strip("\n").split("\n")]
    out.append("")
    for key, refs in strings.items():
        out.append("#: " + " ".join(refs))
        if CTX_SEP in key:
            ctx, msgid = key.split(CTX_SEP, 1)
            out.append('msgctxt "%s"' % po_escape(ctx))
        else:
            msgid = key
        out.append('msgid "%s"' % po_escape(msgid))
        out.append('msgstr "%s"' % po_escape(translations.get(key, "")))
        out.append("")
    with open(path, "w", encoding="utf-8") as fh:
        fh.write("\n".join(out))


def write_mo(path, translations, lang, plural, ver):
    entries = {"": header(lang, plural, ver)}
    entries.update({k: v for k, v in translations.items() if v})
    keys = sorted(entries.keys(), key=lambda k: k.encode("utf-8"))
    ids = [k.encode("utf-8") for k in keys]
    strs = [entries[k].encode("utf-8") for k in keys]

    count = len(keys)
    id_table = 7 * 4
    str_table = id_table + count * 8
    data = str_table + count * 8

    offsets_ids, offsets_strs, blob = [], [], b""
    for value in ids:
        offsets_ids.append((len(value), data + len(blob)))
        blob += value + b"\0"
    for value in strs:
        offsets_strs.append((len(value), data + len(blob)))
        blob += value + b"\0"

    out = struct.pack("<7I", 0x950412DE, 0, count, id_table, str_table, 0, 0)
    for length, offset in offsets_ids:
        out += struct.pack("<2I", length, offset)
    for length, offset in offsets_strs:
        out += struct.pack("<2I", length, offset)
    out += blob
    with open(path, "wb") as fh:
        fh.write(out)


def main():
    ver = version()
    strings = extract()
    write_po(os.path.join(HERE, DOMAIN + ".pot"), strings, {}, "", "", ver)

    missing_total = 0
    for locale, (lang, plural) in LOCALES.items():
        source = os.path.join(HERE, "i18n", locale + ".json")
        translations = {}
        if os.path.exists(source):
            with open(source, encoding="utf-8") as fh:
                translations = json.load(fh)
        missing = [k for k in strings if not translations.get(k)]
        unused = [k for k in translations if k not in strings]
        missing_total += len(missing)

        used = {k: v for k, v in translations.items() if k in strings}
        write_po(os.path.join(HERE, "%s-%s.po" % (DOMAIN, locale)), strings, used, lang, plural, ver)
        write_mo(os.path.join(HERE, "%s-%s.mo" % (DOMAIN, locale)), used, lang, plural, ver)
        print("%-6s %3d/%d translated%s" % (
            locale, len(strings) - len(missing), len(strings),
            ("  (%d unused)" % len(unused)) if unused else "",
        ))
        for key in missing:
            print("   missing: " + key.replace(CTX_SEP, " | "))

    print("%d strings" % len(strings))
    return 1 if missing_total else 0


if __name__ == "__main__":
    sys.exit(main())
