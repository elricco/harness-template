# Architektur-Richtlinie

> Wird aus `AGENTS.md` per Lazy Loading geladen, bevor an der Architektur
> gearbeitet wird. Ziel: einfache, testbare, portable Struktur ohne
> "structural debt".

## Leitprinzipien

- **Core-First:** Nutze immer zuerst die vom REDAXO-Core bereitgestellten
  Funktionen und Klassen. Eine Eigenkreation im Addon ist nur zulässig, wenn der
  Core nichts Passendes bietet – und wird dann kurz begründet.
- **KISS/YAGNI:** Die einfachste Struktur, die die Spec erfüllt. Keine
  Vorratsabstraktionen, keine Schichten "für später".
- **Testbarkeit vor Bequemlichkeit:** Geschäftslogik so schneiden, dass sie ohne
  laufendes REDAXO getestet werden kann (siehe Schichtung unten).
- **Statisch by default, dynamisch nur wo nötig.** Was zur Build-Zeit entstehen
  kann, entsteht zur Build-Zeit. Ausgeliefertes HTML/CSS/JS hat keine
  Angriffsfläche, die ein Backend hätte – Angriffsfläche ist eine
  Architektur-Entscheidung, keine Härtungs-Aufgabe hinterher.
- **Skeleton-Ballast früh gegen die Nicht-Ziele der Spec prüfen.**
  Framework-Skeletons/Meta-Pakete (z. B. `symfony/webapp-pack`) installieren
  oft mehr Subsysteme, als das Projekt tatsächlich braucht – Datenbank-ORM,
  Message-Queue, Notifier. Bleibt das unbemerkt liegen, wird es zur
  unbeauftragten Komplexität: ungeprüft, ungetestet, aber im Ausführungspfad.
  Lektion (Symfony-Rebuild-Projekt, 2026-09): die Spec nannte "keine
  Datenbank" explizit als Nicht-Ziel, trotzdem blieb der komplette
  Doctrine/ORM/Migrations/Messenger-Stack aus dem initialen Skeleton bis zur
  finalen Review unentdeckt liegen – und war die Ursache eines
  produktionsblockierenden Bugs (Mailversand lief unbemerkt durch eine
  Message-Queue ohne laufenden Consumer). Direkt nach dem Scaffolding früh
  gegen die Nicht-Ziele der Spec abgleichen (installierte Pakete/Bundles vs.
  tatsächlicher Bedarf) und ungenutzte Subsysteme aktiv entfernen, statt sie
  stillschweigend mitzuschleppen – nicht erst bei der Abschluss-Review.

## Angriffsfläche als Architektur-Entscheidung

Bei überwiegend statischen Websites (Astro, Eleventy, Hugo …):

- Die **dynamischen Teile isolieren und zählbar halten** – typischerweise genau
  einer (das Kontaktformular). Jeder weitere braucht eine Begründung in der Spec.
- Der dynamische Teil ist **austauschbar hinter einer stabilen Schnittstelle**:
  gleiches Frontend, gleiche Sicherheitsschichten, wechselbares Backend
  (Serverless-Route vs. PHP-Endpunkt auf dem Hoster). Die Wahl fällt nach
  Hosting und Datenschutz, nicht nach Technik-Vorliebe.
- **Secrets liegen nie in der ausgelieferten Schicht.** Statisches Build =
  öffentlich; Secrets nur in env bzw. in einer Config über dem Webroot.
- Beispiel mit beiden Backends: [`../examples/contact-form/`](../examples/contact-form/).

## Schichtung (für TDD und Composer-Portabilität entscheidend)

Trenne **Domänen-/Geschäftslogik** von der **REDAXO-Integration**:

```
lib/                      (PSR-4, namespaced)
├── Domain/               # reine PHP-Logik, KEINE rex_*-Abhängigkeit → unit-testbar
│   └── ...               # Entities, Services, Value Objects
└── Redaxo/               # Integrationsschicht: darf rex_* nutzen
    ├── ...               # Repositories (rex_sql), Extension-Handler, Page-Controller
```

- **Domain** kennt REDAXO nicht (kein `rex_sql`, kein `rex::`), ist damit schnell
  und deterministisch testbar.
- **Redaxo-Schicht** übersetzt zwischen Core-APIs und Domain. Nur hier stehen
  `rex_sql`, `rex_config`, `rex_extension` usw.
