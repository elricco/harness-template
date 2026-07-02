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
