<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 04.04.17
 * Time: 13:35
 */

require '../app/Mage.php'; //Path to Magento
Mage::app();

// $callbackUrl is a path to your file with OAuth authentication example for the Admin user
$callbackUrl = "http://magento.pixy.pro/tests/customers.php";
$temporaryCredentialsRequestUrl = "http://magento.pixy.pro/oauth/initiate?oauth_callback=" . urlencode($callbackUrl);
$adminAuthorizationUrl = 'http://magento.pixy.pro/admin/oauth_authorize';
$accessTokenRequestUrl = 'http://magento.pixy.pro/oauth/token';
$apiUrl = 'http://magento.pixy.pro/api/rest';
$consumerKey = '243dfdaf97c8d25300e88700e860f942';
$consumerSecret = 'efca5432165bbce206987cc5e562585e';

session_start();
if (!isset($_GET['oauth_token']) && isset($_SESSION['state']) && $_SESSION['state'] == 1) {
    $_SESSION['state'] = 0;
}
try {
    $authType = ($_SESSION['state'] == 2) ? OAUTH_AUTH_TYPE_AUTHORIZATION : OAUTH_AUTH_TYPE_URI;
    $oauthClient = new OAuth($consumerKey, $consumerSecret, OAUTH_SIG_METHOD_HMACSHA1, $authType);
    $oauthClient->enableDebug();

    if (!isset($_GET['oauth_token']) && !$_SESSION['state']) {
        $requestToken = $oauthClient->getRequestToken($temporaryCredentialsRequestUrl);
        $_SESSION['secret'] = $requestToken['oauth_token_secret'];
        $_SESSION['state'] = 1;
        header('Location: ' . $adminAuthorizationUrl . '?oauth_token=' . $requestToken['oauth_token']);
        exit;
    } else if ($_SESSION['state'] == 1) {
        $oauthClient->setToken($_GET['oauth_token'], $_SESSION['secret']);
        $accessToken = $oauthClient->getAccessToken($accessTokenRequestUrl);
        $_SESSION['state'] = 2;
        $_SESSION['token'] = $accessToken['oauth_token'];
        $_SESSION['secret'] = $accessToken['oauth_token_secret'];
        header('Location: ' . $callbackUrl);
        exit;
    } else {
        $oauthClient->setToken($_SESSION['token'], $_SESSION['secret']);

        $resourceUrl = "$apiUrl/customers";
        $oauthClient->fetch($resourceUrl, array(), 'GET', array('Content-Type' => 'application/json'));
// print_r($_SESSION);
// die();
        echo $oauthClient->getLastResponse();
    }
} catch (OAuthException $e) {
    print_r($e->getMessage());
    echo "<br/>";
    print_r($e->lastResponse);
}