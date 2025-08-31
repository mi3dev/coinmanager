# Coin Manager – Změnový protokol

---

## v0.1 – Startovací verze (08.08.2025)
- Vytvořena adresářová struktura pro aplikaci (app/, public/, uploads/, vendor/).
- Přidány základní **core třídy**:
  - `Database.php` – PDO wrapper pro práci s MySQL.
  - `Auth.php` – autentizace uživatelů a kontrola rolí.
  - `Session.php` – správa session a CSRF tokenů.
  - `Router.php` – jednoduchý směrovač URL na controllery.
  - `Controller.php` – základ pro dědičnost controllerů.
- Implementovány **modely**:
  - `User.php` – vyhledávání uživatele podle e-mailu.
- Přidány **controllery**:
  - `AuthController.php` – login, logout, kontrola přihlášení.
  - `DashboardController.php` – pracovní plocha s přehledem statistik a posledních změn.
  - `DictionaryController.php` – příklad read-only číselníku.
- Přidány **view šablony**:
  - `layout.php` – hlavní rozvržení stránky s menu podle role.
  - `auth/login.php` – přihlašovací formulář.
  - `dashboard/index.php` – dlaždicové metriky + poslední změny.
  - `dictionary/list.php` – seznam období z DB.
- Přidán základní **CSS styl** (`public/css/style.css`).
- Do každého souboru přidán **první řádek s verzí** (`// v1.0` nebo `/* v1.0 */`).

---

## v0.2 – Oprava routeru (08.08.2025)
- Úprava `Router.php`:
  - Načítá soubory controllerů přímo z `/app/controllers/`.
  - Kontroluje existenci souboru, třídy i metody.
  - Lepší kompatibilita s názvy route (case-insensitive pro část controlleru).
- Doplněn **CHANGELOG.md** pro sledování verzí.
## v0.2.1 – Fix autoloaderu (08.08.2025)
- Upraven `index.php` autoloader: prefix `App\` se mapuje na `app/` a podsložky jsou lowercase (`controllers`, `models`, `core`, `views`).
- Vyřešena chyba `Class "App\Models\User" not found` při konstrukci `AuthController`.
## v0.2.2 – Fix SQL ve výpisu posledních změn (08.08.2025)
- DashboardController@index`: nahrazeno `ci.display` za `COALESCE(ci.commemorativeTitle, cd.display)` s joinem na `CoinDenomination`.
## v0.3 – Profi topbar, Tailwind + Alpine (08.08.2025)
- Nahrazeno menu v `layout.php` za responzivní topbar (Tailwind, Alpine.js).
- Přidán user dropdown, mobile hamburger, role‑aware navigace.
- (Volitelně) sladěn dashboard s Tailwind komponentami.
## v0.4 – Administrace uživatelů (08.08.2025)
- Přidán `UsersController` (list/create/store/edit/update/toggle).
- Dvě view: `users/list.php`, `users/form.php` (Tailwind, CSRF).
- Jen pro roli admin. Heslo lze měnit jen vyplněním nového.
## v0.5 – Helpery pro řazení, stránkování, URL, CSRF/flash (08.08.2025)
- Přidány helpery: Config, Url, Sort, Pagination, View.
- Users list přepsán na jednotné helpery (klikací hlavičky, stránkování z configu).
## v0.6 – Číselník Období (CRUD + validace délek) (08.08.2025)
- Přidán `PeriodsController` (list/create/store/edit/update/delete) pouze pro admin.
- Validace délek dle DB: display(255), name(64), description(255), note(TEXT), yearFrom/yearTo INT (−5000..9999) + kontrola pořadí.
- View: `periods/list.php` (třídění, stránkování, hledání), `periods/form.php` (zobrazování chyb).
- Přidán helper `Validator`.
v0.6.1 – Úprava menu + fix CoinPeriod SELECT (08.08.2025)
- „Období“ přesunuto pod Číselníky (dropdown v menu + mobile sekce).
- PeriodsController.list: odstraněn created_at z SELECT/ORDER BY; whitelist sort sloupců zúžen na id,display,name,yearFrom,yearTo.
v0.7 – Období: audit + soft‑delete + filtry (08.08.2025)
- DB: přidány sloupce active, created_at/by, updated_at/by, deleted_at/by + indexy.
- PeriodsController: 
  - list() implicitně zobrazuje jen aktivní; přidán filtr „Zobrazit i neaktivní“.
  - store()/update() vyplňují auditní stopy z přihlášeného uživatele.
  - delete() změněno na soft‑delete (active=0 + deleted_*), přidána akce toggle() pro (de)aktivaci.
