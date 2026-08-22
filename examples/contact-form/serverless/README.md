# Variante A: Serverless (DSGVO-arm, ohne Drittanbieter-Captcha)

Statische Astro-Seite + eine serverseitige API-Route. Keine Datenbank,
kein externes Captcha, kein Consent-Banner nötig – nur Honeypot,
signiertes Zeit-Token und Rate-Limiting.

> Überblick, Variantenwahl und die bewussten Grenzen stehen in der
> [Baustein-README](../README.md). Diese Datei beschreibt nur die
> Serverless-Ausprägung.

## Dateien

```
src/lib/formToken.ts        HMAC-signiertes Zeit-Token (Web Crypto)
src/lib/rateLimit.ts        In-Memory Rate-Limit (+ Upgrade-Hinweis)
src/lib/untrusted.ts        Prompt-Injection-Härtung (Normalisierung + Flags)
src/lib/env.ts              Pflicht-Env-Var-Guard (siehe "Umgebungsvariablen" unten)
src/pages/api/form-token.ts GET: liefert frisches Token beim Laden
src/pages/api/contact.ts    POST: nimmt Formular an, verschickt Mail
.env.example                Vorlage für Umgebungsvariablen
```

Dazu das gemeinsame Frontend aus [`../frontend/Contact.astro`](../frontend/Contact.astro)
→ im Zielprojekt nach `src/components/Contact.astro`. Die Endpunkt-URLs darin
(`/api/form-token`, `/api/contact`) passen zu dieser Variante unverändert.

## Integration

1. Dateien ins Repo kopieren (Struktur passt zu einem Standard-Astro-Projekt).
2. Adapter installieren, damit API-Routes laufen. Die Seite bleibt statisch,
   nur `/api/*` läuft serverseitig (`export const prerender = false`):

   ```bash
   # Beispiel Cloudflare:
   npx astro add cloudflare
   # oder: npx astro add vercel  /  npx astro add netlify  /  npx astro add node
   ```

   In `astro.config.mjs` genügt `output: "static"` – einzelne Routen mit
   `prerender = false` werden trotzdem serverseitig ausgeführt (Astro >= 5).
3. `pnpm add dotenv` (siehe „Umgebungsvariablen" unten – ohne dotenv bleiben
   die Secrets unter dem Node-Adapter zur Laufzeit `undefined`).
4. `.env.example` nach `.env` kopieren und Werte setzen. `FORM_SECRET`
   z.B. mit `openssl rand -hex 32`. `.env` in `.gitignore` aufnehmen.
5. In Produktion die Variablen im Hosting-Dashboard hinterlegen.
6. Komponente einbinden: `import Contact from "../components/Contact.astro"`.

## Umgebungsvariablen: process.env, nicht import.meta.env

**Lektion aus einem echten Einsatz dieses Bausteins** (Astro-Projekt,
2026-08): `import.meta.env.*` wird von Vite nur **zur Build-Zeit** ersetzt.
Unter Adaptern, die die Route **zur Laufzeit** ausführen (z. B.
`@astrojs/node`), liefert `import.meta.env.FORM_SECRET` & Co. zur Laufzeit
`undefined` — das Formular lehnt dann jede Anfrage mit 403 ab (Origin-Check
gegen `undefined` schlägt fehl), und falls doch eine `.env` beim Build
vorhanden war, werden die Secrets als Literal in den Server-Bundle
eingebacken.

Dieser Baustein liest die fünf Secrets deshalb über **`process.env.*`**
(siehe `contact.ts`/`form-token.ts`). Zwei Dinge sind dafür nötig:

- **`process.env` wird nicht automatisch aus `.env` befüllt** – weder von
  Node noch vom Adapter. `src/lib/env.ts` importiert deshalb `dotenv/config`
  als Seiteneffekt; das lädt eine vorhandene `.env` in `process.env`, ohne
  bereits gesetzte Werte zu überschreiben (z. B. echte Secrets im
  Hosting-Dashboard).
