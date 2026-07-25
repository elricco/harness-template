<?php
declare(strict_types=1);

// GET /api/form-token.php -> { "token": "..." }
require __DIR__ . '/../../config.php';   // liegt ÜBER dem Webroot
require __DIR__ . '/lib/security.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store'); // nie cachen -> Token bleibt frisch

echo json_encode(['token' => issue_token(FORM_SECRET)]);
