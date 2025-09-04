<?php

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

session_start();

require dirname(__DIR__) . '/config/bootstrap.php';

$request = new Request();
$matches = explode('/', $_SERVER['REQUEST_URI']);
$secureKey = $matches[count($matches) - 2];
$ips = ['::1', '127.0.0.1', 'fe80::1', '194.51.155.21', '195.135.16.88', '176.135.112.19', '2a02:8440:5341:81fb:fd04:6bf3:c8c7:1edb', '88.173.106.115', '2001:861:43c3:ce70:bd5f:81d1:7710:888b', '2001:861:43c3:ce70:45e7:2aa7:ab50:c245'];
$allowed = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && in_array($_SERVER['HTTP_X_FORWARDED_FOR'], $ips, true))
    || in_array(@$_SERVER['REMOTE_ADDR'], $ips, true);
$validToken = !empty($_COOKIE['SECURITY_TOKEN']) && $_COOKIE['SECURITY_TOKEN'] === $_ENV['SECURITY_TOKEN'];
$validToken = !empty($_SESSION['SECURITY_TOKEN']) && $_SESSION['SECURITY_TOKEN'] === $_ENV['SECURITY_TOKEN'] || $validToken;
$validToken = !empty($_GET['token']) && $_ENV['APP_SECRET'] === $_GET['token'] || $validToken;
$isAdminFile = preg_match('/build\/admin/', $_SERVER['REQUEST_URI']) && $validToken;

$isValid = $allowed;
if ($isAdminFile) {
    $isValid = true;
} elseif (preg_match('/uploads\/emails/', $_SERVER['REQUEST_URI']) && $validToken) {
    $isValid = true;
} elseif (isset($_COOKIE['SECURITY_USER_SECRET']) && $_COOKIE['SECURITY_USER_SECRET'] === $secureKey) {
    $isValid = true;
}

$pathMatches = explode('?', $_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI']);
$path = !str_contains($pathMatches[0], 'public') ? str_replace($_SERVER['DOCUMENT_ROOT'], $_SERVER['DOCUMENT_ROOT'] . '/public', $pathMatches[0]) : $pathMatches[0];
$filesystem = new Filesystem();

if ($filesystem->exists($path)) {

    $file = new File($path);
    $file = new UploadedFile($file->getPathname(), $file->getFilename(), $file->getMimeType(), NULL, true);
    $extension = $file->getExtension();

    $mimeType = $file->getMimeType();
    if ($extension === 'css') {
        $mimeType = 'text/css';
        header('Content-type: ' . $mimeType);
    } elseif ($extension === 'js') {
        $mimeType = 'application/javascript';
        header('Content-type: ' . $mimeType);
    }

    $isResource = $extension == ('css' || 'js');

    if ($isValid) {

        header('Content-type: ' . $mimeType);

        if (is_file($path)) {
            readfile($path);
        } elseif ($isResource) {
            include 'denied.php';
        } else {
            generateImage('Acces denied');
        }
    } else {

        if ($isResource) {
            include 'denied.php';
        } else {
            generateImage('Not found');
        }
    }
} else {
    generateImage('Not found');
}

/**
 * Generate image
 *
 * @param string $message
 */
function generateImage(string $message): void
{
    $img = imagecreatetruecolor(150, 45);
    $color = imagecolorallocate($img, 84, 182, 33);
    imagestring($img, 15, 15, 15, $message, $color);
    header('Content-type: image/jpeg');
    imagejpeg($img);
    imagedestroy($img);
}