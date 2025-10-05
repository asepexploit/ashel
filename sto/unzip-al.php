import os
import math
import json
import re
import shutil
import glob
from datetime import datetime
from urllib.parse import quote, urlparse, quote_plus

MAX_URLS_PER_SITEMAP = 10000
INPUT_FILE = "index.txt"
SITEMAP_INDEX_FILE = "sitemap_index.xml"
DB_FILE = "../db/db.json"

def create_backup_folder(url):
    """Membuat folder backup berdasarkan URL domain"""
    try:
        parsed_url = urlparse(url)
        domain = parsed_url.netloc
        path = parsed_url.path.strip('/')
        
        # Buat struktur folder backup
        if path:
            backup_path = os.path.join("backup", domain, path)
        else:
            backup_path = os.path.join("backup", domain)
        
        os.makedirs(backup_path, exist_ok=True)
        print(f"📁 Folder backup dibuat: {backup_path}")
        return backup_path
    except Exception as e:
        print(f"Error membuat folder backup: {e}")
        return None

def backup_files(backup_path):
    """Backup file-file penting ke folder backup"""
    files_to_backup = [
        "index.php",
        "index.txt", 
        "images.txt",
        "googleb1b0330dcdd27caf.html",
        "googlef5b613abe7fbe139.html"
    ]
    
    backed_up_files = []
    
    for file in files_to_backup:
        if os.path.exists(file):
            try:
                shutil.copy2(file, backup_path)
                backed_up_files.append(file)
                print(f"✓ {file} berhasil di-backup")
            except Exception as e:
                print(f"⚠ Error backup {file}: {e}")
        else:
            print(f"⚠ File {file} tidak ditemukan, skip backup")
    
    # Backup semua sitemap files
    sitemap_files = []
    for file in os.listdir('.'):
        if file.startswith('sitemap_') and file.endswith('.xml'):
            sitemap_files.append(file)
        elif file == 'sitemap_index.xml':
            sitemap_files.append(file)
    
    for sitemap_file in sitemap_files:
        try:
            shutil.copy2(sitemap_file, backup_path)
            backed_up_files.append(sitemap_file)
            print(f"✓ {sitemap_file} berhasil di-backup")
        except Exception as e:
            print(f"⚠ Error backup {sitemap_file}: {e}")
    
    return backed_up_files

def zip_backup_domain(backup_path, domain=None):
    """Buat zip di dalam folder backup_path yang hanya berisi file-file (tanpa struktur folder).

    Contoh: jika backup_path = 'backup/www.domain.com/assets', maka zip akan dibuat
    di 'backup/www.domain.com/assets/www.domain.com.zip' dan hanya berisi file-file yang ada
    langsung di folder assets.
    """
    import zipfile
    try:
        # pastikan path normal
        backup_path = os.path.normpath(backup_path)

        # Tentukan domain untuk nama zip: gunakan domain param jika ada, atau ambil dari struktur path
        domain_name = domain
        parts = backup_path.split(os.sep)
        if not domain_name and 'backup' in parts:
            b_index = parts.index('backup')
            if len(parts) > b_index + 1:
                domain_name = parts[b_index + 1]

        # Jika masih None, fallback ke nama folder terakhir
        if not domain_name:
            domain_name = os.path.basename(backup_path)

        if not os.path.exists(backup_path):
            print(f"⚠ Folder backup tidak ditemukan: {backup_path}")
            return None

        # Nama file zip di dalam folder backup_path
        zip_filename = f"{domain_name}.zip"
        zip_fullpath = os.path.join(backup_path, zip_filename)

        # Buat zip dan tambahkan hanya file-file yang ada langsung di backup_path
        try:
            with zipfile.ZipFile(zip_fullpath, 'w', compression=zipfile.ZIP_DEFLATED) as zf:
                for entry in os.listdir(backup_path):
                    full_entry = os.path.join(backup_path, entry)
                    # skip the zip file itself dan skip subfolders
                    if entry == zip_filename:
                        continue
                    if os.path.isfile(full_entry):
                        # arcname hanya nama file (tanpa folder)
                        zf.write(full_entry, arcname=os.path.basename(full_entry))
            print(f"📦 Backup di-zip menjadi: {zip_fullpath}")
        except Exception:
            # Fallback: coba shutil.make_archive dari folder (akan menyertakan struktur)
            try:
                base_name = os.path.join(backup_path, domain_name)
                archive_path = shutil.make_archive(base_name, 'zip', root_dir=backup_path)
                print(f"📦 (Fallback) Backup di-zip menjadi: {archive_path}")
                zip_fullpath = archive_path
            except Exception:
                # Final fallback: coba pake 7z jika tersedia
                try:
                    import subprocess
                    # buat nama zip di luar folder untuk menghindari self-inclusion
                    outer_zip = os.path.join(backup_path, zip_filename)
                    cmd = ['7z', 'a', '-tzip', outer_zip, os.path.join(backup_path, '*')]
                    subprocess.run(cmd, check=True)
                    print(f"📦 (7z) Backup di-zip menjadi: {outer_zip}")
                    zip_fullpath = outer_zip
                except Exception as e:
                    print(f"Error membuat zip backup dengan semua metode: {e}")
                    zip_fullpath = None

        # Setelah proses pembuatan archive, scan folder untuk menemukan arsip lain
        archives = []
        for pattern in ('*.zip', '*.rar', '*.7z', '*.tar.gz', '*.tgz'):
            for f in sorted(glob.glob(os.path.join(backup_path, pattern))):
                archives.append(f)
        if archives:
            print("🔍 Arsip yang ditemukan di folder backup:")
            for a in archives:
                print(f" - {a}")

        return zip_fullpath
    except Exception as e:
        print(f"Error membuat zip backup: {e}")
        return None