- Diese Abhängigkeitsrichtung (Redaxo → Domain, **nie** umgekehrt) wird mit
  **Deptrac** durchgesetzt (siehe [`verification-tools.md`](verification-tools.md)).

## Namespaces & Autoloading (REDAXO 6 Pflicht, R5-kompatibel)

REDAXO 6 macht **Namespaces zur Pflicht**. Schreibe deshalb **schon jetzt**
alles namespaced und PSR-4-autoloaded – dann läuft es unter R5 und ist R6-ready.

- Jedes Addon bekommt eine `composer.json` mit PSR-4-Mapping:

  ```json
  {
      "name": "elricco/mein-addon",
      "type": "redaxo-addon",
      "autoload": {
          "psr-4": { "Elricco\\MeinAddon\\": "lib/" }
      },
      "require": { "php": ">=8.1" }
  }
  ```

- REDAXO lädt `vendor/` **nach der Installation** automatisch – kein manuelles
  `require vendor/autoload.php`.
- Der alte `rex_*`-Dateiname-Autoloader (`lib/rex_foo.php` → `rex_foo`) ist
  **Legacy**. Nicht mehr für neuen Code verwenden.

## Composer-Installierbarkeit (Ziel)

Das Addon soll **später per Composer** installierbar sein, obwohl REDAXO 5 aktuell
noch den eigenen Addon-Weg geht. Deshalb:

- Saubere `composer.json` (siehe oben), alle Fremd-Abhängigkeiten dort deklariert.
- Keine harten Pfad-Annahmen; öffentliche Assets über `rex_url::addonAssets()` /
  `$addon->getAssetsUrl()` referenzieren.
- Struktur so halten, dass ein Wechsel auf `composer require` trivial bleibt
  (PSR-4, `type: redaxo-addon`, keine manuellen Includes).

## Symfony-Konventionen

REDAXO baut auf Symfony-Komponenten. Wo Symfony den Ton angibt, folgen:

- **Console/CLI:** Kommandos als `rex_console_command` (Symfony Console) im
  `lib/`-Bereich – keine Standalone-Skripte unter `bin/` (siehe Skill
  `redaxo-console-commands`).
- **Code-Stil:** `@Symfony`-Ruleset in PHP-CS-Fixer (siehe
  [`coding-style.md`](coding-style.md)).
- **Struktur:** klare Service-Klassen, Constructor-Injection statt globaler
  Zugriffe, wo es die Domain-Schicht betrifft.

## REDAXO-Core-Bausteine (statt Eigenbau nutzen)

| Bedarf              | Core-API                                     |
| ------------------- | -------------------------------------------- |
| DB-Zugriff          | `rex_sql`, `rex_sql_table` (Schema)          |
| Tabellen-Präfix     | `rex::getTable('name')`                       |
| Konfiguration       | `rex_config` / `$addon->getConfig()`          |
| Hooks/Events        | `rex_extension::register()`                    |
| Views/Fragmente     | `rex_fragment`, `rex_view`                     |
| Übersetzungen       | `rex_i18n`                                     |
| URLs                | `rex_url`, `rex_yrewrite` (Frontend)          |
| Logging             | `rex_logger::logException()`                   |

## REDAXO-Stolpersteine (aus der Praxis)

Gelernt in einem REDAXO-5.21-Projekt (2026-10/11). Jeder Punkt war ein realer
Fehlschlag:

- **Feste IDs für Templates und Module.** Das `developer`-Addon gleicht
  Dateien und Datenbank über die ID ab. Mit Auto-Increment entsteht nach dem
  Neuaufsetzen ein zweites Template mit demselben Schlüssel, jeder Request
  endet mit 500 (Duplicate Key). Feste IDs passend zu den Verzeichnisnamen
  (`src/templates/Name [5]`) vergeben und laut abbrechen, wenn die ID belegt
  ist.
- **Veralteter Core-Cache:** `var/cache/core/*.cache` überlebt das Löschen von
  `config.yml` und Datenbank; REDAXO hält sich dann für installiert (Fehler:
  Tabelle `rex_config` fehlt). Install-/Reset-Skripte leeren den Cache vor
  dem Setup.
- **yrewrite schreibt die Pfaddatei nur im Backend** (Konsole, Update).
  Legt Frontend-Code (z. B. ein Kundenbereich) Artikel an, selbst
  `rex_yrewrite::generatePathFile([])` aufrufen – sonst ist die neue Seite bis
  zum nächsten Cache-Löschen ein 404. Ab zwei Sprachen bekommen alle URLs ein
  Sprachpräfix (`/de/…`); alte Adressen liefern dann 404.
