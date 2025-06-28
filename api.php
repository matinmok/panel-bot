<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// تنظیمات
$DATA_DIR = __DIR__ . '/data';
$ANNOUNCEMENT_FILE = $DATA_DIR . '/announcement.txt';
$LOGS_FILE = $DATA_DIR . '/rep_logs.json';

// ایجاد پوشه data اگر وجود نداشته باشد
if (!is_dir($DATA_DIR)) {
    mkdir($DATA_DIR, 0755, true);
}

function sendResponse($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        handleGet($action);
        break;
    case 'POST':
        handlePost($action);
        break;
    default:
        sendResponse(false, null, 'Method not allowed', 405);
}

function handleGet($action) {
    global $ANNOUNCEMENT_FILE;
    
    switch ($action) {
        case 'announcement':
            getAnnouncement();
            break;
        case 'ping':
            sendResponse(true, ['status' => 'ok'], 'API is working');
            break;
        default:
            sendResponse(false, null, 'Invalid action', 400);
    }
}

function handlePost($action) {
    switch ($action) {
        case 'log':
            logRepresentativeLogin();
            break;
        case 'announcement':
            setAnnouncement();
            break;
        default:
            sendResponse(false, null, 'Invalid action', 400);
    }
}

function getAnnouncement() {
    global $ANNOUNCEMENT_FILE;
    
    if (file_exists($ANNOUNCEMENT_FILE)) {
        $announcement = file_get_contents($ANNOUNCEMENT_FILE);
        $announcement = trim($announcement);
        
        if (!empty($announcement)) {
            sendResponse(true, [
                'text' => $announcement,
                'has_announcement' => true
            ], 'Announcement retrieved');
        } else {
            sendResponse(true, [
                'text' => '',
                'has_announcement' => false
            ], 'No announcement');
        }
    } else {
        sendResponse(true, [
            'text' => '',
            'has_announcement' => false
        ], 'No announcement file');
    }
}

function setAnnouncement() {
    global $ANNOUNCEMENT_FILE;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['text'])) {
        sendResponse(false, null, 'Text parameter required', 400);
    }
    
    $text = sanitizeInput($input['text']);
    
    if (empty($text)) {
        // حذف اطلاعیه
        if (file_exists($ANNOUNCEMENT_FILE)) {
            unlink($ANNOUNCEMENT_FILE);
        }
        sendResponse(true, null, 'Announcement removed');
    } else {
        // ذخیره اطلاعیه
        $result = file_put_contents($ANNOUNCEMENT_FILE, $text);
        if ($result !== false) {
            sendResponse(true, ['text' => $text], 'Announcement saved');
        } else {
            sendResponse(false, null, 'Failed to save announcement', 500);
        }
    }
}

function logRepresentativeLogin() {
    global $LOGS_FILE;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['repName']) || !isset($input['serverUrl'])) {
        sendResponse(false, null, 'repName and serverUrl required', 400);
    }
    
    $repName = sanitizeInput($input['repName']);
    $serverUrl = sanitizeInput($input['serverUrl']);
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    // خواندن لاگ‌های موجود
    $logs = [];
    if (file_exists($LOGS_FILE)) {
        $logs = json_decode(file_get_contents($LOGS_FILE), true) ?? [];
    }
    
    // افزودن لاگ جدید
    $newLog = [
        'id' => uniqid('log_'),
        'repName' => $repName,
        'serverUrl' => $serverUrl,
        'timestamp' => time(),
        'datetime' => date('Y-m-d H:i:s'),
        'ip' => $ipAddress,
        'userAgent' => $userAgent
    ];
    
    array_unshift($logs, $newLog);
    
    // حفظ فقط 500 لاگ آخر
    $logs = array_slice($logs, 0, 500);
    
    // ذخیره لاگ‌ها
    $result = file_put_contents($LOGS_FILE, json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    if ($result !== false) {
        sendResponse(true, $newLog, 'Login logged successfully');
    } else {
        sendResponse(false, null, 'Failed to save log', 500);
    }
}
?>
