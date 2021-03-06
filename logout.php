<?php
if (!file_exists(__DIR__ . '/config.php')) {
  die("El fitxer config.php no existeix");
}

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__ . '/config.php';
session_start();

$accesstoken = $_SESSION['access_token'];

//Unset token and user data from session    
unset($_SESSION['access_token']);    
unset($_SESSION['userData']);    

//Reset OAuth access token    
$client = new Google_Client();

//$client->revokeToken();    
$client->revokeToken($accesstoken);

//Destroy entire session    
session_destroy();   

$protocol = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
$redirect_uri = str_replace('logout.php', '', $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
?>