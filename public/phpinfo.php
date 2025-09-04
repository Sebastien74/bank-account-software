<?php

$ips = ['::1', '127.0.0.1', 'fe80::1', '194.51.155.21', '195.135.16.88', '176.135.112.19', '2a02:8440:5341:81fb:fd04:6bf3:c8c7:1edb', '88.173.106.115', '2001:861:43c3:ce70:bd5f:81d1:7710:888b', '2001:861:43c3:ce70:45e7:2aa7:ab50:c245'];
$allowed = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && in_array($_SERVER['HTTP_X_FORWARDED_FOR'], $ips, true))
    || in_array(@$_SERVER['REMOTE_ADDR'], $ips, true);
if (!$allowed) {
    header('HTTP/1.0 403 Forbidden');
    require_once $_SERVER['DOCUMENT_ROOT'].'/denied.php';
    exit;
}

phpinfo();
