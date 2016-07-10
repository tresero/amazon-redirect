<?php
namespace Amazon;

require_once('vendor/autoload.php');
// Get the client ip address
$ip = $_SERVER['REMOTE_ADDR'];
$uri = $_SERVER['REQUEST_URI'];

use Amazon\Service\AmazonUrlBuilder;
use Amazon\DB\Db;

// get the config
$db = new \SQLite3(__DIR__ . "/amazonRedirect.db");
$testMode = $db->querySingle("SELECT value FROM config WHERE key = 'testMode'");

// if admin, redirect, move all this when tested
$request = filter_var(parse_url($uri,PHP_URL_PATH),FILTER_SANITIZE_URL);

//now remove amazon-url or subdir thing if exists
if (!empty($urlPrefix) ) {
    $path = substr($request,strlen($urlPrefix));
}

$urlbuilder = new Service\AmazonUrlBuilder();

$theurl = $urlbuilder->getLocalKindleUrl($ip);

if($testMode === 'false'){
    print $theurl . '&nbsp; - &nbsp' .  $ip;
} else {
    header("Location: $theurl");
    exit();
}