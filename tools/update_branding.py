import shutil
import sqlite3
import os

src_logo = r"C:\Users\DKV-25\.gemini\antigravity-ide\brain\ab7e1379-e367-4ff0-9b4f-ecc973217e5a\.user_uploaded\media_1792631976333.png"
src_kop = r"C:\Users\DKV-25\.gemini\antigravity-ide\brain\ab7e1379-e367-4ff0-9b4f-ecc973217e5a\.user_uploaded\media_1792631984502.png"

# 1. Copy logo
os.makedirs("assets/img", exist_ok=True)
os.makedirs("storage/uploads/logo", exist_ok=True)
shutil.copy2(src_logo, "assets/img/logo.png")
shutil.copy2(src_logo, "storage/uploads/logo/logo_default.png")
shutil.copy2(src_logo, "storage/uploads/logo/logo_1792025656.png")

# 2. Copy kop
os.makedirs("storage/uploads/kop", exist_ok=True)
shutil.copy2(src_kop, "assets/img/kop-header.png")
shutil.copy2(src_kop, "storage/uploads/kop/kop_default.png")

# 3. Update SQLite
conn = sqlite3.connect("database/sas.sqlite")
c = conn.cursor()
c.execute("UPDATE pengaturan SET setting_value = 'assets/img/kop-header.png' WHERE setting_key = 'kop_image_path'")
c.execute("UPDATE pengaturan SET setting_value = 'storage/uploads/logo/logo_default.png' WHERE setting_key = 'logo_path'")
conn.commit()
conn.close()

print("Successfully replaced logo and kop!")
