<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Headers: Content-Type');
    echo '';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = [];
    }
} else {
    $data = $_POST;
}
$email = isset($data['email']) ? trim((string)$data['email']) : '';
$code = isset($data['code']) ? trim((string)$data['code']) : '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_email']);
    exit;
}
$domain = strtolower(substr(strrchr($email, '@'), 1) ?: '');
if (!in_array($domain, ['mail.ru', 'gmail.com'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'unsupported_domain']);
    exit;
}
if ($code === '') {
    try {
        $code = (string)random_int(100000, 999999);
    } catch (Throwable $e) {
        $code = (string)mt_rand(100000, 999999);
    }
}
$subject = 'Подтверждение почты';
$body = 'Ваш код подтверждения: ' . $code;
$from = getenv('MAIL_FROM') ?: 'shubka@nexules.local';
$headers = 'From: ' . $from . "\r\n"
    . 'Reply-To: ' . $from . "\r\n"
    . 'Content-Type: text/plain; charset=UTF-8';
$sent = false;
if (function_exists('mail')) {
    $sent = @mail($email, $subject, $body, $headers);
}
if (!$sent) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'mail_failed', 'code' => $code]);
    exit;
}
echo json_encode(['ok' => true, 'code' => $code]);
exit;
