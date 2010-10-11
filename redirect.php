<?php

/* Start session and load library. */
session_start();
require_once( dirname(__FILE__) . '/twitteroauth/twitteroauth.php');

/* Build TwitterOAuth object with client credentials. */
$connection = new TwitterOAuth(get_option('CONSUMER_KEY'), get_option('CONSUMER_SECRET'));
 
/* Get temporary credentials. */
$request_token = $connection->getRequestToken(get_option('OAUTH_CALLBACK'));

/* Save temporary credentials to session. */
$_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
 
/* If last connection failed don't display authorization link. */
switch ($connection->http_code) {
  case 200:
    /* Build authorize URL and redirect user to Twitter. */
    $url = $connection->getAuthorizeURL($token);
    header('Location: ' . $url); 
    break;
  default:
    /* Show notification if something went wrong. */
    echo 'Could not connect to Twitter. Refresh the page or try again later.';
}