def read_keywords(filepath):
    with open(filepath, "r", encoding="utf-8") as f:
        return [line.strip() for line in f if line.strip()]

def read_db_entries():
    """Membaca entries dari db.json"""
    try:
        with open(DB_FILE, "r", encoding="utf-8") as f:
            return json.load(f)
    except FileNotFoundError:
        print(f"File {DB_FILE} tidak ditemukan.")
        return []
    except json.JSONDecodeError:
        print(f"Error membaca JSON dari {DB_FILE}")
        return []

def update_index_php(title, description):
    """Update title dan description di index.php"""
    try:
        with open("index.php", "r", encoding="utf-8") as f:
            content = f.read()
        
        # Cari dan ganti $title = "";
        title_pattern = r'(\$title\s*=\s*")[^"]*(";\s*)'
        content = re.sub(title_pattern, r'\1' + title + r'\2', content)
        
        # Cari dan ganti $desc = "";
        desc_pattern = r'(\$desc\s*=\s*")[^"]*(";\s*)'
        content = re.sub(desc_pattern, r'\1' + description + r'\2', content)
        
        with open("index.php", "w", encoding="utf-8") as f:
            f.write(content)
        
        print(f"✓ index.php berhasil diupdate dengan title dan description baru")
        return True
    except Exception as e:
        print(f"Error updating index.php: {e}")
        return False

def auto_update_from_db():
    """Update otomatis dari database tanpa pilih-pilih"""
    entries = read_db_entries()
    if not entries:
        print("⚠ Tidak ada entry di database, skip update title & description.")
        return False
    
    # Ambil entry pertama
    selected_entry = entries[0]
    title = selected_entry.get('title', '')
    description = selected_entry.get('description', '')
    
    print(f"📝 Menggunakan entry: {title[:50]}...")
    
    if update_index_php(title, description):
        if remove_used_entry_from_db(0):
            print("✓ Title & description berhasil diupdate dan entry dihapus dari database")
            return True
        else:
            print("⚠ Update berhasil tapi gagal menghapus entry dari database")
            return True
    else:
        print("✗ Gagal mengupdate index.php")
        return False

def remove_used_entry_from_db(used_index):
    """Hapus entry yang sudah digunakan dari db.json"""
    try:
        with open(DB_FILE, "r", encoding="utf-8") as f:
            entries = json.load(f)
        
        if 0 <= used_index < len(entries):
            removed_entry = entries.pop(used_index)
            
            with open(DB_FILE, "w", encoding="utf-8") as f:
                json.dump(entries, f, indent=2, ensure_ascii=False)
            
            print(f"✓ Entry berhasil dihapus dari db.json")
            return True
        else:
            print(f"Index {used_index} tidak valid")
            return False
    except Exception as e:
        print(f"Error removing entry from db.json: {e}")
        return False

