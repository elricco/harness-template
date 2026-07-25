<?php
declare(strict_types=1);

// Härtung von untrusted Freitext, der SPÄTER von einer KI verarbeitet werden
// könnte (Inbox-Triage, Zusammenfassung, Auto-Reply, Agenten).
//
// !!! WICHTIG: Das hier LÖST Prompt Injection NICHT. !!!
// Es tut zwei Dinge:
//   1. entfernt bekannte Schmuggel-Vektoren (unsichtbare/Steuer-Zeichen),
//   2. FLAGGT verdächtige Muster als Signal.
// Die eigentliche Verteidigung passiert dort, wo der Text ans LLM geht.
// Siehe PROMPT-INJECTION.md.

/**
 * Normalisiert und entschärft untrusted Freitext und bewertet das Risiko.
 * @return array{clean:string, flags:string[], risk:string}
 */
function analyze_untrusted(string $text): array
{
    $clean = normalize_untrusted($text);
    $flags = detect_injection($clean);

    // Nur „starke" Muster zählen fürs Risiko – URLs/Codeblöcke sind in echten
    // Nachrichten normal und dienen nur als Kontext, nicht als Auslöser.
    $strong = ['instruction-override', 'system-prompt', 'role-hijack',
               'role-marker', 'special-tokens', 'jailbreak', 'exfil-action'];
    $score = count(array_intersect($flags, $strong));
    $risk = $score >= 2 ? 'high' : ($score >= 1 ? 'medium' : 'low');

    return ['clean' => $clean, 'flags' => $flags, 'risk' => $risk];
}

/** Unicode normalisieren + unsichtbare/gefährliche Codepoints entfernen. */
function normalize_untrusted(string $text): string
{
    // NFKC vereinheitlicht z.B. Voll-/Halbbreite und Ligaturen (falls intl da).
    if (class_exists(\Normalizer::class)) {
        $n = \Normalizer::normalize($text, \Normalizer::FORM_KC);
        if (is_string($n)) $text = $n;
    }

    // Unsichtbare / gefährliche Codepoints raus:
    //  200B-200D, 2060, FEFF  -> Zero-Width / Word-Joiner (versteckter Text)
    //  202A-202E, 2066-2069   -> Bidi-Steuerung (kann Text optisch umdrehen)
    //  E0000-E007F            -> Unicode-TAG-Zeichen: schmuggeln UNSICHTBAREN
    //                            ASCII-Text, den ein LLM aber „liest"
    $text = preg_replace(
        '/[\x{200B}-\x{200D}\x{2060}\x{FEFF}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{E0000}-\x{E007F}]/u',
        '',
        $text
    ) ?? $text;

    // Steuerzeichen raus, außer Tab (09), LF (0A), CR (0D).
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text) ?? $text;
}

/**
 * Heuristische Muster – SIGNAL, kein Filter. Bewusst konservativ und leicht
 * erweiterbar. False Positives/Negatives sind eingeplant; nie hart blockieren.
 * @return string[] Namen der getroffenen Muster
 */
function detect_injection(string $text): array
{
    $t = mb_strtolower($text);
    $flags = [];

    $patterns = [
        'instruction-override' => '/\b(ignore|disregard|forget)\b.{0,30}\b(previous|prior|above|earlier|all)\b.{0,30}\b(instruction|prompt|rule|message)/u',
        'system-prompt'        => '/\b(system|developer)\s*(prompt|message|instruction)/u',
        'role-hijack'          => '/\b(you are now|act as|from now on|pretend to be|roleplay as|you must)\b/u',
        'role-marker'          => '/(^|\n)\s*(system|assistant|user)\s*:/u',
        'special-tokens'       => '/<\|.*?\|>|\[\/?INST\]|<<SYS>>/u',
        'jailbreak'            => '/\b(jailbreak|developer mode|dan mode|do anything now)\b/u',
        'exfil-action'         => '/\b(forward|send|email|export|delete|drop|reveal|print)\b.{0,30}\b(all|every|database|contacts?|messages?|data|table|prompt|key|secret)/u',
        'code-fence'           => '/```|~~~/u',
        'url'                  => '/https?:\/\//u',
    ];

    foreach ($patterns as $name => $re) {
        if (preg_match($re, $t)) $flags[] = $name;
    }
    return $flags;
}
