🍽️ Lurr või hitt?
<p align="center"> <strong>Koolitoidu tagasisideplatvorm õpilastele</strong> </p> <p align="center"> <a href="https://github.com/maidla63/opilasfirma-projekt"> <img src="https://img.shields.io/badge/status-active%20development-orange?style=for-the-badge" alt="Project status"> </a> <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP"> <img src="https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL"> <img src="https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript"> <img src="https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white" alt="HTML5"> <img src="https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white" alt="CSS3"> </p> <p align="center"> <a href="#-projektist">Projektist</a> • <a href="#-funktsioonid">Funktsioonid</a> • <a href="#-tehnoloogiad">Tehnoloogiad</a> • <a href="#-paigaldamine">Paigaldamine</a> • <a href="#-projekti-struktuur">Struktuur</a> • <a href="#-turvalisus">Turvalisus</a> </p>
📖 Projektist
Lurr või hitt? on õpilastele suunatud veebiplatvorm, mille eesmärk on muuta koolitoidu kohta tagasiside andmine lihtsaks, kiireks ja läbipaistvaks.

Õpilased saavad hinnata koolis pakutavat toitu, lisada kommentaare, kasutada märksõnu ning anda tagasisidet selle kohta, kui palju toidust ära söödi ja kas nad sooviksid sama toitu uuesti.

Kogutud andmete põhjal saab võrrelda koole, analüüsida toitude populaarsust ning tuvastada korduvaid probleeme.

Lühidalt: mida õpilased sööklas tegelikult arvavad?

🎯 Eesmärk
Projekti eesmärk on ühendada õpilaste tagasiside, statistika ja koolitoidu kvaliteedi jälgimine ühte lihtsasse keskkonda.

Platvorm aitab vastata näiteks järgmistele küsimustele:

🍕 Millised toidud õpilastele kõige rohkem meeldivad?

🥘 Millised toidud saavad kõige rohkem negatiivset tagasisidet?

🍽️ Kui palju toitu jääb söömata?

🏫 Millistes koolides on õpilaste hinnangud kõige paremad?

💬 Millised probleemid korduvad õpilaste kommentaarides?

📈 Kuidas muutuvad hinnangud aja jooksul?

✨ Põhifunktsioonid
👨‍🎓 Õpilastele
Konto loomine ja sisselogimine

Kooliga seotud kasutajakonto

Toitude hindamine

Hinnangute ja kommentaaride lisamine

Märksõnade kasutamine

Toidu söömise hulga märkimine

Märkimine, kas sööksid toitu uuesti

Toidupildi lisamine

Teiste kasutajate hinnangute vaatamine

Hinnangute meeldimised

Profiil ja kasutaja tegevused

Hele ja tume teema

🏫 Koolidele
Koolide võrdlemine

Koolide edetabel

Keskmiste hinnangute kuvamine

Hinnangute arvu põhine statistika

Õpilaste aktiivsuse ülevaade

📊 Statistika ja raportid
Toitude hinnangute statistika

Kasutajate aktiivsuse statistika

Koolide tulemused

Raportivaade

Andmete eksport

🛡️ Administraatoritele
Kasutajate haldamine

Administraatorite haldamine

Sisu moderatsioon

Kasutajate rollide haldamine

Probleemse sisu kontrollimine

Administraatori dashboard

🖥️ Rakenduse põhivaated
📰 Hinnangute voog
Peamine voog koondab kasutajate poolt lisatud hinnangud ning võimaldab näha, mida teised õpilased koolitoidust arvavad.

🍴 Toidu hindamine
Kasutaja saab hinnata konkreetset toitu ning lisada täiendavat infot:

hinnang;

kommentaar;

märksõnad;

söödud kogus;

kas sööks uuesti;

foto.

🏫 Koolide edetabel
Koolide tulemusi saab võrrelda hinnangute põhjal.

Edetabel aitab näha, millistes koolides on õpilaste tagasiside kõige positiivsem.

