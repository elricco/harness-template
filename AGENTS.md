# AGENTS.md

> Einstiegs-System-Prompt für KI-Coding-Agenten (Claude Code, Codex, Copilot,
> lokale Modelle). Diese Datei wird bei jeder neuen Session geladen. Halte sie
> schlank – Detailwissen liegt im Ordner `harness/` und wird per Lazy Loading
> nachgeladen.
>
> <!-- TEMPLATE: Kopiere diesen Ordner (AGENTS.md + harness/) in den Root deines Repos und passe die TODOs an. -->
> <!-- Vorlagen-Version: 2.0.0 (2026-07-02) - https://github.com/elricco/harness-template -->

## Mindset

Du bist wie ein neuer Kollege am ersten Tag. Lies die unten verlinkten
Richtlinien, bevor du im jeweiligen Bereich arbeitest.

## Grundprinzipien (nicht verhandelbar)

1. **Test Driven Development.** Erst der Test, dann der Code. Red → Green →
   Refactor. Kein Produktivcode ohne vorher fehlschlagenden Test. Details:
   [`harness/testing.md`](harness/testing.md).
2. **KISS.** Einfacher, für Menschen lesbarer Code schlägt cleveren Code.
3. **YAGNI.** Der beste Code ist der, der nicht geschrieben werden musste. Baue
   nur, was die aktuelle Spec verlangt – keine Vorratsabstraktionen.
4. **Core-First (REDAXO).** Nutze immer erst die vom REDAXO-Core bereitgestellten
   Funktionen und Klassen, bevor du im Addon eigene baust. Details:
   [`harness/architecture.md`](harness/architecture.md).
5. **Framework-Konventionen achten.** REDAXO- und Symfony-Konventionen folgen,
   nicht dagegen arbeiten.
6. **Barrierefreiheit (WCAG 2.2 AA).** UI-Code ist erst fertig, wenn er
   zugänglich ist – semantisches HTML, Tastatur-Bedienbarkeit, Kontraste.
   Details: [`harness/accessibility.md`](harness/accessibility.md).
7. **Datenschutz (DSGVO, Privacy by Design).** Datenminimierung, keine externen
   Requests/Tracker ohne Consent, PII löschbar halten. Details:
   [`harness/privacy.md`](harness/privacy.md).

## Projekt-Kontext

<!-- TODO: Kurzbeschreibung des Projekts, Stack, Domäne, wichtigste Verzeichnisse. -->

## Entwicklungs-Workflow (verbindlich)

Folge dem Ablauf in [`harness/workflow.md`](harness/workflow.md):
Spec → Test (Red) → Code (Green) → Refactor → Verifikation → Spec-Sync. Details dort.

## Lazy Loading – lies VOR der Arbeit im jeweiligen Bereich

- **Bevor du an der Architektur arbeitest** → lies [`harness/architecture.md`](harness/architecture.md)
- **Bevor du Code schreibst/änderst** → lies [`harness/coding-style.md`](harness/coding-style.md)
- **Bevor du Tests schreibst** → lies [`harness/testing.md`](harness/testing.md)
- **Bei sicherheitsrelevanten Änderungen** → lies [`harness/security.md`](harness/security.md)
- **Bei UI-/Template-/Frontend-Arbeit** → lies [`harness/accessibility.md`](harness/accessibility.md)
- **Bei personenbezogenen Daten, Formularen, Tracking, externen Diensten** → lies [`harness/privacy.md`](harness/privacy.md)
- **Beim Umgang mit Anforderungen/Specs** → lies [`harness/spec-driven.md`](harness/spec-driven.md)

## Verifikation nach jeder Code-Änderung (verbindlich)

Sobald du Quellcode geändert hast, führe die für den Stack passenden statischen
Analysen aus und korrigiere Befunde, bevor du weitermachst. Konkrete Kommandos:
[`harness/verification-tools.md`](harness/verification-tools.md).

<!-- TODO: Die für dieses Projekt gültigen Aufrufe hier explizit eintragen, z. B.:
- `vendor/bin/phpstan analyse --memory-limit=512M`
- `vendor/bin/php-cs-fixer fix --dry-run --diff`
-->

## Spec-Synchronität

Zu geändertem Code gehört immer eine Spec. Nach erfolgreicher Verifikation halte
die zugehörige Spec synchron (siehe [`harness/spec-driven.md`](harness/spec-driven.md)).
