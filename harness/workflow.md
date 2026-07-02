# Workflow

> Der verbindliche Entwicklungsablauf. Wird aus `AGENTS.md` referenziert.

## Ablauf (Test-Driven)

1. **Spec** – Anforderung als Spec festhalten/lesen (siehe [`spec-driven.md`](spec-driven.md)).
   Keine Implementierung ohne Akzeptanzkriterien.
2. **Test zuerst** – Aus den Akzeptanzkriterien einen **fehlschlagenden** Test
   ableiten (Red). Siehe [`testing.md`](testing.md).
3. **Code** – Das Minimum schreiben, damit der Test grün wird (Green). Vorher
   [`coding-style.md`](coding-style.md) und bei Strukturentscheidungen
   [`architecture.md`](architecture.md) lesen.
4. **Refactor** – Aufräumen, ohne Verhalten zu ändern; Tests bleiben grün.
5. **Verifikation** – Statische Analysen aus [`verification-tools.md`](verification-tools.md)
   ausführen, Befunde beheben.
6. **Spec-Sync** – Spec-Status und -Inhalt synchron zum Code halten.

## Regeln

- **Red → Green → Refactor** ist die kleinste Einheit. Kein Produktivcode ohne
  vorher fehlschlagenden Test.
- **YAGNI:** Nur bauen, was die aktuelle Spec verlangt. Der beste Code ist der,
  der nicht geschrieben werden musste.
- **KISS:** Die einfachste Lösung, die die Tests grün macht, gewinnt.
- Lieber kleine, fokussierte Sessions als stundenlange Mammut-Sessions.
- Kein "fertig" ohne bestandene Verifikation **und** grüne Tests.

## Git

- Ein logischer Schritt = ein Commit (aussagekräftige Nachricht).
- Kein Push, bevor Tests grün sind und die statische Analyse sauber ist.

<!-- TODO: Projektspezifische Branch-/PR-Konventionen ergänzen. -->
