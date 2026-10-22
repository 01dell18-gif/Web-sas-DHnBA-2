import html
import json
import re

def extract(filepath):
    records = []
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Split by showRecordDetail(
    parts = content.split('showRecordDetail(')
    for p in parts[1:]:
        # Find closing paren that terminates the call
        # It's followed by )"
        idx = p.find(')"')
        if idx != -1:
            raw_json = p[:idx]
            unescaped = html.unescape(raw_json)
            try:
                rec = json.loads(unescaped)
                records.append(rec)
            except Exception as e:
                # Try finding up to last }
                last_brace = raw_json.rfind('}')
                if last_brace != -1:
                    try:
                        rec = json.loads(html.unescape(raw_json[:last_brace+1]))
                        records.append(rec)
                    except:
                        pass
    return records

absensi = extract("analysis_data/admin_absensi.php.html")
print(f"Absensi: {len(absensi)}")
ba = extract("analysis_data/admin_berita-acara.php.html")
print(f"Berita Acara: {len(ba)}")

with open("seed_records.json", "w", encoding="utf-8") as f:
    json.dump({
        "absensi": absensi,
        "berita_acara": ba
    }, f, ensure_ascii=False, indent=2)

print("Saved seed_records.json successfully!")
