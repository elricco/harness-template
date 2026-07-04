# harness-template

Wiederverwendbarer **Harness für KI-Coding-Agenten** (Claude Code, Codex,
Copilot, lokale Modelle) – tool-agnostisch als Markdown, deterministisch
geprüft durch statische Analyse.

Basierend auf dem Harness-Modell von David Tielke: Der Agent ist wie ein neuer
Kollege am ersten Tag – alles, was er wissen muss, steht in ladbaren
Richtlinien statt im Kopf des Teams.

## Die drei Säulen

1. **`AGENTS.md`** als schlanker Einstiegs-System-Prompt, den jedes Tool bei
   Session-Start lädt.
2. **Lazy Loading:** Detailwissen liegt in `harness/` und wird nur bei Bedarf
   gelesen (Architektur, Coding-Style, Tests, Security, Barrierefreiheit,
   Datenschutz, Design, Specs).
3. **Deterministische Verifikation:** Statische Tools (PHPStan/rexstan,
   PHP-CS-Fixer, Deptrac, ESLint, dependency-cruiser, axe/pa11y …) prüfen nach
   jeder Änderung – kein zweiter, nicht-deterministischer Agent.

## Nutzung

1. `AGENTS.md` + `harness/` in den **Repo-Root** deines Projekts kopieren.
2. Die `TODO`-Marker in `AGENTS.md` füllen (Projekt-Kontext,
   Verifikations-Kommandos).
3. Passende Configs aus `configs/` in den Repo-Root kopieren und
   Pfade/Namespaces anpassen (siehe [`configs/README.md`](configs/README.md)).
4. Im Projekt notieren, welche **Vorlagen-Version** kopiert wurde (steht im
   Kopf der `AGENTS.md`).

## Verbindliche Grundprinzipien der Vorlage

TDD (Red → Green → Refactor), KISS, YAGNI, Core-First (REDAXO),
Framework-Konventionen (REDAXO/Symfony), Barrierefreiheit (WCAG 2.2 AA / BFSG)
und Datenschutz (DSGVO, Privacy by Design).

## Stacks

Ausgearbeitet für **PHP/REDAXO** (tief), **JS/TS/Vue** und **WordPress**
(solide). Das Prinzip ist stack-neutral – weitere Stacks brauchen nur eigene
Sektionen in den Richtlinien plus passende Analyse-Tools.

## Beispiele

[`examples/redaxo-mcp-server/`](examples/redaxo-mcp-server/) zeigt die Vorlage
zugeschnitten auf ein echtes Projekt (REDAXO-Addon `mcp`).

## Rückfluss-Regel (gegen Template-Drift)

Erkenntnisse aus Projekten wandern **zurück in dieses Repo** (per Commit/PR),
nicht nur in die Projekt-Kopie. Versionen werden getaggt (`vX.Y.Z`); Projekte
vergleichen ihre kopierte Version bei Bedarf mit dem aktuellen Stand.
