# Spec-Driven Development

> Wird aus `AGENTS.md` per Lazy Loading geladen beim Umgang mit Anforderungen.

## Ablage

Specs liegen in `specs/` (Unterordner erlaubt). Eine Spec = eine Story.

Bei größeren Vorhaben hat sich eine **zweistufige Hierarchie** bewährt
(Vanilla-JS-Pilot): ein übergeordnetes **Gesamtdesign-Dokument**
(Entscheidungen, Architektur, Arbeitspakete) + daraus abgeleitete
**Story-Specs** pro Arbeitspaket. Beide nutzen dasselbe Frontmatter.

**Visuelle Vorgaben** (Farben, Typo, Layout) gehören nicht in Story-Specs,
sondern in die `DESIGN.md` im Repo-Root – auch Design ist eine Spec, mit
eigenem Format und eigener Prüfung (siehe [`design.md`](design.md)).

## Arbeitspakete zuschneiden: Tracer Bullet zuerst

Nach _The Pragmatic Programmer_ (Hunt/Thomas): Leuchtspurmunition zeigt sofort,
wo sie einschlägt – man korrigiert am echten Treffer statt an der Berechnung.

**Arbeitspaket 1 ist immer der Tracer Bullet:** ein dünner, echter Pfad durch
**alle** Komponenten der Architektur – Eingabe/UI → Logik → Speicherung →
Ausgabe. Fachlich kann er fast nichts (ein Feld, eine Route, ein Datensatz),
technisch ist er vollständig. Sein Zweck ist der Machbarkeitsbeweis: Trägt das
Gesamtkonzept?

- **Produktionsqualität, kein Wegwerfcode.** TDD, Verifikation, Spec – wie
  jedes andere Paket. Er wird danach ausgebaut, nicht ersetzt. (Wer eine
  offene Frage nur klären will und den Code wegwirft, baut einen _Prototyp_ –
  das ist erlaubt, muss aber so benannt und nie gemergt werden.)
- **Fertig heißt: läuft mit dem Produktions-Build.** Nicht nur im
  Dev-Server, sondern unter Produktionsbedingungen – lokal reicht: Prod-Image
  bauen + Smoke-Test, bzw. `build` + `preview` statt Dev-Modus. Manche
  Architekturfragen zeigen sich nur dort (Laufzeit-Env-Variablen, CSP,
  Prod-Image mit anderer PHP-Version/anderen Rechten); ohne diesen Schritt
  beweist der Tracer Bullet nur „läuft bei mir“.
- **Kein Deployment nötig.** Staging ist Abnahme, nicht Machbarkeit – die
  erste Scheibe auf Staging ist ein eigener, früher Meilenstein danach.
  **Ausnahme:** Ist die Zielumgebung selbst das Risiko (neues Hosting,
  Serverless/Edge-Runtime, spezielle Server-Infrastruktur), gehört das
  Deployment in den Tracer Bullet. Das wird im Gesamtdesign entschieden, nicht
  pauschal.
- **Alle weiteren Pakete sind vertikale Scheiben:** jede liefert ein
  Stück Funktion, das Ende-zu-Ende nutzbar und vorzeigbar ist.
  Nicht nach Schichten schneiden („erst das ganze Backend, dann das UI“).
- **Daneben getroffen = Plan anpassen.** Zeigt der Tracer Bullet, dass eine
  Architekturentscheidung nicht trägt, wird das Gesamtdesign korrigiert,
  bevor weitere Pakete entstehen.

Beispiel (mehrstufiges Formular mit Verwaltungsbereich): Nicht „AP 1: alle
Schritte des Formulars, AP 2: Verwaltungsbereich, AP 3: Deployment“, sondern
„AP 1: ein Eingabefeld wird gespeichert und erscheint im Verwaltungsbereich –
nachgewiesen im lokal gebauten Prod-Image“, danach je Schritt eine Scheibe.

## Verträge für den nächsten Plan

Bewährt in einem REDAXO-Projekt mit 15 Plänen (2026-10/11): Das Review am Ende eines Plans
hält nicht nur Befunde fest, sondern auch **Verträge** – was der nächste Plan
einlösen muss, weil es sich erst dort lösen lässt. Beispiele: „Zustandswechsel
als Compare-and-Set“, „Download nur über geschützten Endpunkt mit Audit“,
„Exporte entschärfen Formelzeichen“.

- Ablage in den projektweiten Setup-/Betriebsnotizen (z. B.
  `docs/setup-notes.md`) unter „Verträge für Plan N“.
- Der nächste Plan startet damit: jeder Vertrag wird ein Akzeptanzkriterium
  oder ausdrücklich verschoben.
- So geht zwischen zwei Plänen nichts verloren, was ein Review gefunden,
  aber nicht sofort behoben hat.

## Spec-Format

```markdown
---
status: new        # new | edited | implemented
id: <eindeutig>
---

# <Titel>

## User Story
Als <Rolle> möchte ich <Ziel>, um <Nutzen>.

## Beschreibung
<Kontext>

## Akzeptanzkriterien
- [ ] ...
- [ ] ...
```

## Status-Workflow

- Neue Spec → `status: new`.
- Spec geändert → der Agent setzt `status: edited`.
- Nach Implementierung + bestandener Verifikation → `status: implemented`.
- Sammelbefehl: "Implementiere alle Specs mit `status: edited`".

## Synchronität

> "Die Wahrheit liegt in der Spezifikation." Nach jeder Code-Änderung Spec und
> Code synchron halten.
