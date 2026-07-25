// rateLimit.ts
// Einfaches Fixed-Window-Limit im Speicher.
//
// WICHTIG zur Reichweite:
//   - Funktioniert pro *warmer* Instanz. Bei Serverless mit vielen
//     parallelen Instanzen oder häufigen Cold-Starts ist es nur eine
//     grobe erste Barriere, kein hartes Limit.
//   - Für ein verlässliches, instanzübergreifendes Limit:
//       Cloudflare -> KV oder Durable Objects
//       Vercel/Netlify -> Upstash Redis (@upstash/ratelimit)
//     Dann diese Datei durch den jeweiligen Store-Aufruf ersetzen;
//     die Signatur `rateLimit(key, limit, windowMs): boolean` kann bleiben.

type Bucket = { count: number; reset: number };
const buckets = new Map<string, Bucket>();

/** true = erlaubt, false = blockiert. */
export function rateLimit(key: string, limit = 5, windowMs = 60_000): boolean {
  const now = Date.now();

  // Gelegentlich abgelaufene Einträge aufräumen (verhindert Speicherleck).
  if (buckets.size > 5_000) {
    for (const [k, b] of buckets) if (now > b.reset) buckets.delete(k);
  }

  const b = buckets.get(key);
  if (!b || now > b.reset) {
    buckets.set(key, { count: 1, reset: now + windowMs });
    return true;
  }
  if (b.count >= limit) return false;
  b.count++;
  return true;
}
