# Design-Richtlinie (DESIGN.md)

> Wird aus `AGENTS.md` per Lazy Loading geladen bei UI-Gestaltung (Layout,
> Farben, Typografie, Komponenten-Look).
>
> Grundsatz: **Design-Vorgaben sind Spezifikationen.** Ob sie von uns kommen,
> vom Kunden geliefert oder von einem Design-Tool generiert wurden – sie werden
> als `DESIGN.md` im Repo-Root festgehalten und gelten wie jede andere Spec.

## Das Format

`DESIGN.md` ist ein offener Standard von Google Labs (aus dem Design-Tool
**Stitch** heraus entwickelt, am 21.04.2026 open-sourced, Apache 2.0,
Status: **alpha**). Spec: https://github.com/google-labs-code/design.md

Aufbau – zwei Schichten in einer Datei:

1. **YAML-Frontmatter** = maschinenlesbare **Design-Tokens** mit exakten
   Werten: `colors` (CSS-valide, auch `oklch()`), `typography` (fontFamily,
   fontSize, fontWeight, lineHeight, …), `spacing`, `rounded`, `components`
   (benannte Gruppen, die Tokens auf Komponenten mappen).
   Querverweise per `{path.to.token}`. Kompatibel zum W3C Design Token
   Format (DTCG).
2. **Markdown-Prosa** = das **Warum** und die Anwendung. Kanonische
   Sektionsreihenfolge: Overview, Colors, Typography, Layout,
   Elevation & Depth, Shapes, Components, Do's and Don'ts.

Herkunft ist egal: von Hand geschrieben, aus Stitch generiert, vom Kunden
geliefert oder Community-Vorlage – sobald sie im Repo liegt, ist sie die
Wahrheit für alles Visuelle.

## Regeln für den Agenten

- **Tokens sind verbindlich.** Farben, Abstände, Radien, Schriften kommen aus
  der `DESIGN.md` – niemals frei erfinden oder "ungefähr passende" Werte
  hartcodieren. Die Prosa entscheidet, *welches* Token wofür gedacht ist.
- **Keine UI ohne Design-Spec.** Fehlt eine `DESIGN.md`, nicht drauflos
  gestalten (Lektion aus dem Vanilla-JS-Pilot: ohne Design-Vorgabe wird
  die Übersichtsseite beliebig). Stattdessen: minimale `DESIGN.md` anlegen
  (aus vorhandenen Vorgaben ableiten oder beim Nutzer erfragen) und bestätigen
  lassen – dann erst bauen.
- **Spec-Sync gilt auch hier:** Design-Änderung → zuerst `DESIGN.md`
  aktualisieren, dann Code (siehe [`spec-driven.md`](spec-driven.md)).
- **Nicht duplizieren, exportieren:** `tokens.json` (DTCG) oder
  Tailwind-Config per CLI aus der `DESIGN.md` generieren – Tokens nie von Hand
  in CSS/Config nachbauen (ein Ort der Wahrheit).
- **WCAG schlägt DESIGN.md:** Verstößt ein geliefertes Design gegen die
  Kontrastgrenzen aus [`accessibility.md`](accessibility.md), den Konflikt
  melden statt blind umsetzen.

## Deterministische Prüfung

Das CLI (`@google/design.md`, npm) macht Design-Vorgaben prüfbar – passt
exakt in unsere Verifikations-Schicht:

```bash
npx @google/design.md lint DESIGN.md    # Struktur, kaputte {token}-Referenzen,
                                        # verwaiste Tokens, KONTRAST-Checks
npx @google/design.md diff a.md b.md    # Token-Änderungen zwischen Versionen
npx @google/design.md export --tailwind # Tailwind-Config / DTCG-tokens.json
```

- `lint` läuft nach jeder Änderung an `DESIGN.md` (siehe
  [`verification-tools.md`](verification-tools.md)).
- **Alpha-Status beachten:** CLI-Version exakt pinnen (Lektion
  "lokal grün, CI rot"); Breaking Changes der Spec einkalkulieren.