- **Adapter-Unterschiede beachten:** `process.env` funktioniert zuverlässig
  unter Node-basierten Adaptern (`node`, i. d. R. auch Netlify/Vercel-Functions).
  **Cloudflare Workers hat kein `process.env`** – dort kommen Bindings über
  `context.locals.runtime.env`. Vor dem Einsatz mit `@astrojs/cloudflare`
  die aktuelle Astro-/Cloudflare-Doku prüfen, nicht blind dieses Muster
  übernehmen.
- **`SITE_ORIGIN`/`FORM_SECRET` fehlen = Konfigurationsfehler, kein
  legitimes „Forbidden"** – `env.ts`s `requireEnv()` wirft laut statt still
  wie ein falscher (aber vorhandener) Wert zu wirken. Ein `undefined !==
  undefined`-Vergleich wäre sonst `false` und würde einen Origin-Check
  versehentlich bestehen lassen.

## Mail-Versand

Beispiel nutzt [Resend](https://resend.com). Zum Wechseln nur den `fetch`-Block
in `contact.ts` austauschen (Postmark, Mailgun, SMTP via Nodemailer bei
Node-Adapter …). Absenderdomain vorher verifizieren (SPF/DKIM), sonst landet
die Mail im Spam.

## Sicherheits-Checkliste

- [x] Origin-Check – kein offener Relay
- [x] Honeypot (`company`) – meldet Fake-Erfolg statt Fehler
- [x] Signiertes Zeit-Token – min. 3 s, HMAC gegen Fälschung, Konstantzeit-Vergleich
- [x] Rate-Limit pro IP – grobe erste Barriere
- [x] Längen- und Formatvalidierung
- [x] Header-Injection-Schutz (`\r\n` gestrippt)
- [x] Mail als `text`, nicht `html`
- [x] Secrets nur serverseitig aus env – `process.env` (nicht `import.meta.env`,
      siehe „Umgebungsvariablen" oben), via `dotenv/config` geladen
- [x] Fehlende Pflicht-Secrets lösen einen lauten 500er aus (`env.ts`), nie
      ein stilles, falsch-legitimes „Forbidden"
- [x] Untrusted-Freitext entschärft + Risiko-Metadaten (`untrusted.ts`) –
      die verbindlichen Konsum-Regeln stehen in
      [`harness/untrusted-input.md`](../../../harness/untrusted-input.md)

## Nächste Härtungsschritte

- **Rate-Limit produktionsfest** machen: Cloudflare KV/Durable Objects oder
  Upstash Redis statt In-Memory (siehe Kommentar in `rateLimit.ts`).
- **Security-Header** setzen (CSP, `X-Content-Type-Options`, `Referrer-Policy`)
  – bei Cloudflare/Netlify per `_headers`-Datei, bei Vercel per `vercel.json`.
  **Achtung CSP + Astro:** Astro inlined kleine Stylesheets (`<style>`) und
  hoisted `<script>`-Tags unter ~4 KB standardmäßig direkt in die Seite – das
  bricht ein striktes `style-src`/`script-src 'self'` ohne Hashes/Nonces.
  Gegenmittel: `inlineStylesheets: "never"` in `astro.config.mjs`s `build`
  UND `assetsInlineLimit: 0` unter `vite.build` (nicht Astros eigenem
  `build`-Key – dort wird der Wert von Astros Config-Schema stillschweigend
  ignoriert). Details/Lektion: [`../../../harness/security.md`](../../../harness/security.md#astro).
- **Altcha** als optionales Modul, falls das Spam-Aufkommen steigt
  (selbst-hostbar, kein Drittanbieter-Transfer).

## Datenschutz-Hinweis

Ohne Turnstile/reCAPTCHA fließen bei diesem Setup keine Formulardaten an
Dritte – außer an deinen gewählten Mail-Dienstleister (hier Resend, US-Anbieter:
in der Datenschutzerklärung als Auftragsverarbeiter/Drittlandtransfer nennen).
Der Endpunkt verarbeitet die IP nur flüchtig fürs Rate-Limit. Kein Rechtsrat –
Formulierung der Datenschutzerklärung im Zweifel prüfen lassen.
