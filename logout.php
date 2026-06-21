<?php
require 'config.php';
if (isLoggedIn()) {
    db()->prepare("DELETE FROM users_online WHERE ip = ?")->execute([userIp()]);
}
session_destroy();
header('Location: login.php');
exit;
