// untrusted.ts
// Härtung von untrusted Freitext, der SPÄTER von einer KI verarbeitet werden
// könnte. LÖST Prompt Injection NICHT – entfernt Schmuggel-Vektoren und flaggt
// Muster als Signal. Die echte Verteidigung ist die Konsum-Seite (siehe Doku).

export type Analysis = { clean: string; flags: string[]; risk: "low" | "medium" | "high" };

// Unsichtbare / gefährliche Codepoints (Zero-Width, Bidi, Unicode-Tags) +
// Steuerzeichen außer \t \n \r.
const INVISIBLES =
  /[\u200B-\u200D\u2060\uFEFF\u202A-\u202E\u2066-\u2069\u{E0000}-\u{E007F}]/gu;
const CONTROLS = /[\u0000-\u0008\u000B\u000C\u000E-\u001F\u007F]/g;

const PATTERNS: Record<string, RegExp> = {
  "instruction-override": /\b(ignore|disregard|forget)\b.{0,30}\b(previous|prior|above|earlier|all)\b.{0,30}\b(instruction|prompt|rule|message)/i,
  "system-prompt": /\b(system|developer)\s*(prompt|message|instruction)/i,
  "role-hijack": /\b(you are now|act as|from now on|pretend to be|roleplay as|you must)\b/i,
  "role-marker": /(^|\n)\s*(system|assistant|user)\s*:/i,
  "special-tokens": /<\|.*?\|>|\[\/?INST\]|<<SYS>>/i,
  "jailbreak": /\b(jailbreak|developer mode|dan mode|do anything now)\b/i,
  "exfil-action": /\b(forward|send|email|export|delete|drop|reveal|print)\b.{0,30}\b(all|every|database|contacts?|messages?|data|table|prompt|key|secret)/i,
  "code-fence": /```|~~~/,
  "url": /https?:\/\//i,
};

const STRONG = new Set([
  "instruction-override", "system-prompt", "role-hijack",
  "role-marker", "special-tokens", "jailbreak", "exfil-action",
]);

export function analyzeUntrusted(text: string): Analysis {
  const clean = text.normalize("NFKC").replace(INVISIBLES, "").replace(CONTROLS, "");
  const flags = Object.entries(PATTERNS)
    .filter(([, re]) => re.test(clean))
    .map(([name]) => name);

  const score = flags.filter((f) => STRONG.has(f)).length;
  const risk = score >= 2 ? "high" : score >= 1 ? "medium" : "low";
  return { clean, flags, risk };
}
