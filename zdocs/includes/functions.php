<?php
if (!defined('ROOT_PATH')) die('Forbidden');

function CheckRegister() {
    global $zdocs;
    // 自动注册账户
    if (defined('REGISTER_USER') && defined('REGISTER_PASS') && defined('REGISTER_MAIL')) {
        $stmt = $zdocs->pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
        $stmt->execute(['username' => REGISTER_USER, 'email' => REGISTER_MAIL]);
        $user = $stmt->fetch();
        if (!$user) {
            $salt = substr(md5(microtime(true)), 0, 16);
            $stmt = $zdocs->pdo->prepare("INSERT INTO users (username, email, password, salt, role) VALUES (:username, :email, :password, :salt, :role)");
            $stmt->execute([
                'username' => REGISTER_USER,
                'email'    => REGISTER_MAIL,
                'password' => password_hash(REGISTER_PASS. $salt, PASSWORD_BCRYPT),
                'salt'     => $salt,
                'role'     => 'root'
            ]);
        }
    }
}

function CheckLogin() {
    if (!isset($_SESSION['user'])) {
        JsonOutput(false, '未登录');
        exit;
    }
}

function VerifyRole() {
    global $zdocs;
    if (!isset($_SESSION['user'], $_SESSION['role'])) {
        JsonOutput(false, '未登录或权限不足');
        exit;
    }
    if ($_SESSION['role'] !== 'root') {
        JsonOutput(false, '你没有权限这么做');
    }
}

function JsonOutput($success, $message, $statusCode = 200) {
    Header("Content-Type: application/json", true, $statusCode);
    echo json_encode(['success' => $success,'message' => $message]);
}

function RegisterAction($name, $callback, $checkLogin = false, $requireRole = false) {
    global $actions;
    if (isset($actions[$name])) {
        throw new Exception(sprintf("Action already exists: %s", $name));
    }
    $actions[$name] = [
        'callback' => $callback,
        'checkLogin' => $checkLogin,
        'requireRole' => $requireRole
    ];
}
