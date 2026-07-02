# Verifikations-Configs (Vorlagen)

Kopierfertige Konfigurationen für die statischen Tools aus
[`../harness/verification-tools.md`](../harness/verification-tools.md).

**Nutzung:** Die passenden Dateien in den **Repo-Root** kopieren (nicht in
Unterordnern lassen) und Pfade/Namespaces an dein Projekt anpassen. Die `.dist`-
Endung ist Konvention für versionierte Vorlagen, die pro Umgebung überschrieben
werden dürfen.

## PHP / REDAXO (`php/`)

| Datei                     | Tool          | Zweck                                   |
| ------------------------- | ------------- | --------------------------------------- |
| `.php-cs-fixer.dist.php`  | PHP-CS-Fixer  | Stil: `@PSR12` + `@Symfony`, strict_types |
| `phpstan.neon.dist`       | PHPStan       | Statik/Typen                            |
| `deptrac.yaml`            | Deptrac       | Domain ⊥ rex_* (Schichtregel)           |
| `phpunit.xml.dist`        | PHPUnit       | Tests (TDD)                             |

Installation (Composer, im Addon):
```bash
composer require --dev friendsofphp/php-cs-fixer deptrac/deptrac phpunit/phpunit
```

**PHPStan in REDAXO-Addons:** nicht roh installieren, sondern **rexstan**
verwenden (`php redaxo/bin/console rexstan:analyze`) – bringt REDAXO-Stubs und
Baseline-Verwaltung mit. Die `phpstan.neon.dist` hier ist nur für
Nicht-REDAXO-PHP gedacht (siehe
[`../harness/verification-tools.md`](../harness/verification-tools.md)).

## JS / TS / Vue (`js/`)

| Datei                       | Tool               | Zweck                     |
| --------------------------- | ------------------ | ------------------------- |
| `.dependency-cruiser.cjs`   | dependency-cruiser | Schichtregeln erzwingen   |
| `vitest.config.ts`          | Vitest             | Tests (TDD)               |

ESLint/Prettier laufen i. d. R. über die Projekt-Presets (flat config); hier
bewusst nicht dupliziert.

## WordPress (`wordpress/`)

| Datei                 | Tool     | Zweck                              |
| --------------------- | -------- | ---------------------------------- |
| `phpcs.xml.dist`      | PHPCS    | WordPress Coding Standards (WPCS)  |
| `phpstan.neon.dist`   | PHPStan  | Statik mit WP-Stubs                |

## Barrierefreiheit (`a11y/`) – stack-übergreifend

| Datei       | Tool     | Zweck                                        |
| ----------- | -------- | -------------------------------------------- |
| `.pa11yci`  | pa11y-ci | WCAG 2.2 AA gegen gerenderte Seiten (URLs anpassen!) |

Installation:
```bash
pnpm add -D pa11y-ci
```

axe-core in Tests (`vitest-axe`, `@axe-core/playwright`) und
`eslint-plugin-vuejs-accessibility` laufen über die normalen Test-/Lint-Setups
(siehe [`../harness/accessibility.md`](../harness/accessibility.md)).