- **Artikel per API:** `rex_article_service::addArticle` legt offline an
  (Status wird ignoriert, in Offline-Kategorien sowieso); danach
  `articleStatus($id, 1, …)`. Eine Seite ohne Start-/404-Artikel liefert 500.
- **`rex_api_function` beendet sich selbst** (`exit` nach der Antwort),
  sonst rendert REDAXO danach noch die Seite. Jeder API-Aufruf
  (`?rex-api-call=…`) startet eine Session – Tests, die ein `Set-Cookie`
  erwarten, brauchen danach eine frische Sitzung.
- **`sendRedirect()`** erlaubt laut Docblock nur 301/307 (PHPStan meldet
  303); für 303 erst `setStatus('303 See Other')`.
- **Viterex-Stylesheet** (`dist/assets/style-*.css`) wird **nach** dem
  eigenen Addon-CSS geladen und setzt globale Regeln: `body`-Farben im
  Dark-Mode, Prose-Formatierung für nackte `<article>`, `line-height: 1.5`
  auf `html`, `appearance: none` auf Radios/Checkboxen. Eigene Basisfarben an
  eine Body-Klasse hängen, kein `<article>` im eigenen Layout, `line-height`
  und `appearance` bewusst zurücksetzen.

## JS / TS / Vue – Schichtung

Dasselbe Prinzip wie bei REDAXO: **Logik von Framework trennen.**

```
src/
├── domain/        # reine TS-Funktionen/Typen, KEIN Vue, KEIN fetch → unit-testbar
├── composables/   # useXxx – verbindet Domain mit Vue-Reaktivität
├── components/    # "dumme" UI, bekommt Daten via Props/Emits
├── stores/        # Pinia (typisiert)
└── api/           # HTTP-Zugriff, isoliert (ein Ort für fetch/axios)
```

- **Feature-basiert** gruppieren, wenn ein Feature wächst (`features/<name>/…`).
- Abhängigkeitsrichtung: `components → composables → domain`; `api` nur über
  composables/stores ansprechen, **nicht** direkt aus Komponenten.
- Geschäftslogik in `domain/` ist framework-agnostisch → schnell testbar ohne
  Component-Mount.
- Durchgesetzt mit **dependency-cruiser** (siehe [`verification-tools.md`](verification-tools.md)).
- **Publizierbarkeit** (Analogie zu Composer): saubere Exports/Barrels, damit ein
  Teil später als npm-Paket ausgekoppelt werden könnte.
- **Ohne Framework gilt dasselbe Prinzip**, nur mit eigenen Schichtnamen –
  z. B. `core/` (reine Logik) ← `render/` (Browser-API-Kapsel: DOM, Canvas,
  Web Audio) ← `features/`. Entscheidend bleibt: reine Logik importiert keine
  Browser-/UI-Schicht.

## Astro – Schichtung

