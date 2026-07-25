# Baustein: Kontaktformular (statische Site, DSGVO-arm, ohne Captcha)

Wiederverwendbarer Baustein für den häufigsten dynamischen Teil einer sonst
statischen Website: **ein Kontaktformular**. Zwei austauschbare Backend-Varianten
mit identischen Sicherheitsschichten und identischem Frontend.

> Herkunft: Vordenken in einem Astro-Projekt (Juli 2026), übernommen als
> Baustein. Die generalisierten Regeln stehen in den Richtlinien –
> [`harness/security.md`](../../harness/security.md) (Schichten öffentlicher
> Endpunkte), [`harness/privacy.md`](../../harness/privacy.md) (Spam-Schutz ohne
> Drittanbieter) und [`harness/untrusted-input.md`](../../harness/untrusted-input.md)
> (Prompt Injection). Dieser Ordner ist die lauffähige Ausprägung davon.

## Architektur-Entscheidung

- Der Site-Generator (hier Astro) liefert **reines HTML/CSS/JS** aus – kein
  laufendes Backend, das übers Frontend angreifbar wäre.
- Das Formular ist der **einzige** dynamische Teil und strikt vom statischen
  Rest getrennt. Siehe „Statisch by default" in
  [`harness/architecture.md`](../../harness/architecture.md).
- **Grundregel:** keine Secrets im Frontend. Alles, was der Browser sieht, ist
  öffentlich – API-Keys/SMTP-Passwörter nur serverseitig (env bzw. Config über
  dem Webroot).

## Variantenwahl

|                   | `serverless/`                         | `php/`                                  |
| ----------------- | ------------------------------------- | --------------------------------------- |
| Ziel-Host         | Cloudflare / Vercel / Netlify         | europäisches Shared Hosting             |
| Mailversand       | Resend (US-API)                       | PHPMailer + SMTP des Hosters (EU)       |
| Rate-Limit        | In-Memory (instanzgebunden, schwach)  | Datei-basiert mit `flock` (stabil)      |
| DSGVO-Fußabdruck  | Mailprovider = US-Drittanbieter       | nur der Hoster (AV-Vertrag)             |
| Adapter nötig     | ja (`astro add …`)                    | nein                                    |

**Default-Empfehlung: `php/`.** Kein Drittlandtransfer, verlässlicheres
Rate-Limit, keine Adapter-Abhängigkeit. `serverless/` ist die dokumentierte
Alternative, wenn ohnehin auf einer Edge-Plattform deployt wird.

## Aufbau

```
frontend/Contact.astro     gemeinsames Formular (Honeypot + Client-Script)
serverless/                Variante A – siehe serverless/README.md
php/                       Variante B – siehe php/README.md
```

`Contact.astro` gehört im Zielprojekt nach `src/components/Contact.astro`. Der
einzige Unterschied zwischen den Varianten sind die beiden Endpunkt-URLs im
`<script>`:

- Serverless: `/api/form-token`, `/api/contact`
- PHP mit extensionless Routing: **identisch** (`api/.htaccess` blendet `.php` aus)
- PHP ohne Rewrite: `/api/form-token.php`, `/api/contact.php`

## Sicherheitsschichten (Reihenfolge im Endpunkt beibehalten)