👥 Õpilaste edetabel
Õpilaste vaade võimaldab näha aktiivsemaid kasutajaid ning nende panust platvormile.

📊 Dashboard
Dashboard koondab projekti jaoks olulise statistika ja annab parema ülevaate kogutud andmetest.

🛡️ Moderatsioon
Moderatsiooni kaudu saab hallata kasutajate loodud sisu ja tagada, et platvorm jääks sobivaks ning kasutajasõbralikuks.

🧰 Tehnoloogiad
Tehnoloogia	Kasutus
PHP	Serveripoolne loogika
MySQL / MariaDB	Andmete salvestamine
JavaScript	Interaktiivsus ja API-päringud
HTML5	Veebilehe struktuur
CSS3	Kujundus ja responsive UI
SQL	Andmebaasi skeem ja päringud

🏗️ Arhitektuur
Rakendus on üles ehitatud klassikalise serveripoolse veebirakenduse põhimõttel.

┌──────────────────────────────────────┐
│              Frontend                │
│        HTML + CSS + JavaScript       │
└──────────────────┬───────────────────┘
                   │
                   ▼
┌──────────────────────────────────────┐
│             PHP Backend              │
│     Authentication / Business Logic  │
└──────────────────┬───────────────────┘
                   │
          ┌────────┴────────┐
          ▼                 ▼
┌─────────────────┐  ┌─────────────────┐
│       API       │  │   File Storage  │
│ PHP endpoints   │  │ uploads/storage │
└────────┬────────┘  └─────────────────┘
         │
         ▼
┌──────────────────────────────────────┐
│              MySQL                   │
│          Application data            │
└──────────────────────────────────────┘

📁 Projekti struktuur
opilasfirma-projekt/
│
├── api/                    # API ja backend-funktsioonid
│   ├── auth.php
│   ├── db.php
│   └── ...
│
├── assets/                 # CSS ja JavaScript
│   ├── kk.css
│   ├── extra.css
│   ├── confirm.css
│   ├── kk.js
│   └── confirm.js
│
├── cron/                   # Ajastatud ülesanded
│   └── ...
│
├── sql/                    # Andmebaasi SQL-failid
│   └── ...
│
├── src/                    # Projekti lähtekood
│   └── ...
│
├── storage/                # Rakenduse salvestatud andmed
│   └── ...
│
├── uploads/                # Kasutajate üleslaaditud failid
│   └── ...
│
├── admin.php               # Administraatori dashboard
├── admin.js                # Admini JavaScript
├── admin_managers.php      # Administraatorite haldus
├── app.js                  # Rakenduse JavaScript
├── dashboard.php           # Statistika ja raportid
├── export.php              # Andmete eksport
├── index.php               # Avaleht / feed
├── login.php               # Sisselogimine
├── logout.php              # Väljalogimine
├── moderation.php          # Moderatsioon
├── profile.php             # Kasutaja profiil
├── register.php            # Registreerimine
├── script.js               # Üldine JavaScript
└── README.md

🚀 Paigaldamine
Eeldused
Enne projekti käivitamist veendu, et arvutis on olemas:

PHP 8.x või uuem

MySQL või MariaDB

Apache või muu PHP-d toetav veebiserver

Git

1. Repositooriumi kloonimine
git clone https://github.com/maidla63/opilasfirma-projekt.git
cd opilasfirma-projekt

2. Andmebaasi loomine
Loo MySQL/MariaDB andmebaas:

CREATE DATABASE opilasfirma;

Seejärel impordi projekti sql/ kaustas olevad vajalikud SQL-failid.

Näiteks:

mysql -u USERNAME -p opilasfirma < sql/database.sql

SQL-faili täpne nimi sõltub projekti praegusest versioonist.

3. Andmebaasiühenduse seadistamine
Kontrolli faili:

api/db.php

ja määra enda keskkonnale vastavad ühenduse andmed:

$host = 'localhost';
$db   = 'opilasfirma';
$user = 'YOUR_USERNAME';
$pass = 'YOUR_PASSWORD';

