# Worked Example: Harness für das `mcp`-Addon (REDAXO MCP-Server)

Die Vorlage aus dem Repo-Root, zugeschnitten auf ein echtes Projekt: das
REDAXO-Addon `mcp`, das REDAXO als MCP-Server (JSON-RPC 2.0 over HTTP)
verfügbar macht (REDAXO 5.21.1, Transport über das FriendsOfREDAXO/api-Addon).

> Diese Datei dokumentiert die konkrete Ausprägung; beim Anlegen des Addon-Repos
> kommt die [`AGENTS.md`](AGENTS.md) in den Addon-Root und die generischen
> `harness/`-Richtlinien werden mitkopiert.

## Was der Trockenlauf bestätigt hat

- **Schichtung passt:** Die Design-Komponenten fallen sauber in `Domain` (rex-
  agnostisch, unit-testbar) vs. `Redaxo` (Integration):
  - **Domain:** `McpServer` (JSON-RPC – „kennt REDAXO nicht im Detail"),
    `Tool`-Interface, `ToolRegistry`-Filterlogik.
  - **Redaxo:** `McpRoutePackage`, `McpController`, konkrete Tools (`rex_sql`,
    Console, Structure), `EnvironmentGuard` (rex-Config), `AuditLogger`
    (`rex_logger`).
  - Deptrac-Regel `Domain ⊥ rex_*` ist damit sinnvoll erzwingbar.
- **TDD greift:** Das Design will ohnehin „PHPUnit für Tool-Logik (jedes Tool
  isoliert)" und „JSON-RPC-Layer mit Fixtures" – deckt sich mit
  [`../../harness/testing.md`](../../harness/testing.md).

## Was der Trockenlauf an der Vorlage verbessert hat

- **PHPStan in REDAXO läuft über `rexstan`** (`rexstan:analyze`), nicht über ein
  rohes `phpstan.neon`. Ist in
  [`../../harness/verification-tools.md`](../../harness/verification-tools.md)
  vermerkt.

## Offene Anpassungen beim Repo-Start

- Namespace final festlegen (Annahme in der AGENTS.md: `FriendsOfRedaxo\MCP\`).
- api-Addon als `requires_addons` in `package.yml`.
- rexstan als Dev-Dependency + Baseline für Altlasten.
- Design-Dokument + Implementierungsplan ins `specs/`-Format überführen
  (siehe [`../../harness/spec-driven.md`](../../harness/spec-driven.md)).
