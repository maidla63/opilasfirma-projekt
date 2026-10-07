# 🍽️ Lurr või hitt?

<p align="center">
  <strong>Koolitoidu tagasisideplatvorm õpilastele</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript">
  <img src="https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white" alt="HTML5">
  <img src="https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white" alt="CSS3">
</p>

---

## 📖 Projektist

**Lurr või hitt?** on õpilastele mõeldud veebiplatvorm, kus saab anda tagasisidet koolis pakutava toidu kohta.

Kasutajad saavad hinnata toite, kirjutada kommentaare, lisada märksõnu ning jagada oma arvamust teiste õpilastega.

Kogutud hinnangute põhjal saab võrrelda erinevaid koole ja toite ning vaadata statistikat.

### 🎯 Projekti eesmärk

Projekti eesmärk on muuta koolitoidu kohta tagasiside andmine:

- lihtsaks;
- kiireks;
- läbipaistvaks;
- õpilastele mugavaks.

Samuti aitab kogutud tagasiside paremini mõista, millised toidud õpilastele meeldivad ja millised mitte.

---

## ✨ Funktsioonid

### 👨‍🎓 Õpilastele

- 👤 Kasutajakonto loomine
- 🔐 Sisselogimine ja väljalogimine
- 🍴 Toidu hindamine
- ⭐ Hinnangu andmine
- 💬 Kommentaaride lisamine
- 🏷️ Märksõnade lisamine
- 📷 Toidupildi lisamine
- 🍽️ Söödud koguse märkimine
- 🔄 Märkimine, kas sööksid toitu uuesti
- ❤️ Hinnangutele reageerimine
- 👤 Kasutajaprofiil
- 🌙 Hele ja tume teema

### 🏫 Koolid

- Koolide vaatamine
- Koolide võrdlemine
- Koolide edetabel
- Keskmiste hinnangute kuvamine
- Hinnangute arvu kuvamine

### 📊 Statistika

- Toitude hinnangute statistika
- Kasutajate aktiivsuse statistika
- Koolide tulemuste võrdlemine
- Raportite vaatamine
- Andmete eksport

### 🛡️ Administraatoritele

- Kasutajate haldamine
- Administraatorite haldamine
- Sisu moderatsioon
- Kasutajarollide haldamine
- Administraatori dashboard
- Sobimatu sisu eemaldamine

---

## 🖥️ Rakenduse põhivaated

### 📰 Avaleht

Avalehel kuvatakse kasutajate poolt lisatud toiduhinnanguid.

Kasutaja saab sirvida teiste õpilaste arvamusi ning vaadata erinevaid hinnanguid.

### 🍴 Toidu hindamine

Kasutaja saab lisada uue hinnangu ning määrata näiteks:

- toidu nime;
- kooli;
- hinnangu;
- märksõnad;
- kommentaari;
- söödud koguse;
- kas sööks toitu uuesti;
- toidupildi.

### 🏫 Koolid

Koolide vaates saab võrrelda erinevaid koole ning vaadata nende tulemusi.

### 👥 Õpilased

Õpilaste vaates saab vaadata aktiivsemaid kasutajaid ja nende tegevust platvormil.

### 📊 Dashboard

Dashboard koondab statistika ja raportid ühte vaatesse.

### 🛡️ Admin

Administraatori vaates saab hallata kasutajaid ja platvormi sisu.

---

## 🛠️ Tehnoloogiad

| Tehnoloogia | Kasutus |
|---|---|
| PHP | Backend ja serveripoolne loogika |
| MySQL / MariaDB | Andmebaas |
| JavaScript | Interaktiivsus ja API-päringud |
| HTML5 | Veebilehe struktuur |
| CSS3 | Kujundus ja responsive kasutajaliides |
| SQL | Andmebaasi päringud ja struktuur |

---

## 🏗️ Projekti ülesehitus

```text
opilasfirma-projekt/
│
├── api/
│   ├── auth.php
│   ├── db.php
│   └── ...
│
├── assets/
│   ├── kk.css
│   ├── extra.css
│   ├── confirm.css
│   ├── kk.js
│   └── confirm.js
│
├── cron/
│   └── ...
│
├── sql/
│   └── ...
│
├── src/
│   └── ...
│
├── storage/
│   └── ...
│
├── uploads/
│   └── ...
│
├── admin.php
├── admin.js
├── admin_managers.php
├── app.js
├── dashboard.php
├── export.php
├── index.php
├── login.php
├── logout.php
├── moderation.php
├── profile.php
├── register.php
├── script.js
└── README.md
