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
3. `.env.example` nach `.env` kopieren und Werte setzen. `FORM_SECRET`
   z.B. mit `openssl rand -hex 32`. `.env` in `.gitignore` aufnehmen.
4. In Produktion die Variablen im Hosting-Dashboard hinterlegen.
5. Komponente einbinden: `import Contact from "../components/Contact.astro"`.

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
- [x] Secrets nur serverseitig aus env
- [x] Untrusted-Freitext entschärft + Risiko-Metadaten (`untrusted.ts`) –
      die verbindlichen Konsum-Regeln stehen in
      [`harness/untrusted-input.md`](../../../harness/untrusted-input.md)

## Nächste Härtungsschritte

- **Rate-Limit produktionsfest** machen: Cloudflare KV/Durable Objects oder
  Upstash Redis statt In-Memory (siehe Kommentar in `rateLimit.ts`).
- **Security-Header** setzen (CSP, `X-Content-Type-Options`, `Referrer-Policy`)
  – bei Cloudflare/Netlify per `_headers`-Datei, bei Vercel per `vercel.json`.
- **Altcha** als optionales Modul, falls das Spam-Aufkommen steigt
  (selbst-hostbar, kein Drittanbieter-Transfer).

## Datenschutz-Hinweis

Ohne Turnstile/reCAPTCHA fließen bei diesem Setup keine Formulardaten an
Dritte – außer an deinen gewählten Mail-Dienstleister (hier Resend, US-Anbieter:
in der Datenschutzerklärung als Auftragsverarbeiter/Drittlandtransfer nennen).
Der Endpunkt verarbeitet die IP nur flüchtig fürs Rate-Limit. Kein Rechtsrat –
Formulierung der Datenschutzerklärung im Zweifel prüfen lassen.
