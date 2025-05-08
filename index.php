<?php
define('ROOT_PATH', __DIR__);
require_once(ROOT_PATH . '/zdocs/zdocs.php');

$zdocs = new ZDocs();
$actions = [];

include(ROOT_PATH . '/zdocs/includes/functions.php');
include(ROOT_PATH . '/zdocs/includes/actions.php');

SESSION_START();

if (defined('INSTALL_DB') && INSTALL_DB) {
    $zdocs->InstallDatabase();
}

CheckRegister();

if (isset($_GET['action']) && is_string($_GET['action']) && preg_match('/^\w+$/', $_GET['action'])) {
    $actName = trim($_GET['action']);
    if (isset($actions[$actName])) {
        $action = $actions[$actName];
        if ($action['checkLogin']) {
            CheckLogin();
        }
        if ($action['requireRole']) {
            VerifyRole();
        }
        $action['callback']();
    } else {
        JsonOutput(false, "Action Not Found");
    }
    exit;
}

include(ROOT_PATH . '/zdocs/includes/header.php');
include(ROOT_PATH . '/zdocs/includes/render.php');
include(ROOT_PATH . '/zdocs/includes/footer.php');
