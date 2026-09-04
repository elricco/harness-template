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
