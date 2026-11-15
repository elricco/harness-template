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
- **Tracer Bullet zuerst:** Das erste Arbeitspaket eines Vorhabens ist ein
  dünner Pfad durch alle Komponenten, nachgewiesen mit dem Produktions-Build
  (kein Deployment nötig), danach folgen vertikale Scheiben. Details: [`spec-driven.md`](spec-driven.md).
- Lieber kleine, fokussierte Sessions als stundenlange Mammut-Sessions.
- Kein "fertig" ohne bestandene Verifikation **und** grüne Tests.

## Git

- Ein logischer Schritt = ein Commit (aussagekräftige Nachricht).
- Kein Push, bevor Tests grün sind und die statische Analyse sauber ist.

### Branch-Modell

Drei feste Branches, jeder steht für eine Umgebung:

| Branch    | Zweck                                   | Agent darf …                              |
| --------- | --------------------------------------- | ----------------------------------------- |
| `develop` | Entwicklung (lokal)                     | committen – das ist der Standard-Branch   |
| `staging` | Präsentation/Abnahme auf dem Dev-Server | mergen, **nur** wenn der Mensch „bereit fürs Staging“ sagt |
| `main`    | Production                              | **nichts** – Merge und Tag macht der Mensch |

**Einrichtung (einmalig pro Repo):**

```bash
git init -b main
echo "# Projektname" > README.md
git add README.md && git commit -m "Initial commit"
git switch -c staging
git switch -c develop
```

**Regeln:**

- **Vor Arbeitsbeginn Branch prüfen** (`git branch --show-current`). Steht das
  Repo auf `main` oder `staging`: nach `develop` wechseln oder nachfragen –
  nie dort committen.
- **Feature-Branches/Worktrees** (`feature/<name>`) nur für umfangreichere
  Arbeiten, nie für kleine Fixes. Abzweigen von `develop`, zurück nach
  `develop`. Werkzeuge/Skills, die „nach `main` mergen“ vorschlagen, gelten
  hier nicht.
- **Merges nach `staging` und `main` mit `--no-ff`** – jede Abnahme und jedes
  Release bleibt als Merge-Commit in der History sichtbar.
- **Abnahme-Fixes** entstehen auf `develop` und gehen erneut nach `staging`.
  Auf `staging` selbst wird nicht gearbeitet.
- **Hotfix:** `hotfix/<name>` von `main`, danach zurück nach `main`, `staging`
  **und** `develop`, damit der Fix nicht beim nächsten Release verschwindet.
- **Releases auf `main` werden getaggt** (`vX.Y.Z`).

### Releases mit Container-Image: einmal bauen, dann befördern

Wenn das Projekt ein Image baut (z. B. GHCR), geht genau das Image live, das
auf Staging abgenommen wurde – kein zweiter Build für Production. Ein
Neubau kann andere Basis-Images/Pakete ziehen und ist dann nicht mehr das
Getestete.

```
develop ─merge─▶ staging ─tag vX.Y.Z-rc.N─▶ CI baut + testet + pusht :X.Y.Z-rc.N ─▶ Dev-Server
                    │   (Abnahme-Fix auf develop → erneut mergen → rc.N+1)
                    ▼
                  main ─tag vX.Y.Z─▶ CI taggt das letzte rc-Image um auf :X.Y.Z ─▶ Production
```

- **rc-Tag** (`v1.4.0-rc.1`) auf `staging`: CI baut, führt Smoke-Tests aus,
  pusht das Image unter `:1.4.0-rc.1`. Den Tag darf der Agent zusammen mit dem
  Staging-Merge setzen; gepusht wird mit `git push origin staging --follow-tags`.
- **Finaler Tag** (`v1.4.0`) auf `main`: CI baut **nicht**, sondern sucht das
  höchste `1.4.0-rc.*`-Image und hängt den Versions-Tag an dasselbe Manifest
  (`docker buildx imagetools create -t <image>:1.4.0 <image>:1.4.0-rc.3`).
  Gibt es kein rc-Image, bricht der Job ab.
- Ein Versions-Check (Tag passt zur Versionsangabe im Code) muss beide Formen
  akzeptieren; beim rc-Tag zählt die Basis `X.Y.Z`.
- Server verweisen immer auf einen festen Tag, nie `latest`: Dev-Server auf
  das rc, Production auf die finale Version.
- Wer nur eine statische Seite deployt (kein Image), lässt diesen Teil weg:
  Push auf `staging` deployt den Dev-Server, Push/Tag auf `main` Production.

## CI/CD-Actions & Node-Version aktuell halten

Gepinnte GitHub-Action-Versionen und Node-LTS-Stände sind Momentaufnahmen –
sie veralten, ohne dass der Code sich ändert, und GitHub deprecatet
Runner-Node-Versionen mit Vorlauf, aber verbindlich (Beispiel:
[Node 20 Deprecation-Ankündigung](https://github.blog/changelog/2025-09-19-deprecation-of-node-20-on-github-actions-runners/)).
Vor dem ersten produktiven CI-Setup (und danach in größeren Abständen) gegen
die tatsächlichen Marketplace-/Release-Seiten prüfen, nicht aus dem
Gedächtnis oder von einem älteren Projekt kopieren – Stand beim Schreiben
dieser Zeile (2026-08, aus einem Astro-Praxistest):
`actions/checkout@v7`, `actions/setup-node@v7`, `actions/upload-artifact@v7`,
`pnpm/action-setup@v6` (eigene, niedrigere Versionszählung, anderer
Maintainer), Node **24** als aktuelle Active-LTS (22 nur noch Maintenance).
Diese Zahlen selbst werden veralten – die Prüfpflicht bleibt.
