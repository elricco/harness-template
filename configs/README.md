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

**Vanilla Web (ohne Framework):** Beide Vorlagen sind auf Vue zugeschnitten –
beim Kopieren anpassen (Hinweise stehen als `TEMPLATE:`-Kommentar in den
Dateien): vue-Plugin raus, Vitest-Environment `node` + jsdom-Pragma je Datei,
dependency-cruiser-Schichtnamen auf die eigene Struktur umschreiben.
Praxisbeispiel: ein Vanilla-JS-Projekt
(Schichtung `core ← render ← backgrounds`).

**Astro:** vue-Plugin raus (kein Vue), Vitest über `getViteConfig()` aus
`astro/config` wrappen, wenn Tests `astro:content` o. ä. importieren (offizielles
Astro+Vitest-Muster – Achtung: kann zu zwei koexistierenden vite-Major-Versionen
im `node_modules`-Baum führen, wenn eine weitere Abhängigkeit `vite@8` als Peer
zieht; im Zweifel mit `pnpm why vite` prüfen und den Typkonflikt gezielt mit
`@ts-expect-error` an der betroffenen Stelle dokumentieren statt zu unterdrücken).
dependency-cruiser-Schichtnamen auf `content ← layouts ← components/pages`
umschreiben (siehe [`../harness/architecture.md`](../harness/architecture.md)),
`.astro`-Dateien vom Cruise ausschließen (`exclude` in der Config – keine
verlässliche Auflösung, Stand Astro 5). Praxisbeispiel: das Projekt
einem Astro-Projekt.

## WordPress (`wordpress/`)

| Datei                 | Tool     | Zweck                              |
| --------------------- | -------- | ---------------------------------- |
| `phpcs.xml.dist`      | PHPCS    | WordPress Coding Standards (WPCS)  |
| `phpstan.neon.dist`   | PHPStan  | Statik mit WP-Stubs                |

## Barrierefreiheit (`a11y/`) – stack-übergreifend

| Datei               | Tool          | Zweck                                                |
| ------------------- | ------------- | ----------------------------------------------------- |
| `.pa11yci`          | pa11y-ci      | WCAG 2.2 AA gegen gerenderte Seiten (URLs anpassen!)  |
| `lighthouserc.json` | Lighthouse CI | Accessibility-/SEO-Score als Build-Gate (URLs anpassen!) |

Installation:
```bash
pnpm add -D pa11y-ci puppeteer
```

axe-core in Tests (`vitest-axe`, `@axe-core/playwright`) und
`eslint-plugin-vuejs-accessibility` laufen über die normalen Test-/Lint-Setups
(siehe [`../harness/accessibility.md`](../harness/accessibility.md)).

**Chrome in CI (Lektion aus Astro-Projekt, 2026-08):** pa11y-ci und
Lighthouse CI brauchen beide ein echtes Chrome. Zwei Fallstricke, die genau
dann auftreten, wenn es lokal funktioniert und in der CI nicht (oder erst
nach Tagen unbemerkt bleiben):

- **Chrome fehlt ganz** ("Could not find Chrome"): `pnpm` blockt seit v10
  standardmäßig Install-Skripte von Abhängigkeiten – auch das von
  `puppeteer`, das den Chrome-Download macht. Verlässlicher als
  `pnpm.onlyBuiltDependencies` in package.json (pnpm-Versions- und
  Cache-abhängig, hat in einer echten CI trotz korrekter Config nicht
  gegriffen) ist ein **expliziter Install-Schritt**, der unabhängig von
  pnpm-Version/Store-Cache immer läuft:
  ```bash
  pnpm exec puppeteer browsers install chrome
  ```
  Dafür muss `puppeteer` eine **direkte** devDependency sein (nicht nur
  transitiv über `pa11y-ci`), sonst findet `pnpm exec` das Binary nicht.
  Diesen Schritt vor `pnpm build`/dem eigentlichen Testlauf einfügen (bei
  pa11y-ci z. B. direkt im `a11y`-Package-Script).
- **Chrome startet nicht** ("FATAL: No usable sandbox!"): GitHub-Actions-
  Runner (Ubuntu 24.04+) haben unprivilegierte User-Namespaces per AppArmor
  deaktiviert – Chromes eigener Sandbox-Mechanismus kann sich dort nicht
  initialisieren. Fix: `--no-sandbox` (+ optional
  `--disable-setuid-sandbox`) als Chrome-Launch-Flag, siehe `.pa11yci` in
  diesem Ordner. Vertretbar, weil beide Tools nur die eigene, gerade
  gebaute Seite besuchen – keine fremden/untrusted URLs, für die der
  Sandbox-Schutz eigentlich gedacht ist.

**Lighthouse-Preset auf schlanken Seiten (dieselbe Lektion):**
`lighthouse:recommended` asserted `minScore` gegen **jeden** Einzelaudit.
Auf einer Seite ohne Bilder/Animationen liefern manche Audits
(`lcp-lazy-loaded`, `non-composited-animations`, `prioritize-lcp-image`)
grundsätzlich keinen Score (`null`/`NaN`, "nicht anwendbar") – das zählt bei
einer `minScore`-Assertion fälschlich als Fail, nicht als Bestanden. In
`lighthouserc.json` hier bereits auf `"off"` gesetzt; bei eigenen Anpassungen
gezielt nur die wirklich nicht anwendbaren Audits abschalten, nicht das
ganze Preset verwerfen – `errors-in-console` und `link-text` etc. sind
echte, sinnvolle Checks.