Jede Schicht filtert früh, damit teure Schritte (Mailversand) nur bei sauberen
Requests laufen. Die generelle Regel dazu steht in
[`harness/security.md`](../../harness/security.md#öffentliche-endpunkte--formulare-unauthentifiziert).

1. **Methode + Origin** – nur POST von der eigenen Domain, kein offener Relay.
2. **Rate-Limit pro IP** – grobe erste Barriere gegen Flooding.
3. **Honeypot** (`company`) – ausgefüllt = Bot → **Fake-Erfolg (200)**, damit der
   Bot nichts lernt.
4. **Signiertes Zeit-Token** – HMAC-SHA256 über einen Timestamp: prüft *Echtheit*
   und *Alter* (min. 3 s). Vergleich in **Konstantzeit**.
5. **Validierung + Längenlimits.**
6. **Header-Injection-Schutz** – `\r\n` aus allem strippen, was in Mail-Header
   wandert (`Reply-To`, `Subject`).
7. **Versand** – reiner Text statt HTML, `From` = eigenes Postfach (SPF/DKIM),
   Besucheradresse nur als `Reply-To`, Fehlerdetails nur ins Log.

## Prompt-Injection-Härtung

Kontaktnachrichten sind untrusted Freitext. Falls sie später eine KI verarbeitet
(Triage, Zusammenfassung, Auto-Reply), tut der Baustein zwei Dinge – und **löst
das Problem damit nicht**:

- **Intake-Entschärfung** (`injection.php` / `untrusted.ts`): NFKC-Normalisierung,
  Entfernen unsichtbarer Codepoints (Zero-Width, Bidi, Unicode-TAG-Zeichen, die
  unsichtbaren ASCII schmuggeln), heuristische Muster-Flags → `low|medium|high`.
- **Metadaten in der Mail:** Header `X-Untrusted-Content`, `X-Injection-Risk`,
  Body-Delimiter `BEGIN/END UNTRUSTED MESSAGE`.

Die eigentliche Verteidigung sitzt auf der **Konsum-Seite** und ist verbindlich
in [`harness/untrusted-input.md`](../../harness/untrusted-input.md) beschrieben.
Wer KI-Verarbeitung dieser Daten baut, liest zuerst dort.

## Bewusste Grenzen (nicht „wegoptimieren")

- **Honeypot muss Fake-Erfolg liefern**, keinen Fehler.
- **In-Memory-Rate-Limit ist kein hartes Limit** – instanzgebunden. Der ehrliche
  Kommentar in `rateLimit.ts` bleibt stehen. Upgrade-Pfad: Store tauschen
  (Cloudflare KV/Durable Objects, Upstash Redis), Signatur
  `rateLimit(key, limit, windowMs): boolean` beibehalten.
- **Extensionless Routing ist Obfuskation, keine Sicherheitsgrenze.** Nicht als
  Schutz verkaufen; der echte Schutz sind die Schichten oben.
- **`ServerTokens Prod` gehört NICHT in `.htaccess`** (nur server-seitig gültig,
  löst sonst einen 500er aus). Dort steht bewusst nur `ServerSignature Off`.
- **`TraceEnable Off`** ist per `.htaccess` nicht sauber erzwingbar – als
  Hoster-Frage dokumentieren, nicht vortäuschen.
- **HSTS / CSP** bleiben auskommentiert mit Warnung: HSTS-preload ist schwer
  rückholbar, CSP ist seitenspezifisch und vor dem Scharfschalten zu testen.
- **Injection-Heuristik ist ein Signal, kein Gate.** Nicht hart blockieren, nicht
  als „injection-sicher" verkaufen – sie hat False Positives und Negatives.
- **Rate-Limit ist fail-open:** ist der Store nicht schreibbar, wird nicht
  blockiert (Honeypot + Token schützen weiter), aber es wird geloggt. Bewusste
  Verfügbarkeits-Entscheidung – bei anderem Schutzbedarf umdrehen und begründen.

## Bekannter Unterschied zwischen den Varianten

Die PHP-Variante prüft `Origin` mit **Referer-Fallback** (manche Browser senden
bei Same-Origin-POST kein `Origin`), die Serverless-Variante prüft `Origin`
strikt. Für den `fetch()`-Client hier ist das unkritisch – `fetch` sendet bei
POST immer einen `Origin` –, und die strikte Prüfung ist die schärfere. Wer das
Formular auf klassisches Form-Encoding umbaut, braucht den Fallback auch dort.

## Test-Checkliste

- [ ] Statischer Build enthält keine Secrets (Bundle greppen).
- [ ] POST von fremder Origin → 403.
- [ ] Sofort-Absenden (< 3 s) → Token-Fehler 403.
- [ ] Ausgefülltes Honeypot-Feld → 200, aber keine Mail.
- [ ] `\r\n` in Name/E-Mail landet nicht in Mail-Headern.
- [ ] > 5 Requests/Minute/IP → 429 (PHP-Variante zuverlässig).
- [ ] `/api/contact.php` direkt → 404 (bei aktivem Rewrite).
- [ ] Direktaufruf `.env`, `.git/`, `api/lib/*` → 404.
- [ ] Mail kommt als reiner Text, `Reply-To` = Besucheradresse.
- [ ] Fehlerseiten zeigen keine Apache-/PHP-Hinweise.
- [ ] „ignore all previous instructions …" → `X-Injection-Risk: high`.
- [ ] Zero-Width-/Unicode-TAG-Zeichen werden aus der Mail entfernt.
- [ ] Legitime Nachricht mit URL → nicht blockiert (`low`/`medium`).

## Datenschutz (kein Rechtsrat)

Kein Captcha, kein Consent-Banner nötig, kein US-Transfer (PHP-Variante). Die IP
wird nur flüchtig fürs Rate-Limit verarbeitet. Bei der Serverless-Variante ist
der Mailprovider (Resend, US) als Auftragsverarbeiter samt Drittlandtransfer in
der Datenschutzerklärung zu nennen. Formulierung im Zweifel juristisch prüfen
lassen.