Statisch by default, dynamischer Teil isoliert und zählbar (siehe
„Angriffsfläche als Architektur-Entscheidung" oben):

```
src/
├── content/        # Content Collections + Zod-Schemas, KEIN Import aus layouts/components/pages
├── layouts/         # Seiten-Rahmen (BaseLayout etc.), nutzt components
├── components/      # UI-Bausteine, bekommt Daten via Props
├── lib/             # serverseitige Logik (Kontaktformular-Backend, env-Guards)
├── pages/           # Routen, komponiert layouts + components, liest content
└── pages/api/       # dynamischer Teil (z. B. Kontaktformular), `prerender = false`
```

- Abhängigkeitsrichtung: `content` importiert nichts aus `layouts`/`components`/`pages`.
  `components` importiert nicht aus `lib` (Server-Code) — Komponenten sprechen `/api/*`
  ausschließlich über `fetch()` zur Laufzeit an, nie über einen direkten Import.
- Durchgesetzt mit dependency-cruiser für `.ts`-Dateien (siehe
  [`verification-tools.md`](verification-tools.md)); `.astro`-Dateien sind davon
  bewusst ausgenommen (dependency-cruiser hat keine verlässliche `.astro`-Auflösung,
  Stand Astro 5) — dort gilt die Regel durch Code-Review, nicht durch ein
  automatisiertes Gate.
- **Content-Pflege per Git-CMS (Keystatic, Decap, …):** Das CMS-Schema
  (z. B. `keystatic.config.ts`) bildet dieselben Content-Typen wie die
  Content-Collection-Schemas ab, ist aber ein **separates, unabhängiges
  Schema** – kein automatisches Zod-Mapping. Bei jeder Änderung an einem der
  beiden Schemas **immer auch das andere prüfen und nachziehen**, sonst
  laufen CMS-Formular und Content-Validierung auseinander.
- **CMS-Admin-UI mit lokalem/keinem Auth-Modus (z. B. Keystatics `local`-Storage)
  darf nie in einem Produktions-Build landen** – siehe
  [`security.md`](security.md#öffentliche-endpunkte--formulare-unauthentifiziert)
  für die Ausschluss-Regel. Praxismuster: Integration nur einbinden, wenn
  `process.argv` `astro dev` erkennt, nicht `build`/`preview` (Details/Lektion
  zur `defineConfig()`-Funktionsform-Falle: Astro-Projekt, 2026-08 –
  bei aktueller Astro-Version gegenprüfen, ob `defineConfig` inzwischen eine
  `({ command }) => …`-Funktionsform unterstützt, bevor `process.argv` als
  Workaround übernommen wird).
- **`<Image>`/`<Picture>` mit pnpm:** `sharp` als **direkte** Abhängigkeit
  installieren (`pnpm add sharp`). pnpm löst Astros transitive
  sharp-Abhängigkeit nicht auf, `pnpm build` bricht sonst mit
  `MissingSharp` ab (Astro-Website, 2026-10).
- **`loading="eager"`/`fetchpriority="high"` nur, wenn das Bild bei
  _jeder_ Breite im ersten Bildschirm steht.** Ein Hero-Porträt, das am
  Desktop oben steht, rutscht mobil oft unter den Text – eager geladen
  belegt es dann Bandbreite, die der sichtbare Inhalt braucht. Ein `<img>`
  kann nicht je Breite unterschiedlich laden; im Zweifel `loading="lazy"`
  (Astros Standard) – im Viewport lädt der Browser es trotzdem direkt.
  Mobil nachmessen; in einer Astro-Website (2026-10) war `lazy` auf beiden
  Formaten schneller (Desktop-LCP 0,60 → 0,54 s).
- **CSS-Hintergrundbilder unterhalb des ersten Bildschirms nachladen.** Für
  `background-image` gibt es kein `loading="lazy"` – der Browser lädt sie
  sofort, sobald das Element im DOM steht (Lighthouse `offscreen-images`,
  in einer Astro-Website 249 KB in einem CTA-Band ganz unten). Muster: Element
  mit `data-lazy-bg` markieren; eine CSS-Regel blendet das Bild aus, solange
  die Klasse `is-bg-loaded` fehlt:
  `.js [data-lazy-bg]:not(.is-bg-loaded) { background-image: none; }`.
  Ein IntersectionObserver (`rootMargin: "300px 0px"`) setzt die Klasse.
  Ohne JS fehlt `.js`,
  das Bild lädt wie gewohnt. Hero-Hintergründe im ersten Bildschirm nicht
  markieren. Referenz: `src/scripts/lazy-background.ts` in
  Astro-Website.

## WordPress – Schichtung

```
mein-plugin/
├── mein-plugin.php     # Bootstrap: lädt Autoloader, registriert Hooks – dünn
├── composer.json       # PSR-4 Autoload + Dependencies
└── src/                # namespaced Klassen (PSR-4)
    ├── Domain/         # reine Logik, KEINE WP-Funktionen → unit-testbar
    └── Wp/             # Integration: Hooks, WP_Query, $wpdb, REST-Controller
```

- **Domain** kennt WordPress nicht (kein `WP_Query`, kein `$wpdb`) → per Brain
  Monkey/reinen Units testbar.
- **Wp-Schicht** kapselt Core-Zugriffe und registriert Hooks über einen schlanken
  Loader.
- **Composer-Installierbarkeit** von Anfang an (PSR-4, Dependencies deklariert).
- Abhängigkeit `Wp → Domain`, nie umgekehrt – ebenfalls per Deptrac erzwingbar.

## Prüfung

Nach Architektur-Änderungen die Architektur-Analyse (Deptrac für PHP,
dependency-cruiser für JS/TS) aus [`verification-tools.md`](verification-tools.md)
ausführen.
