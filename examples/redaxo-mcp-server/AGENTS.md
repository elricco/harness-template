# AGENTS.md — `mcp` Addon (REDAXO MCP-Server)

> Einstiegs-System-Prompt für dieses Addon. Wird bei jeder Session geladen.
> Generische Detail-Richtlinien im mitkopierten Ordner `harness/`.

## Grundprinzipien (nicht verhandelbar)

1. **TDD** – erst Test, dann Code (Red → Green → Refactor). Jedes Tool isoliert
   getestet; JSON-RPC-Layer mit Fixtures.
2. **KISS** – einfacher, lesbarer Code.
3. **YAGNI** – nur den aktuellen MVP-Scope bauen (siehe Roadmap im Design).
4. **Core-First** – erst REDAXO-Core-APIs nutzen (`rex_sql`, `rex_console_command`,
   `rex_logger`, Structure-API), bevor Eigenes entsteht.
5. **REDAXO/Symfony-Konventionen** achten.

## Projekt-Kontext

- **Was:** Addon `mcp` macht REDAXO als **MCP-Server** (JSON-RPC 2.0 over HTTP)
  verfügbar – REDAXO-Pendant zu Novamira.
- **Transport:** über das **FriendsOfREDAXO/api**-Addon (Pflicht-Dependency);
  dessen Bearer-Token + Scopes sind die Auth-Schicht. **Scope == Routenname.**
- **Tiers/Routen:** `mcp/read`, `mcp/structure`, `mcp/console` (Phase 2: `mcp/full`).
- **Sicherheit:** Default-deny. Nicht-`read`-Tiers nur in Dev/Staging
  (`EnvironmentGuard`). Console nur per Whitelist. Jeder Call wird auditiert.
- **Ziel:** REDAXO 5.21.1, PHP 8.1+. Namespace `FriendsOfRedaxo\MCP\` (PSR-4, `lib/`).

## Architektur / Schichtung

Trenne Protokoll-/Domänenlogik von der REDAXO-Integration (Details:
[`harness/architecture.md`](harness/architecture.md)):

```
lib/
├── Domain/          # KEIN rex_* → unit-testbar
│   ├── McpServer        (JSON-RPC: initialize / tools/list / tools/call)
│   ├── Tool             (Interface: name/description/category/inputSchema/
│   │                     outputSchema/tier/run)
│   └── ToolRegistry     (Tier-Filter, baut tools/list aus Schemas)
└── Redaxo/          # darf rex_* nutzen
    ├── McpRoutePackage  (registriert Tier-Routen + BearerAuth)
    ├── McpController     (Einstieg pro Route, Tier → McpServer)
    ├── Tools/…           (db.query via rex_sql, console.run, article.edit, …)
    ├── EnvironmentGuard  (rex-Config / rex::isDebugMode())
    └── AuditLogger       (rex_logger)
```

Abhängigkeit nur **Redaxo → Domain**, nie umgekehrt (per Deptrac erzwungen).

## Entwicklungs-Workflow

Spec → Test (Red) → Code (Green) → Refactor → Verifikation → Spec-Sync. Die
Specs sind das REDAXO-MCP-Server-Design-Dokument und der zugehörige
Implementierungsplan (bei Repo-Start ins `specs/`-Format überführen).
Details: [`harness/workflow.md`](harness/workflow.md).

## Lazy Loading – lies VOR der Arbeit im Bereich

- Architektur → [`harness/architecture.md`](harness/architecture.md)
- Code → [`harness/coding-style.md`](harness/coding-style.md)
- Tests → [`harness/testing.md`](harness/testing.md)
- Security → [`harness/security.md`](harness/security.md)
- Specs → [`harness/spec-driven.md`](harness/spec-driven.md)

## Verifikation nach jeder Code-Änderung (verbindlich)

```bash
vendor/bin/phpunit                                 # Tests zuerst (TDD)
vendor/bin/php-cs-fixer fix --dry-run --diff       # Stil (@Symfony + @PSR12)
php redaxo/bin/console rexstan:analyze             # PHPStan via rexstan (Ziel: max)
vendor/bin/deptrac analyse                         # Domain ⊥ rex_*
composer audit                                     # CVEs
gitleaks protect --staged                          # keine Secrets im Commit
```

Befunde beheben, bevor eine Aufgabe als erledigt gilt.

## Security-Leitplanken (dieses Addon)

- Jedes Tool prüft sein `tier()`; `ToolRegistry` gibt nur Tier-konforme Tools aus.
- `db.query` & Co. ausschließlich über `rex_sql` mit gebundenen Parametern.
- `console.run` nur gegen die Command-Whitelist – kein beliebiges Exec.
- Kein Tool ohne Audit-Log-Eintrag.
