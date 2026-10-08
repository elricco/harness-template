# Docker-Entwicklungsumgebung (Vorlage) – stack-übergreifend

Löst ein wiederkehrendes Problem: der Entwickler-Rechner hat oft eine andere
Sprachversion auf dem PATH als das Projekt braucht (ältere PHP-/Node-
Installation, Systempakete, …). Ohne eine gepinnte, projekteigene Umgebung
muss dann jeder manuelle Testlauf – und jeder Agent-Dispatch – einen
Versions-Workaround durchreichen, der leicht vergessen wird. Ein Docker-Setup
mit exakt der richtigen Version macht diesen Workaround überflüssig: Mensch
und Agent nutzen dieselbe, garantiert korrekte Umgebung.

| Datei                        | Zweck                                                             |
| ----------------------------- | ------------------------------------------------------------------ |
| `Dockerfile.php.dist`         | PHP-Basis-Image, exakt gepinnt. Für andere Stacks Basis-Image tauschen. |
| `docker-compose.yml.dist`     | `app`-Service (Bind-Mount, Dev-Server) + `mailer`-Service (Mailpit) |

## Nutzung

1. `Dockerfile.php.dist` → `Dockerfile`, `docker-compose.yml.dist` →
   `docker-compose.yml`, beide in den Repo-Root kopieren.
2. Basis-Image-Version und den `app`-Service-Startbefehl ans Projekt
   anpassen (`TEMPLATE:`-Kommentare markieren die Stellen).
3. `docker compose build && docker compose run --rm app <verifikationsbefehl>`
   statt lokal installierter Tools – für jeden Task-Dispatch an einen
   KI-Agenten in der Anweisung explizit erwähnen (`docker compose run --rm
   app composer test` statt nur `composer test`).

## Mailpit: Mailversand lokal sichtbar machen

Der `mailer`-Service (Mailpit) fängt jede ausgehende Mail lokal ab, statt sie
an einen echten SMTP-Server zu schicken – Web-UI unter
`http://localhost:8025` zeigt jede „gesendete" Mail sofort an, ganz ohne
Postfach-Zugangsdaten. Die `docker-compose.yml.dist` überschreibt
`MAILER_DSN` für den `app`-Service automatisch auf `smtp://mailer:1025` –
kein manuelles `.env.local`-Setup nötig, greift sofort unter Docker.

**Ersetzt nicht den Test, der den echten Mailer-Service auflöst** (siehe
[`../../harness/testing.md`](../../harness/testing.md), Abschnitt „Grenzen
von In-Process-Tests") – Mailpit ist für die manuelle/explorative Prüfung
während der Entwicklung, nicht für automatisierte Verifikation.

**Andere Sprachen/Frameworks:** Das Docker-Muster (ein `app`-Service mit
Bind-Mount + gepinntem Basis-Image, optional `mailer` für alles, was Mail
verschickt) ist unabhängig vom Stack. Für Node/Python/etc. nur das
Basis-Image im Dockerfile tauschen (`node:20-slim`, `python:3.12-slim`, …),
der Rest bleibt gleich.

## REDAXO mit Viterex im Docker (Lektionen)

Gelernt in einem REDAXO-Projekt (2026-10): REDAXO 5.21 per `create-viterex` (modern
layout) in einem Compose-Stack (PHP-FPM, Nginx, MariaDB, Mailpit). Jede Zeile
war ein realer Fehlschlag.

- **Das Image braucht den `mysql`-Client** (`mariadb-client`): create-viterex
  ruft ihn auf, sonst `ENOENT`.
- **Nicht-interaktiv scaffolden:** `npx create-viterex --config viterex.json`
  (alle Antworten gepinnt, `skipGit`, `packageManager`). `viterex.json` enthaelt
  Passwoerter im Klartext: nie committen. `--resume` friert die Config in
  `.viterex-state.json` ein, geaenderte Zugangsdaten greifen dann nicht mehr:
  Scaffold-Verzeichnis verwerfen und neu starten.
- **REDAXO legt die DB ueber die System-DB `mysql` an.** Ein App-User ohne
  Zugriff darauf scheitert mit "Unable to open the specified database!"
  (irrefuehrend, die Verbindung klappt). Ausweg: Scaffold einmalig mit
  Root-Zugang, danach `var/data/core/config.yml` auf den App-User umstellen;
  fuer reproduzierbare Setups die DB per `/docker-entrypoint-initdb.d`-Skript
  anlegen (exakter `GRANT` auf die Projekt-DB) und `setup:run --db-createdb=no`
  nutzen.
- **`setup:run` installiert nur einen Teil der System-Addons.** Den Rest
  (`structure`, `mediapool`, `users`, ...) in Abhaengigkeits-Reihenfolge per
  `package:install <addon> --no-interaction` nachziehen, bereits installierte
  ueberspringen (sonst fragt der Befehl interaktiv und haengt).
- **pnpm 12 ignoriert `.npmrc` und `npm_config_*` fuer den Store.** Auf einem
  macOS-Bind-Mount scheitern Hardlinks (`failed to import ... os error 2`).
  Loesung: `pnpm-workspace.yaml` mit `storeDir: /tmp/pnpm-store` und
  `packageImportMethod: copy`.
- **`.gitignore` nie mit `sort -u` zusammenfuehren:** Negationen
  (`!var/cache/.*`) muessen nach ihren Ignore-Zeilen stehen. Kein pauschales
  `/var/` ignorieren, Viterex trackt `var/data/addons/mform` u. a.
- **Viterex legt eine `composer.json` mit `vendor-dir: .tools` an.** Eigene
  Dev-Tools dort einhaengen (Bootstrap `.tools/autoload.php`), nicht dagegen
  kaempfen. Core und Addons sind im Repo getrackt (~2800 Dateien).
- **Port 8080 ist oft belegt** (anderer Stack mit Traefik). Webport per
  `${WEB_PORT:-8081}` konfigurierbar machen, sonst liest der Smoke-Test die
  Antwort eines fremden Containers.
- **Leere REDAXO-Seite:** Frontend `/` liefert 500 (yrewrite ohne Startartikel).
  Smoke-Test zuerst gegen `/redaxo/` (200 + "REDAXO"), gesperrte Pfade
  (`/.env`) und einen Body-Check, dass `config.yml` nie ausgeliefert wird.
- **Sporadisches `curl: (6)`** beim Download im Container: erneut versuchen.

