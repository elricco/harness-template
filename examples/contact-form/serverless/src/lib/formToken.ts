// formToken.ts
// Signiertes Zeit-Token gegen "Sofort-Post"-Bots und Replay.
// Nutzt Web Crypto (HMAC-SHA256) -> läuft in Node- UND Edge-Runtimes
// (Cloudflare, Vercel Edge, Netlify). Keine externe Abhängigkeit.

const encoder = new TextEncoder();

async function getKey(secret: string): Promise<CryptoKey> {
  return crypto.subtle.importKey(
    "raw",
    encoder.encode(secret),
    { name: "HMAC", hash: "SHA-256" },
    false,
    ["sign"],
  );
}

function toHex(buf: ArrayBuffer): string {
  return [...new Uint8Array(buf)].map((b) => b.toString(16).padStart(2, "0")).join("");
}

/** Erstellt ein Token `<timestamp>.<hmac>`. Beim Ausliefern des Formulars aufrufen. */
export async function issueToken(secret: string): Promise<string> {
  const ts = Date.now().toString();
  const key = await getKey(secret);
  const sig = await crypto.subtle.sign("HMAC", key, encoder.encode(ts));
  return `${ts}.${toHex(sig)}`;
}

type VerifyOpts = { minMs?: number; maxMs?: number };

/**
 * Prüft Signatur UND Alter.
 * - minMs: frühestens so alt (Bots posten in <1s) -> Default 3s
 * - maxMs: spätestens so alt (Token nicht ewig gültig) -> Default 1h
 */
export async function verifyToken(
  token: string,
  secret: string,
  { minMs = 3_000, maxMs = 3_600_000 }: VerifyOpts = {},
): Promise<boolean> {
  const [ts, sig] = (token ?? "").split(".");
  if (!ts || !sig || !/^\d+$/.test(ts)) return false;

  const key = await getKey(secret);
  const expected = toHex(await crypto.subtle.sign("HMAC", key, encoder.encode(ts)));

  // Konstantzeit-Vergleich, damit die Signaturprüfung nicht über
  // Antwortzeiten angreifbar wird.
  if (sig.length !== expected.length) return false;
  let diff = 0;
  for (let i = 0; i < sig.length; i++) diff |= sig.charCodeAt(i) ^ expected.charCodeAt(i);
  if (diff !== 0) return false;

  const age = Date.now() - Number(ts);
  return age >= minMs && age <= maxMs;
}
