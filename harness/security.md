# Security-Richtlinie

> Wird aus `AGENTS.md` per Lazy Loading geladen bei sicherheitsrelevanten
> Änderungen.

## Baseline (alle Stacks)

- **Input validieren, Output escapen.** Nie ungefiltert vertrauen.
- **Keine Secrets im Code.** API-Keys/Passwörter über Config/Env, nicht hartcodiert.
- **Least Privilege:** Berechtigungen so eng wie möglich.
- **Prepared Statements** für jeden DB-Zugriff mit Nutzereingaben.

## PHP / REDAXO

- **SQL:** ausschließlich `rex_sql` mit gebundenen Parametern – **nie**
  String-Konkatenation.

  ```php
  $sql = rex_sql::factory();
  $sql->setQuery(
      'SELECT * FROM ' . rex::getTable('meine_tabelle') . ' WHERE id = :id',
      ['id' => $id]
  );
  ```

- **Tabellen immer über `rex::getTable()`** (Präfix, Multi-Instance).
- **Output-Escaping:** `rex_escape($wert)` (bzw. `rex_escape($wert, 'html_attr')`
  in Attributen) für alles, was aus Daten/Eingaben in HTML landet.
- **CSRF:** Backend-Formulare/Aktionen mit `rex_csrf_token` absichern.
- **API-Funktionen:** `rex_api_function` nur mit gesetztem `$published`-Flag für
  Frontend-Zugriff; Berechtigungen prüfen.
- **Backend-Seiten:** `perm:` in `package.yml` deklarieren – sonst offen für jeden
  Backend-User.
- **Config statt Hardcoding:** Keys/Tokens über `rex_config`.
- **Logging:** Fehler via `rex_logger::logException()`, keine Secrets ins Log.

## WordPress

- **Input je Typ sanitisieren:** `sanitize_text_field()`, `absint()`,
  `sanitize_email()`, `wp_kses()` (erlaubte HTML-Whitelist).
- **Output je Kontext escapen:** `esc_html()`, `esc_attr()`, `esc_url()`,
  `esc_js()`, `wp_kses_post()` – **spät escapen**, direkt bei der Ausgabe.
- **DB:** `$wpdb->prepare()` **immer**; nie String-Konkatenation in Queries.
- **Aktionen absichern:** `wp_verify_nonce()` / `check_admin_referer()` +
  `current_user_can()` (Capability), nicht nur Rollen prüfen.
- **REST API:** jeder Endpunkt braucht einen `permission_callback` (nie
  `__return_true` für schreibende Routen) und `args`-Validierung/-Sanitisierung.
- **Dateien/Uploads:** `wp_handle_upload()`, erlaubte MIME-Types prüfen.
- **Secrets** über `wp-config.php`/Env, nicht im Code oder in der DB im Klartext.

## JS / TS / Vue

- **XSS:** Kein `v-html` mit ungeprüften Daten; kein `innerHTML` aus
  Nutzereingaben. Muss HTML gerendert werden → mit **DOMPurify** säubern.
- **Env/Secrets:** Alles mit `VITE_`-Präfix landet **im öffentlichen Bundle** –
  dort niemals Secrets. Tokens/Keys bleiben serverseitig.
- **Dependencies:** `npm audit` sauber halten; `eslint-plugin-security` beachten.
- **API-Aufrufe:** Antworten nicht blind vertrauen; Eingaben clientseitig
  validieren, aber Autorität liegt beim Server.
- **CSP:** Content-Security-Policy setzen; keine Inline-Skripte.

## Prüfung

Security-Checks aus [`verification-tools.md`](verification-tools.md) ausführen
(Composer audit / npm audit / Psalm-Taint). Bei sicherheitskritischen Änderungen
gezielt einen Test für den Angriffsfall schreiben (TDD, siehe
[`testing.md`](testing.md)).
