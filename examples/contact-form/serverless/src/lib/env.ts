// env.ts
// Kleiner Guard für Pflicht-Umgebungsvariablen server-seitiger Routen.
//
// Lektion aus einem echten Einsatz dieses Bausteins (Astro-Projekt,
// 2026-08): process.env wird NICHT automatisch aus einer .env befüllt —
// weder von Node noch vom @astrojs/node-Adapter. `dotenv/config` schließt
// diese Lücke, ohne bereits gesetzte process.env-Werte zu überschreiben
// (z. B. echte Secrets im Hosting-Dashboard) — nur fehlende werden aus der
// lokalen .env ergänzt. Gilt in `astro dev` genauso wie im gebauten Server.
import "dotenv/config";

// Ein fehlender Secret-Wert ist ein Konfigurationsfehler, keine legitime
// Sicherheitsentscheidung — er darf deshalb NIE still wie ein normaler
// (falscher) Wert behandelt werden (z. B. `undefined !== undefined` wäre
// `false` und würde einen Origin-Check versehentlich bestehen lassen).
export function requireEnv(name: string, value: string | undefined): string {
  if (!value) {
    throw new Error(`${name} ist nicht gesetzt — .env prüfen (siehe .env.example).`);
  }
  return value;
}
