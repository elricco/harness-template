# Variante B: PHP-Brücke (europäisches Shared Hosting) — Default

Statisches Astro-Build + ein PHP-Endpunkt auf demselben Server. Kein
Serverless-Adapter, kein US-Mailprovider. Die Mail geht über das SMTP-Postfach
deines Hosters und bleibt in der EU. Gleiche Sicherheitsschichten wie die
Serverless-Variante.

> Überblick, Variantenwahl und die bewussten Grenzen stehen in der
> [Baustein-README](../README.md). Diese Datei beschreibt nur die
> PHP-Ausprägung.

## Dateien

```
config.example.php          -> nach ../config.php (ÜBER dem Webroot!)
api/contact.php             POST: Formular annehmen + versenden
api/form-token.php          GET:  signiertes Zeit-Token
api/lib/security.php        Token, Rate-Limit, Sanitizing
api/lib/injection.php       Prompt-Injection-Härtung (Normalisierung + Flags)
api/.htaccess               extensionless Routing, schützt lib/, Security-Header
webroot/.htaccess           Härtung: Methoden, Dotfiles, Fehlerseiten, Header
webroot/error/*.html        neutrale Fehlerseiten (self-contained)
```

## Serverstruktur

```
/kunde/                 config.php          (NICHT öffentlich)
/kunde/rl_store/        Rate-Limit-Dateien  (schreibbar, NICHT öffentlich)
/kunde/httpdocs/        Webroot: Astro-Build + api/
```

`config.php` und `rl_store/` liegen bewusst **eine Ebene über** dem Webroot –
so sind sie per URL nicht erreichbar. Passt der Pfad bei deinem Hoster nicht,
die `require`-Pfade in `api/*.php` und `RATE_LIMIT_DIR` anpassen.

## PHPMailer installieren

**Variante A – Composer** (falls verfügbar), im Ordner über dem Webroot:

```bash
composer require phpmailer/phpmailer
```

Erzeugt `vendor/` – `contact.php` findet die `vendor/autoload.php` automatisch.

**Variante B – manuell** (kein Composer auf dem Host):
Von github.com/PHPMailer/PHPMailer aus `src/` diese drei Dateien holen und nach
`api/lib/PHPMailer/` legen: `PHPMailer.php`, `SMTP.php`, `Exception.php`.
`contact.php` bindet sie als Fallback ein.

## Frontend-Anpassung

Das gemeinsame [`../frontend/Contact.astro`](../frontend/Contact.astro) gehört
nach `src/components/Contact.astro`. **Mit dem `api/.htaccess` aus diesem Ordner
(extensionless Routing) bleibt die Komponente unverändert** – `/api/contact`
trifft `contact.php`.

Nur wenn `mod_rewrite` beim Hoster nicht verfügbar ist, die beiden URLs anpassen:

```diff
- fetch("/api/form-token")
+ fetch("/api/form-token.php")

- const res = await fetch("/api/contact", {
+ const res = await fetch("/api/contact.php", {
```

Sonst bleibt die Komponente identisch – Honeypot, Token-Handling und
Fehlermeldungen funktionieren unverändert.

## Einrichten

1. Astro statisch bauen (`astro build`) und `dist/` in den Webroot laden.
2. `api/` und den Inhalt von `webroot/` (Root-`.htaccess` + `error/`) daneben in
   den Webroot legen.
3. `config.example.php` nach `../config.php` kopieren, Werte setzen.
   `FORM_SECRET` mit `openssl rand -hex 32`. **`config.php` und `rl_store/`
   gehören in die `.gitignore`** – committet wird nur `config.example.php`.
4. `rl_store/` anlegen und schreibbar machen (z.B. `chmod 700`).
5. SMTP-Zugang deines Hosters eintragen. Absenderdomain mit SPF/DKIM
   einrichten, sonst Spam-Verdacht.

## Sicherheits-Checkliste

- [x] Origin-Check (+ Referer-Fallback)
- [x] Honeypot – Fake-Erfolg statt Fehler
- [x] Signiertes Zeit-Token – HMAC + Konstantzeit-Vergleich, min. 3 s
- [x] Datei-basiertes Rate-Limit mit Lock (Shared-Hosting-tauglich)
- [x] Längen- und Formatvalidierung
- [x] Header-Injection-Schutz (`\r\n` gestrippt)
- [x] Reine Text-Mail; Besucheradresse nur als Reply-To
- [x] Fehlerdetails nur ins Log, nie an den Client
- [x] Secrets über dem Webroot, nicht abrufbar
- [x] Untrusted-Freitext entschärft + Risiko-Metadaten (`api/lib/injection.php`) –
      die verbindlichen Konsum-Regeln stehen in
      [`harness/untrusted-input.md`](../../../harness/untrusted-input.md)

## Datenschutz

Keine Formulardaten an Dritte außer deinem Hoster (EU, per AV-Vertrag
abgedeckt). Kein Captcha, kein US-Transfer, kein Consent-Banner nötig. Die IP
wird nur flüchtig fürs Rate-Limit verarbeitet. Kein Rechtsrat – Formulierung
der Datenschutzerklärung im Zweifel prüfen lassen.
