<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$requestMethod = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

if ($requestMethod === 'POST' && isset($data['repName'])) {
    $repName = $data['repName'];
    $timestamp = time();
    $logEntry = ['repName' => $repName, 'timestamp' => $timestamp];

    $existingLogs = json_decode($_COOKIE['repLoginLogs'] ?? '[]', true);
    $existingLogs[] = $logEntry;

    if (count($existingLogs) > 100) {
        $existingLogs = array_slice($existingLogs, -100);
    }

    setcookie('repLoginLogs', json_encode($existingLogs), [
        'expires' => time() + 86400 * 30,
        'path' => '/',
        'samesite' => 'Strict',
        'secure' => true
    ]);

    echo json_encode(['success' => true, 'message' => 'لاگ ورود با موفقیت ثبت شد']);
    http_response_code(201);
    exit();
} elseif ($requestMethod === 'GET') {
    $existingLogs = json_decode($_COOKIE['repLoginLogs'] ?? '[]', true);
    echo json_encode($existingLogs);
    http_response_code(200);
    exit();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'درخواست نامعتبر']);
    exit();
}
