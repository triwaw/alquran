# 📖 AlQuran Project

A Laravel-based project to **normalize, store, and display Quranic text and translations**.  
This repository is both an **archive of my learning journey** and a **reference guide** for future projects.  

---

## 🌟 Features

- **Flat Quran Table → Normalized Schema**  
  Migrates a single legacy table (`quran`) into normalized tables:  
  - `surahs` → Surah details  
  - `verses` → Arabic text of verses  
  - `translations` → Multiple translations linked to verses  

- **Multiple Translations**  
  - Urdu (Fateh, Mehmood)  
  - English (Mohsin, Taqi)  
  - Easily extendable for more translators & languages  

- **Data Import Tools**  
  - Artisan command `quran:migrate-flat` to migrate existing `quran` table into normalized schema  
  - Seeder support  

- **Simple Web Interface**  
  - Input a **Surah number** and display the complete Surah with **Arabic text + translations**  

- **Tutorial Included**  
  - A step-by-step guide (HTML & PDF) for beginners to replicate this project from scratch  

---

## 🚀 Getting Started

### 1. Clone & Install
```bash
git clone https://github.com/your-username/alquran.git
cd alquran
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Database Setup
- Create a MySQL database (e.g. `alquran`)  
- Update `.env` with DB credentials  

### 3. Run Migrations & Seeders
```bash
php artisan migrate
php artisan db:seed
```

### 4. Migrate Flat Quran Table
If you already have a legacy `quran` table with Arabic text and translations:  
```bash
php artisan quran:migrate-flat
```

### 5. Serve & Access
```bash
php artisan serve
```
Visit `http://127.0.0.1:8000/quran/show?surah=1`

---

## 📂 Project Structure

- `app/Console/Commands/MigrateFlatQuran.php` → Migration command logic  
- `database/migrations/` → Surahs, Verses, Translations schema  
- `resources/views/quran/show.blade.php` → Display Surah + Translations  
- `database/data/` → Source JSON/SQL files (optional archive)  
- `docs/alquran_tutorial.pdf` → Beginner-friendly guide  

---

## 🔮 Future Plans

- Add search by **keyword or topic**  
- More translations (Urdu, English, other languages)  
- Tafseer integration  
- Audio recitations by reciters  

---

## 🙏 Acknowledgement

This project is a **personal learning effort** to better understand:  
- Laravel migrations & seeders  
- Database normalization  
- Artisan commands  
- Building small but meaningful apps with PHP/Laravel  

I hope this serves as a reference for myself and a guide for others exploring Quran-related software projects.  
