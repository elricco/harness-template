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

## Docker-Entwicklungsumgebung (`docker/`) – stack-übergreifend

| Datei                       | Zweck                                                       |
| ---------------------------- | ------------------------------------------------------------ |
| `Dockerfile.php.dist`        | Gepinntes Sprachversions-Image (PHP-Beispiel)                |
| `docker-compose.yml.dist`    | `app`-Service + `mailer`-Service (Mailpit, Mailversand lokal sichtbar) |

Details, Rationale und Nutzung: [`docker/README.md`](docker/README.md).

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

**pa11y deterministisch machen (Lektion aus einer Astro-Website, 2026-10):**
Die `.pa11yci` hier setzt zwei Chrome-Flags, beide aus einem echten
CI-Fehlschlag:

- `--blink-settings=preferredColorScheme=1` legt den Farbmodus fest
  (1 = hell, 0 = dunkel). Ohne das entscheidet das Betriebssystem: lokal auf
  macOS (dunkel) war alles grün, in der CI (Linux, hell) nicht. Hat ein
  Projekt einen Dark Mode, **beide Modi prüfen** (zweite Config mit `0`) –
  nicht per Klick auf einen Theme-Toggle: pa11y misst dann mitten in
  CSS-Transitions und meldet Phantom-Kontrastfehler.
- `--force-prefers-reduced-motion`: Scroll-Animationen starten Inhalte mit
  `opacity: 0`, und pa11y **überspringt unsichtbare Elemente** – geprüft
  wurde praktisch nur der erste Bildschirm. Mit reduzierter Bewegung ist
  alles sofort sichtbar (setzt voraus, dass die Animationen
  `prefers-reduced-motion` respektieren).
- **Belegter Port = falsche Seite:** `start-server-and-test` nutzt einen
  bereits belegten Port stillschweigend mit. Läuft dort noch ein
  Preview-Server eines **anderen** Projekts, prüft pa11y dessen Seiten. Vor
  lokalen Läufen den Port freimachen (`lsof -ti tcp:4321 | xargs kill`).
- Kein `pa11y`-Einzel-CLI voraussetzen: installiert ist nur `pa11y-ci`;
  für eine einzelne URL eine Mini-Config übergeben.
- pa11y-ci akzeptiert auch `.pa11yci.cjs` – dann sind Kommentare zu
  Ausnahmen (`hideElements` für Fehlalarme) direkt in der Config möglich.

**Lighthouse-Bericht nicht öffentlich hochladen (dieselbe Lektion):**
`upload.target: "temporary-public-storage"` legt jeden Bericht öffentlich
abrufbar bei Google ab – auch aus privaten Repos und von noch nicht
veröffentlichten Seiten. Die Vorlage nutzt deshalb `"filesystem"`; in der
CI den Ordner per `actions/upload-artifact` (mit `if: always()`) an den Lauf
hängen. Weitere Lighthouse-Fallen aus demselben Projekt:

- `staticDistDir` muss zum **tatsächlichen** Build-Output passen. Astro mit
  `@astrojs/node`-Adapter legt Seiten unter `dist/client`, ohne Adapter
  direkt in `dist/` – ein veralteter Pfad liefert Lighthouse nur 404.
- Seiten mit `noindex` vor dem Livegang: `collect.settings.skipAudits:
  ["is-crawlable"]` **und** die Assertion `"is-crawlable": "off"` – das
  Preset verlangt sonst, dass jede Prüfung gelaufen ist (`auditRan`).
  Beides beim Livegang entfernen.
- `offscreen-images` kann **nur in der CI** anschlagen: Lighthouse wertet
  ein Bild außerhalb des ersten Bildschirms erst als Fehler, wenn es vor
  dem Zeitpunkt der Interaktivität geladen ist – auf der langsameren
  CI-CPU trifft das zu, lokal nicht (auch nicht mit
  `--settings.throttling.cpuSlowdownMultiplier`). Ursache in
  Astro-Website war ein **CSS-Hintergrundbild** (249 KB) in einem
  CTA-Band weit unten – CSS-Hintergründe kennen kein `loading="lazy"`
  (Lösung: `harness/architecture.md`, Astro). Korrektur zu v2.9.1: Dort
  stand, ein `eager`-Porträt sei die Ursache gewesen – das war eine
  ungeprüfte Annahme, der Bericht zeigte etwas anderes.
- **Erst den Bericht lesen, dann fixen.** Der CI-Log nennt nur die
  Prüfung, nicht das Element. Welches Bild/Element betroffen ist, steht im
  Bericht (`upload.target: "filesystem"` + Artefakt, siehe oben). Ohne ihn
  wird aus der Seitenstruktur geraten – in einer Astro-Website kostete das
  einen zusätzlichen, wirkungslosen Fix-Zyklus.
- `errors-in-console` schlägt an, wenn die Seite beim Laden ein Backend
  anfragt, das im statischen Test fehlt (z. B. `/api/form-token`). Dann als
  Warnung führen, nicht abschalten – echte JS-Fehler bleiben so sichtbar.

**`staticDistDir` vs. Live-Server (Lektion aus einem Symfony-Rebuild-Projekt,
2026-09):** `lighthouserc.json` hier ist per `staticDistDir: "./dist"` auf
statische Build-Ausgaben zugeschnitten (Astro, Eleventy, Hugo …). Bei jedem
Stack mit einem echten Live-Server (Symfony, WordPress, jedes klassische
Backend-Framework) **ignoriert Lighthouse CI den laufenden Server komplett**,
sobald `staticDistDir` gesetzt ist – der CI-Job "besteht" grün, ohne jemals
eine Seite gemessen zu haben, weil nichts einen Fehler wirft. Das ist ein
stiller Fail-Open, keine Fehlermeldung. Für live-server-basierte Stacks
stattdessen:

```json
{
  "ci": {
    "collect": {
      "url": ["http://localhost:8000/", "http://localhost:8000/kontakt"],
      "startServerCommand": "php -S 0.0.0.0:8000 -t public",
      "startServerReadyPattern": "Development Server \\(http"
    }
  }
}
```

`staticDistDir` und `collect.url`/`startServerCommand` schließen sich
gegenseitig aus – beim Kopieren dieser Vorlage in ein Live-Server-Projekt
`staticDistDir` **entfernen**, nicht nur die URL-Liste anpassen.
