# Verifikations-Tools

> Deterministische, statische Analyse statt zweitem (nicht-deterministischem)
> Agenten. Wird aus `AGENTS.md` nach jeder Code-Änderung ausgeführt.
>
> David Tielkes .NET-Setup: ReSharper (Code) + NDepend (Architektur). Unser
> Web-Stack-Äquivalent unten.

## Mapping je Stack

| Zweck            | PHP / REDAXO                         | JS/TS / Vue                  | WordPress             |
| ---------------- | ------------------------------------ | ---------------------------- | --------------------- |
| Code-Stil        | PHP-CS-Fixer (`@Symfony`, PSR-12)    | Prettier                     | PHPCS (WPCS)          |
| Statik/Typen     | PHPStan (hohes Level)                | ESLint, `tsc --noEmit`       | PHPStan + WP-Stubs    |
| Architektur      | Deptrac (Domain ⊥ rex_*)             | dependency-cruiser           | Deptrac               |
| Security         | Psalm (taint), Composer audit        | `npm audit`, eslint-security | WPScan-Regeln         |
| Barrierefreiheit | pa11y-ci / Lighthouse (gerenderte Seiten) | eslint-plugin-vuejs-accessibility, axe-core (vitest/Playwright), pa11y-ci | pa11y-ci / Lighthouse |
| Datenschutz      | Externe-Hosts-Scan, PII-Log-Scan     | Externe-Hosts-Scan im Bundle | Externe-Hosts-Scan    |
| Tests (TDD)      | PHPUnit                              | Vitest / Playwright          | PHPUnit + WP-Test-Lib |

## Kopierfertige Configs

Vorlagen für alle Tools liegen unter [`../configs/`](../configs/) (PHP, JS, WordPress)
– in den Repo-Root kopieren und Pfade/Namespaces anpassen. Siehe
[`../configs/README.md`](../configs/README.md).

## Konkrete Aufrufe (pro Projekt füllen/verifizieren)

### PHP / REDAXO
```bash
# TODO: an Projekt anpassen
vendor/bin/phpunit                                   # Tests zuerst (TDD)
vendor/bin/php-cs-fixer fix --dry-run --diff         # Stil (@Symfony-Ruleset)
vendor/bin/phpstan analyse --memory-limit=512M       # Statik/Typen
vendor/bin/deptrac analyse                           # Architektur-Schichten
composer audit                                       # bekannte CVEs
```

- **PHP-CS-Fixer:** `.php-cs-fixer.dist.php` mit `@Symfony` + `@PSR12`.
- **PHPStan:** Level schrittweise anheben; Ziel möglichst hoch.
  - **In REDAXO-Addons idiomatisch über `rexstan`** statt rohem `phpstan.neon`:
    `php redaxo/bin/console rexstan:analyze`. rexstan bringt REDAXO-Stubs mit und
    verwaltet die Baseline. Die generische `phpstan.neon.dist` nur für Nicht-REDAXO-
    PHP nutzen.
- **Deptrac:** Regel erzwingt, dass `Domain` **nicht** von der Redaxo-/rex_*-Schicht
  abhängt (siehe [`architecture.md`](architecture.md)).

### JS / TS / Vue
```bash
# TODO: an Projekt anpassen
pnpm test                 # Vitest (TDD)
pnpm eslint .
pnpm prettier --check .
pnpm tsc --noEmit
pnpm depcruise src
npm audit
```

### WordPress
```bash
# TODO: an Projekt anpassen
vendor/bin/phpunit
vendor/bin/phpcs --standard=WordPress
vendor/bin/phpstan analyse   # mit szepeviktor/phpstan-wordpress
```

### Barrierefreiheit (alle Stacks mit UI)
```bash
# TODO: URLs/Setup an Projekt anpassen (lokale Instanz muss laufen)
pnpm pa11y-ci                # gegen gerenderte Seiten, WCAG 2.2 AA (.pa11yci)
pnpm lighthouse <url> --only-categories=accessibility --quiet   # Smoke-Check
```

- **pa11y-ci** prüft gerenderte Seiten – funktioniert für REDAXO, WordPress und
  SPAs gleich. Config-Vorlage: [`../configs/a11y/.pa11yci`](../configs/a11y/.pa11yci).
- **axe-core** läuft zusätzlich in Komponenten-/E2E-Tests (`vitest-axe`,
  `@axe-core/playwright`) – damit ist A11y Teil des normalen TDD-Laufs.
- Automatisierte Checks finden nur ~30–50 % der Verstöße → manuelle Kurzprüfung
  aus [`accessibility.md`](accessibility.md) bleibt Pflicht.

### Datenschutz (alle Stacks)
```bash
# Externe-Hosts-Scan: ungewollte Dritt-Requests im Quelltext/Build finden.
# TODO: Pfade anpassen; Treffer sind nur ok, wenn consent-gated (privacy.md).
grep -rniE 'fonts\.(googleapis|gstatic)|unpkg\.com|jsdelivr|cdnjs|youtube\.com/embed|maps\.googleapis|google-analytics|googletagmanager|connect\.facebook' \
  src/ lib/ templates/ --include='*.{php,js,ts,vue,html,twig}'

# PII-Log-Scan: E-Mail-Adressen/IPs in Log-Aufrufen aufspüren.
grep -rniE '(logger|rex_logger|error_log|console\.(log|error)).*(\$email|\$ip|e-?mail)' src/ lib/
```

## Regeln

- **Tests laufen zuerst** (grün), dann Stil/Statik/Architektur.
- Nur **geänderte** Dateien prüfen, wenn möglich (schneller).
- Befunde beheben, bevor eine Aufgabe als erledigt gilt.