⚠️ Ära commit'i päris paroole GitHubi.

Kui võimalik, kasuta tootmiskeskkonnas environment variable'e.

4. Rakenduse käivitamine
Arenduskeskkonnas saab PHP sisseehitatud serverit kasutada:

php -S localhost:8000

Seejärel ava:

http://localhost:8000

🔐 Turvalisus
Turvalisus on rakenduse oluline osa.

Projekt kasutab muu hulgas:

🔒 paroolide hashimist;

🛡️ sessioonipõhist autentimist;

🎫 CSRF-token'eid;

👤 kasutajarolle;

🔑 ligipääsukontrolli;

🗄️ ettevalmistatud SQL-päringuid;

🚫 admini funktsioonide piiratud ligipääsu.

Ära kunagi lisa GitHubi:
.env
database passwords
API keys
private keys
production credentials
session secrets

Soovituslik .gitignore võiks sisaldada näiteks:

.env
.env.*
*.log

storage/*
uploads/*

.DS_Store
.idea/
.vscode/

👤 Kasutajarollid
Rakenduses on kasutajatel erinevad õigused.

Roll	Õigused
user	Toitude hindamine ja tavakasutaja funktsioonid
admin	Kasutajate, sisu ja süsteemi haldamine

Täiendavaid rolle saab projekti arenedes juurde lisada.

📊 Andmed ja statistika
Platvorm kogub kasutajate hinnanguid, mille põhjal saab moodustada statistikat.

Näiteks:

                 Kooli keskmine
                       │
                       ▼
              ┌─────────────────┐
              │      4.2 ⭐      │
              └─────────────────┘
                       │
          ┌────────────┼────────────┐
          ▼            ▼            ▼
       1 245          82%          68%
     hinnangut     sööks uuesti   sõi ära

Selline info aitab muuta subjektiivse tagasiside mõõdetavamaks ning lihtsamini analüüsitavaks.

🧪 Arendus
Muudatuste tegemiseks loo uus haru:

git checkout -b feature/my-feature

Pärast muudatuste tegemist:

git add .
git commit -m "Add: my feature"
git push origin feature/my-feature

Seejärel saab GitHubis avada Pull Request'i.

Commit-sõnumite soovitus
Kasuta võimalusel selgeid commit-sõnumeid:

Add: new food rating feature
Fix: login validation
Update: dashboard statistics
Refactor: database connection
Style: improve mobile layout
Docs: update README

🗺️ Roadmap
Edasises arenduses võiks projektile lisada näiteks:

 Täielik responsive mobile-first UI

 Täpsem statistika ja graafikud

 Toitude automaatne järjestamine

 Täiustatud otsing ja filtrid

 Teavituste süsteem

 PWA / mobiilirakenduse tugi

 Täiustatud admin dashboard

 Automaatne testimine

 CI/CD

 Production deployment

 Detailsem API dokumentatsioon

🤝 Panustamine
Panustamine on teretulnud.

Fork'i projekt.

Loo uus branch.

Tee oma muudatused.

Lisa selge commit.

Push'i branch GitHubi.

Loo Pull Request.

Enne Pull Request'i veendu, et:

olemasolev funktsionaalsus töötab;

uusi PHP vigu ei teki;

tundlikke andmeid ei ole commit'itud;

kasutajaliides töötab ka väiksemal ekraanil;

muudatused on piisavalt dokumenteeritud.

📄 Litsents
Projekt on loodud Õpilasfirma projekti raames.

Kui projektile määratakse ametlik open-source litsents, tuleks siia lisada vastav litsents ja LICENSE fail.

👥 Õpilasfirma projekt
Lurr või hitt? on loodud eesmärgiga anda õpilastele võimalus koolitoidu kohta oma arvamust avaldada ning muuta tagasiside kogumine koolidele lihtsamaks ja kasulikumaks.

🔗 GitHub:
https://github.com/maidla63/opilasfirma-projekt

<p align="center"> Made with ❤️ for students and better school food. </p>
