<?php
// Nach ../config.php kopieren – WICHTIG: eine Ebene ÜBER dem Webroot,
// damit die Datei nicht per URL abrufbar ist. Die api/*.php erwarten sie
// unter __DIR__ . '/../../config.php'.
//
// Struktur auf dem Server:
//   /kunde/                  <- hier: config.php  (NICHT öffentlich)
//   /kunde/rl_store/         <- Rate-Limit-Dateien (schreibbar, NICHT öffentlich)
//   /kunde/httpdocs/         <- Webroot: hier das Astro-Build + api/

declare(strict_types=1);

define('SITE_ORIGIN', 'https://deine-domain.de');

// Signatur-Geheimnis fürs Zeit-Token. openssl rand -hex 32
define('FORM_SECRET', 'HIER-LANGEN-ZUFALLSSTRING-EINSETZEN');

// Schreibbares Verzeichnis für das Rate-Limit, außerhalb des Webroots.
define('RATE_LIMIT_DIR', __DIR__ . '/rl_store');

// SMTP-Zugang deines Hosters (EU-Postfach).
define('SMTP_HOST', 'smtp.dein-hoster.de');
define('SMTP_PORT', 465);                       // 465 = SMTPS, 587 = STARTTLS
define('SMTP_USER', 'noreply@deine-domain.de');
define('SMTP_PASS', 'dein-smtp-passwort');

define('CONTACT_FROM', 'noreply@deine-domain.de'); // eigenes Postfach
define('CONTACT_TO',   'du@deine-domain.de');       // wohin die Nachricht geht
