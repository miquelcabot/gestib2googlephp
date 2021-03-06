<?php
if (!file_exists(__DIR__ . '/config.php')) {
  die("El fitxer config.php no existeix");
}

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__ . '/config.php';
session_start();

$client = new Google_Client();
$client->setAuthConfig(CLIENT_SECRET_PATH);
$client->setApplicationName(APPLICATION_NAME);
$client->setScopes(SCOPES);
$client->setAccessType('offline');

$protocol = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';

$client->setRedirectUri($protocol . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] );

if (!isset($_GET['code'])) {
  $auth_url = $client->createAuthUrl();
  header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
} else {
  $client->authenticate($_GET['code']);
  $_SESSION['access_token'] = $client->getAccessToken();
  $redirect_uri = str_replace('oauth2callback.php', '', $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}
?>