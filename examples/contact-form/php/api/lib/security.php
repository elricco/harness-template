<?php
declare(strict_types=1);

// Geteilte Sicherheitsfunktionen. Kein Ausgabe-Code hier drin.

/** Erstellt ein Token `<ms-timestamp>.<hmac>`. Beim Ausliefern des Formulars. */
function issue_token(string $secret): string
{
    $ts = (string) (int) round(microtime(true) * 1000);
    $sig = hash_hmac('sha256', $ts, $secret);
    return $ts . '.' . $sig;
}

/**
 * Prüft Signatur (HMAC, Konstantzeit) UND Alter.
 * - $minMs: frühestens so alt (Bots posten in <1s) -> Default 3s
 * - $maxMs: spätestens so alt (Token nicht ewig gültig) -> Default 1h
 */
function verify_token(string $token, string $secret, int $minMs = 3000, int $maxMs = 3600000): bool
{
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) return false;
    [$ts, $sig] = $parts;
    if ($ts === '' || !ctype_digit($ts)) return false;

    $expected = hash_hmac('sha256', $ts, $secret);
    if (!hash_equals($expected, $sig)) return false; // konstantzeit

    $age = (int) round(microtime(true) * 1000) - (int) $ts;
    return $age >= $minMs && $age <= $maxMs;
}

/** Zeilenumbrüche raus – gegen Header-Injection in reply_to/subject. */
function strip_crlf(string $s): string
{
    return trim(str_replace(["\r", "\n"], '', $s));
}

/**
 * Datei-basiertes Fixed-Window-Limit (für Shared Hosting ohne Redis).
 * true = erlaubt, false = blockiert.
 * Fail-open: ist der Store nicht schreibbar, wird NICHT blockiert
 * (Honeypot + Token schützen weiterhin) – aber es wird geloggt.
 */
function rate_limit(string $key, int $limit, int $windowSec, string $dir): bool
{
    if (!is_dir($dir)) @mkdir($dir, 0700, true);

    // Gelegentlich abgelaufene Einträge aufräumen (~1 %).
    if (random_int(1, 100) === 1) {
        foreach (glob($dir . '/*.json') ?: [] as $f) {
            $old = json_decode(@file_get_contents($f) ?: 'null', true);
            if (!is_array($old) || time() > ($old['reset'] ?? 0)) @unlink($f);
        }
    }

    $file = $dir . '/' . hash('sha256', $key) . '.json';
    $fp = @fopen($file, 'c+');
    if ($fp === false) {
        error_log("rate_limit: Store nicht schreibbar ($dir)");
        return true;
    }

    try {
        flock($fp, LOCK_EX);
        $now = time();
        $raw = stream_get_contents($fp);
        $b = json_decode($raw !== '' ? $raw : 'null', true);

        if (!is_array($b) || $now > ($b['reset'] ?? 0)) {
            $b = ['count' => 1, 'reset' => $now + $windowSec];
            $allowed = true;
        } elseif ($b['count'] >= $limit) {
            $allowed = false;
        } else {
            $b['count']++;
            $allowed = true;
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($b));
        fflush($fp);
        return $allowed;
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