def process_db_update():
    """Proses update dari database JSON"""
    print("\n=== UPDATE DARI DATABASE ===")
    
    entries = read_db_entries()
    if not entries:
        print("Tidak ada entry di database atau file tidak dapat dibaca.")
        return False
    
    print(f"Ditemukan {len(entries)} entry di database:")
    for i, entry in enumerate(entries):
        title_preview = entry.get('title', '')[:50] + "..." if len(entry.get('title', '')) > 50 else entry.get('title', '')
        print(f"{i + 1}. {title_preview}")
    
    try:
        choice = int(input(f"\nPilih entry yang akan digunakan (1-{len(entries)}): ")) - 1
        if 0 <= choice < len(entries):
            selected_entry = entries[choice]
            
            title = selected_entry.get('title', '')
            description = selected_entry.get('description', '')
            
            print(f"\nTitle: {title}")
            print(f"Description: {description}")
            
            confirm = input("\nLanjutkan update? (y/n): ").lower()
            if confirm == 'y':
                if update_index_php(title, description):
                    if remove_used_entry_from_db(choice):
                        print("\n✓ Proses selesai! Title dan description telah diupdate, entry telah dihapus dari database.")
                        return True
                    else:
                        print("\n⚠ Update berhasil tapi gagal menghapus entry dari database.")
                        return True
                else:
                    print("\n✗ Gagal mengupdate index.php")
                    return False
            else:
                print("Update dibatalkan.")
                return False
        else:
            print("Pilihan tidak valid.")
            return False
    except ValueError:
        print("Input tidak valid. Masukkan angka.")
        return False
    except KeyboardInterrupt:
        print("\nProses dibatalkan.")
        return False

def generate_sitemap(urls, file_index):
    filename = f"sitemap_{file_index}.xml"
    with open(filename, "w", encoding="utf-8") as f:
        f.write('<?xml version="1.0" encoding="UTF-8"?>\n')
        f.write('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n')
        for url in urls:
            f.write("  <url>\n")
            f.write(f"    <loc>{url}</loc>\n")
            f.write(f"    <lastmod>{datetime.utcnow().isoformat()}Z</lastmod>\n")
            f.write("    <changefreq>weekly</changefreq>\n")
            f.write("    <priority>0.8</priority>\n")
            f.write("  </url>\n")
        f.write("</urlset>\n")
    return filename

def generate_sitemap_index(sitemap_files, base_url):
    with open(SITEMAP_INDEX_FILE, "w", encoding="utf-8") as f:
        f.write('<?xml version="1.0" encoding="UTF-8"?>\n')
        f.write('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n')
        for fname in sitemap_files:
            f.write("  <sitemap>\n")
            f.write(f"    <loc>{base_url.rstrip('/')}/{fname}</loc>\n")
            f.write(f"    <lastmod>{datetime.utcnow().isoformat()}Z</lastmod>\n")
            f.write("  </sitemap>\n")
        f.write("</sitemapindex>\n")

def main():
    """Langsung jalan tanpa pilih-pilih!"""
    print("=== SITEMAP GENERATOR & AUTO UPDATER ===")
    
    base_input = input("Masukkan URL (contoh: https://asep.com/site/): ").strip()
    if not base_input:
        print("ERROR: URL tidak boleh kosong.")
        return

    print(f"\n🚀 Memproses URL: {base_input}")
    
    # Step 1: Buat backup folder
    print("\n💾 Step 1: Membuat backup...")
    backup_path = create_backup_folder(base_input)
    if not backup_path:
        print("⚠ Gagal membuat folder backup, melanjutkan tanpa backup...")
    
    # Step 2: Auto update title & description dari database
    print("\n📝 Step 2: Auto update title & description...")
    auto_update_from_db()
    
    # Step 3: Generate sitemap
    print("\n📋 Step 3: Generate sitemap...")
    base_url = base_input.rstrip("/") + "/?thgenkshin="

    keywords = read_keywords(INPUT_FILE)
    total = len(keywords)
    chunks = math.ceil(total / MAX_URLS_PER_SITEMAP)

    sitemap_files = []
    for i in range(chunks):
        start = i * MAX_URLS_PER_SITEMAP
        end = start + MAX_URLS_PER_SITEMAP
        urls = [f"{base_url}{quote_plus(keyword)}" for keyword in keywords[start:end]]
        sitemap_file = generate_sitemap(urls, i + 1)
        sitemap_files.append(sitemap_file)

    generate_sitemap_index(sitemap_files, base_input)
    
    # Step 4: Backup semua file
    if backup_path:
        print(f"\n💾 Step 4: Backup files ke {backup_path}...")
        backed_up_files = backup_files(backup_path)
        print(f"✓ {len(backed_up_files)} file berhasil di-backup")
        # Buat zip dari folder domain
        try:
            parsed = urlparse(base_input)
            domain_name = parsed.netloc or None
        except Exception:
            domain_name = None

        zip_path = zip_backup_domain(backup_path, domain=domain_name)
        if zip_path:
            print(f"✓ Zip backup tersimpan di: {zip_path}")
    
    print(f"\n🎉 SELESAI!")
    print(f"✓ {len(sitemap_files)} sitemap files berhasil dibuat")
    print(f"✓ Sitemap index disimpan di: {SITEMAP_INDEX_FILE}")
    print(f"✓ Title & description sudah diupdate otomatis")
    if backup_path:
        print(f"✓ Backup tersimpan di: {backup_path}")

if __name__ == "__main__":
    main()
