import urllib.request
import json

classes = [5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15]
data = {}
total_students = 0

for cid in classes:
    url = f"https://sas.doantara.my.id/api/get-students.php?class_id={cid}"
    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
    try:
        with urllib.request.urlopen(req) as resp:
            res = json.loads(resp.read().decode('utf-8'))
            data[cid] = res
            st_count = len(res.get('students', []))
            total_students += st_count
            cinfo = res.get('class', {})
            print(f"Class {cid} ({cinfo.get('nama_kelas')}): {st_count} students")
    except Exception as e:
        print(f"Error {cid}: {e}")

print(f"Total students fetched: {total_students}")
with open('seed_data.json', 'w', encoding='utf-8') as f:
    json.dump(data, f, ensure_ascii=False, indent=2)
print("Saved seed_data.json successfully!")
