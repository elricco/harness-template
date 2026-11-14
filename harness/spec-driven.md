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
**alle** Schichten des Vorhabens – Eingabe/UI → Logik → Speicherung → Ausgabe
→ Build/Deploy bis auf `staging`. Fachlich kann er fast nichts (ein Feld, eine
Route, ein Datensatz), technisch ist er vollständig.

- **Produktionsqualität, kein Wegwerfcode.** TDD, Verifikation, Spec – wie
  jedes andere Paket. Er wird danach ausgebaut, nicht ersetzt. (Wer eine
  offene Frage nur klären will und den Code wegwirft, baut einen _Prototyp_ –
  das ist erlaubt, muss aber so benannt und nie gemergt werden.)
- **Fertig heißt: läuft auf Staging.** Erst wenn der Pfad im echten Zielsystem
  funktioniert (Container, Server, CSP, Rechte, Secrets), ist der Tracer Bullet
  abgeschlossen. Genau dort liegen die Überraschungen, die sonst erst in der
  letzten Woche auftauchen.
- **Alle weiteren Pakete sind vertikale Scheiben:** jede liefert ein
  Stück Funktion, das Ende-zu-Ende nutzbar und auf Staging zeigbar ist.
  Nicht nach Schichten schneiden („erst das ganze Backend, dann das UI“).
- **Daneben getroffen = Plan anpassen.** Zeigt der Tracer Bullet, dass eine
  Architekturentscheidung nicht trägt, wird das Gesamtdesign korrigiert,
  bevor weitere Pakete entstehen.

Beispiel (mehrstufiges Formular mit Verwaltungsbereich): Nicht „AP 1: alle
Abschnitte der Strecke, AP 2: Portal, AP 3: Deployment“, sondern „AP 1: ein
Eingabefeld wird gespeichert, erscheint im Portal und läuft als Image auf
Staging“, danach je Abschnitt eine Scheibe.

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
