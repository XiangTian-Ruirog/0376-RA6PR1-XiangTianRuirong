# 0376-RA6PR1-XiangTianRuirong
RA6PR1 - Projecte Web: PHP + MySQL amb Cline

# ⏱ HorApp - Control d'Hores de Feina

Aplicació web desenvolupada amb **PHP + MySQL** per al control d'hores de treball d'una empresa.
Projecte del mòdul **0376 - Implantació d'aplicacions Web** (Curs 2025/2026).

---

## 🚀 Funcionalitats

### 👤 Empleat
- Fitxar entrada i sortida amb selecció de projecte
- Veure historial dels últims 7 dies
- Calendari personal amb dies treballats (verd = +7h, groc = menys de 7h)
- Perfil personal amb estadístiques i canvi de contrasenya

### 🔧 Administrador
- Dashboard amb resum diari i gràfic d'activitat dels últims 7 dies
- Gestió d'empleats: crear, editar, activar/desactivar
- Gestió de projectes: crear, editar, activar/desactivar
- Sistema d'alertes: llista vermella d'empleats que no han fitxat o fan poques hores
- Reports amb gràfics de barres i pastís (hores per projecte i per empleat)
- Calendari general amb activitat de tots els empleats
- Edició i eliminació de registres d'hores

---

## 🛠️ Tecnologies

- **Backend:** PHP 8
- **Base de dades:** MySQL / PDO
- **Frontend:** Bootstrap 5, Chart.js, FullCalendar 6
- **Servidor:** Apache (LAMP)

---

## ⚙️ Instal·lació

### Requisits
- Apache 2.4+
- PHP 8.0+
- MySQL 8.0+

### Passos

**1. Clona el repositori:**
```bash
git clone https://github.com/TU_USUARIO/0376-RA6PR1-XiangTianRuirong.git /var/www/html/0376-RA6PR1-XiangTianRuirong
```

**2. Importa la base de dades:**
```bash
mysql -u usuari -p < /var/www/html/0376-RA6PR1-XiangTianRuirong/horapp/sql/schema.sql
```

**3. Configura la connexió:**

Edita `horapp/config/database.php` amb les teves credencials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'horapp');
define('DB_USER', 'el_teu_usuari');
define('DB_PASS', 'la_teva_contrasenya');
```

**4. Configura Apache:**

El `DocumentRoot` ha d'apuntar a:

/var/www/html/0376-RA6PR1-XiangTianRuirong/horapp

**5. Accedeix a l'aplicació:**

http://localhost/public/login.php

---

## 👥 Usuaris de prova

| Rol | Email | Contrasenya |
|-----|-------|-------------|
| Admin | admin@horapp.com | password |
| Empleat | joan@horapp.com | password |
| Empleat | maria@horapp.com | password |

---

## 🔒 Seguretat implementada

- Contrasenyes xifrades amb `password_hash()` (bcrypt)
- Validació d'accés per rol en totes les pàgines
- Prepared Statements per prevenir injeccions SQL
- Neteja d'inputs amb `htmlspecialchars()` i `filter_var()`
- Sessions segures al servidor (mai contrasenyes en cookies)
- Errors de BD ocults a l'usuari final

---

## 👨‍💻 Autor

**Xiang Tian Ruirong**  
Institut de Logística de Barcelona  
Curs 2025/2026