- View list: rozbalovací „Další filtry“, indikace stavu, tlačítka Aktivovat/Deaktivovat.
## v0.7.1 – Menu & Login polish (12.08.2025)
- Menu: vrácen odkaz na správu uživatelů pro roli admin (layout fix).
- Přihlašovací stránka přepracována do profesionální podoby (Tailwind), sjednoceny stavy chyb a validace.

## v0.7.2 – Kompaktní stránkování (12.08.2025)
- Pagination helper: „okna“ s ellipsami + tlačítka První/Poslední.
- Nasazeno v seznamech Uživatelé a Období.

## v0.8 – Číselníky: kompletní CRUD + audit + (de)aktivace (12.08.2025)
- **CoinDenomination** – název, hodnota, jednotka, display, poznámka; kontroly délek podle DB.
- **CoinMetal** – název, popis, display, poznámka; kontroly délek.
- **CoinMint** – název, popis, display, země, značka; kontroly délek.
- **CoinType** – název, popis, display, poznámka.
- **CoinDesigner** – jméno/příjmení, roky, národnost, popis, poznámka.
- **CoinRarity** – název, display, level, popis, poznámka; filtry aktivní/neaktivní.
- Všude doplněny sloupce auditů (created/updated/deactivated by/at) a „Active“ soft‑delete.
- Formuláře hlídají max. délky vstupů a numerické formáty dle schématu.

## v0.8.1 – Nominály (CoinCatalogItem) – základní CRUD (12.08.2025)
- Seznam s filtry (fulltext), tříděním a stránkováním.
- Detail s vazbami na období/typ/nominál/kov/hranu/mincovnu a parametry (průměr, váha, tloušťka).
- Validace vstupů (délky, des. čísla, roky od–do).

## v0.8.2 – Nominály: obrázky + autoři líc/rub (12.08.2025)
- Upload **avers/revers** (JPG/PNG/WEBP, max 5 MB), generování bezpečných názvů, nahrazení/smazání starých souborů.
- Multi‑select **autorů** přes `CoinCatalogDesigner` (1‑N) pro **líc** i **rub**; v listu zobrazen rychlý přehled počtů.
- Konfigurovatelná cesta uploadů (`uploads.coins`).

## v0.8.3 – Katalogy (Catalog) + detail katalogu (12.08.2025)
- CRUD pro `Catalog` (název, rok, měna, popis, aktivita).
- **Detail katalogu**: přehled `CatalogEntry` včetně vzácnosti, nákladů, cen a variant; opraveny joiny a názvy sloupců dle DB.
- Do seznamu katalogů přidán odkaz na detail.

## v0.9 – Plata (CollectionTray) – GUI a řazení (12.08.2025)
- `CollectionTraysController` (list/create/edit/delete/moveUp/moveDown).
- Pořadí v rámci uživatele s automatickým „zahustěním“ při vložení/smazání.
- Přístup pro role **admin** a **collector** (`Auth::requireAnyRole`).

## v0.9.1 – Moje sbírka (Collection) – GUI (12.08.2025)
- List s filtry (text/plato/rok/pouze ve sbírce), náhledy líc/rub, stránkování.
- Form s výběrem položky katalogu, rokem, **kvalitou** (číselník: PROOF, RL, 0/0, -0/0-, 1/1, -1/1-, 2/2), cenami, platem, poznámkami.
- Přidána pole **variantType/variantDescription** (stejně jako v CatalogEntry) a upload **obverseImage/reverseImage** (stejná pravidla jako u nominálů).

## v0.9.2 – Auth & Database vylepšení (12.08.2025)
- `Auth.php`: doplněny `start()`, `check()`, `user()`, `id()`, `login()`, `logout()`, `requireRole()`, `requireAnyRole()`.
- `Database.php`: metoda `lastInsertId()` a robustnější konfigurace (`dsn` nebo `host`+`dbname|database`+`port`+`charset`), lepší chybová hlášení.

## v0.9.3 – Období: tlačítko „Zrušit filtr“ (13.08.2025)
- Seznam období: přidáno tlačítko pro zrušení filtrů včetně ikony.

## V 1.0 – první verze aplikace
- Základní přihlášení pomocí e-mailu a hesla
- Role: admin, sběratel, host
- Administrace katalogu, ceníku a sbírky
- Napojení na databázi MySQL

## V 1.1 – úprava přihlášení na uživatelské jméno
- Přihlášení přes **uživatelské jméno** místo e-mailu
- Odstraněn odkaz „Zapomenuté heslo“
- Odstraněn duplicitní text „© Coin Manager“ z login stránky
- Přidána metoda `findByUsername()` do modelu `User`
- Úprava kontroleru přihlášení (`AuthController`) pro práci s uživatelským jménem