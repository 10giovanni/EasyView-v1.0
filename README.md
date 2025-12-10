# 💎 EasyView v1.0 (Single-File PHP)

![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)
![Style](https://img.shields.io/badge/Style-Glassmorphism-purple?style=for-the-badge)

A lightweight, aesthetic, and secure database management tool contained within a **single PHP file**. Designed as a modern alternative to Adminer, featuring a beautiful Glassmorphism UI, Card View for data, and a built-in security Gatekeeper.

Created with <3 by **Giovanni**.

---

## ✨ Features

* **🎨 Stunning UI:** Dark mode with Glassmorphism effects, neon accents, and smooth animations.
* **📦 Single File:** No installation required. Just upload one `.php` file and go.
* **📱 Card View Layout:** Data is displayed in modern, responsive cards instead of boring tables.
* **🔒 Gatekeeper Security:** Double-layer security. First, a site-wide password (MD5 hashed), then Database credentials.
* **🌍 Multi-Language:** Native support for **English (EN)** and **Romanian (RO)**.
* **⚡ Smart Badges:** Auto-detects status fields (active, pending, paid) and applies color-coded badges.
* **🛡️ Secure:** No raw passwords stored in code (MD5 Hashing used).

---

## 📸 Screenshots

*(Add your screenshots here. Example below:)*

| Secure Login | Dashboard | Card View |
|:---:|:---:|:---:|
|<img width="2869" height="1450" alt="image" src="https://github.com/user-attachments/assets/8e93bf73-77ce-4a14-891c-b3aef5d84453" /> |<img width="2871" height="1442" alt="image" src="https://github.com/user-attachments/assets/83285e7a-02bf-4cb9-921f-d0b6ac763dea" /> |<img width="2877" height="1452" alt="image" src="https://github.com/user-attachments/assets/5279903d-882c-46af-a2e0-eb5e35d0dc23" />

---

## 🚀 Quick Start

1.  **Download** the `viewer.php` file.
2.  **Upload** it to your web server (e.g., inside `public_html`).
3.  **Configure** the security password (see below).
4.  **Access** via browser: `yoursite.com/viewer.php`.

---

## ⚙️ Configuration

Open the file and edit the top configuration section:

```php
// --- 1. CONFIGURATION ---
// Default password is 'admin'. 
// Generate your own MD5 hash online and replace it here!
$SITE_PASSWORD_HASH = '21232f297a57a5a743894a0e4a801fc3'; 

$ROWS_LIMIT = 300; // Max items per page
