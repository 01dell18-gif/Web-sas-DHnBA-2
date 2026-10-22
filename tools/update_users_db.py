import sqlite3

conn = sqlite3.connect('database/sas.sqlite')
cursor = conn.cursor()
cols = [r[1] for r in cursor.execute('PRAGMA table_info(users)').fetchall()]
if 'password_plain' not in cols:
    cursor.execute("ALTER TABLE users ADD COLUMN password_plain VARCHAR(255) DEFAULT ''")
    print("Column password_plain added.")
else:
    print("Column password_plain already exists.")

cursor.execute("UPDATE users SET password_plain = 'admin123' WHERE username = 'admin'")
cursor.execute("UPDATE users SET password_plain = 'operator123' WHERE username = 'operator'")
conn.commit()
print("Current users:", cursor.execute("SELECT id, username, password_plain, role FROM users").fetchall())
conn.close()
