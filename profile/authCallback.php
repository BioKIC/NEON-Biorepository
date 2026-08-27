<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OpenIdProfileManager.php');
include_once($SERVER_ROOT . '/config/auth_config.php');
require_once($SERVER_ROOT . '/vendor/autoload.php');
use Jumbojett\OpenIDConnectClient;
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/profile/authCallback.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/profile/authCallback.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/profile/authCallback.en.php');


$profManager = new OpenIdProfileManager();

$AUTH_PROVIDER = $AUTH_PROVIDER ?? 'oid';

$oidc = new OpenIDConnectClient($PROVIDER_URLS[$AUTH_PROVIDER], $CLIENT_IDS[$AUTH_PROVIDER], $CLIENT_SECRETS[$AUTH_PROVIDER], $PROVIDER_URLS[$AUTH_PROVIDER]); // assumes that the issuer is identical to the providerUrl, as seems to be the case for microsoft

if(isset($SHOULD_UPGRADE_INSECURE_REQUESTS)){
  $oidc->setHttpUpgradeInsecureRequests($SHOULD_UPGRADE_INSECURE_REQUESTS);
}
if(isset($SHOULD_VERIFY_PEERS)){
  $oidc->setVerifyPeer($SHOULD_VERIFY_PEERS);
}


if (array_key_exists('code', $_REQUEST) && $_REQUEST['code']) {
  
  try{
    $status = $oidc->authenticate();
    $claims = $oidc->getVerifiedClaims();
    $sid = isset($claims->sid) ? $claims->sid : false;
  }
  catch (Exception $ex){
    $_SESSION['last_message'] = $LANG['CAUGHT_EXCEPTION'] . ' ' . $ex->getMessage() . ' <ERR/>';
    header('Location:' . $CLIENT_ROOT . '/profile/index.php');
    exit();
  }
  //status is 1 if authorization was successful
  if($status){
    //authentication token to query the auth0 API
    $_SESSION['ACCESS_TOKEN'] = $oidc->getAccessToken();
    //sub is the subscriber - the unique ID for that user x provider (google, cilogon, direct, etc.)
    $sub = $oidc->requestUserInfo('sub');
    $_SESSION['SUBSCRIBER'] = $sub;
    $_SESSION['AUTH_PROVIDER'] = $AUTH_PROVIDER;
    $_SESSION['AUTH_CLIENT_ID'] = $oidc->getClientID();

    // see if authenticated user is in usersthirdpartyauth table (and thus in users)
    if($profManager->authenticate($sub, $PROVIDER_URLS[$AUTH_PROVIDER])){
      // add session to usersthirdpartysessions
      $profManager->linkThirdPartySid($sid, session_id(), $_SERVER['REMOTE_ADDR']);
      // update user information from Auth0 fields
      $firstName = $oidc->requestUserInfoByID($sub, 'given_name')
          ?? $oidc->requestUserInfoByID($sub, 'user_metadata.first_name');
      
      $lastName = $oidc->requestUserInfoByID($sub, 'family_name')
          ?? $oidc->requestUserInfoByID($sub, 'user_metadata.last_name');
          
      $organization = $oidc->requestUserInfoByID($sub, 'user_metadata.ror_id')
          ?? $oidc->requestUserInfoByID($sub, 'user_metadata.organization');
      
      $profManager->updateLocalUserFromAuth0Metadata(
          $sub,
          $oidc->getProviderURL(),
          $firstName,
          $lastName,
          $organization,
          $oidc->requestUserInfoByID($sub, 'user_metadata.affiliation'),
          $oidc->requestUserInfoByID($sub, 'user_metadata.organization_country'),
          $oidc->requestUserInfoByID($sub, 'user_metadata.subject_matter_expertise_provider'),
          $oidc->requestUserInfoByID($sub, 'user_metadata.orcid')
      );
      if($_SESSION['refurl']){
        header("Location:" . $_SESSION['refurl']);
        unset($_SESSION['refurl']);
      } else {
        header("Location: " . $CLIENT_ROOT . '/index.php');
        unset($_SESSION['refurl']);
      }
    }
    else {
      if ($email = $oidc->requestUserInfo('email')){
        try{
          // try to link auth user with local user, if not in users table, make new user
          $status = $profManager->linkLocalUserOidSub($email, $sub, $oidc->getProviderURL(), $oidc->requestUserInfo('nickname'), $oidc->requestUserInfoByID($sub, 'user_metadata.first_name'), $oidc->requestUserInfoByID($sub, 'user_metadata.last_name'));
          // update user information from Auth0 fields
          $profManager->updateLocalUserFromAuth0Metadata(
              $sub,
              $oidc->getProviderURL(),
              $oidc->requestUserInfoByID($sub, 'user_metadata.first_name'),
              $oidc->requestUserInfoByID($sub, 'user_metadata.last_name'),
              $oidc->requestUserInfoByID($sub, 'user_metadata.ror_id'),
              $oidc->requestUserInfoByID($sub, 'user_metadata.affiliation'),
              $oidc->requestUserInfoByID($sub, 'user_metadata.organization_country'),
              $oidc->requestUserInfoByID($sub, 'user_metadata.subject_matter_expertise_provider'),
              $oidc->requestUserInfoByID($sub, 'user_metadata.orcid')
          );
        }catch (Exception $ex){
          $_SESSION['last_message'] = $LANG['CAUGHT_EXCEPTION'] . ' '  . $ex->getMessage();
          header('Location:' . $CLIENT_ROOT . '/profile/index.php');
          exit();
        }
        if($status){
          if($profManager->authenticate($sub, $PROVIDER_URLS[$AUTH_PROVIDER])){
            $profManager->linkThirdPartySid($sid, session_id(), $_SERVER['REMOTE_ADDR']);
            if($_SESSION['refurl']){
              header("Location:" . $_SESSION['refurl']);
              unset($_SESSION['refurl']);
            } else {
              header("Location: " . $CLIENT_ROOT . '/index.php');
              unset($_SESSION['refurl']);
            }
          }
          else{
            $_SESSION['last_message'] = $LANG['UNKNOWN_ERROR'] . " <ERR/>";
            header('Location:' . $CLIENT_ROOT . '/profile/index.php');
            //@TODO Consider logging this error to PHP logfiles
          }
        }else{
          $_SESSION['last_message'] = $LANG['ERROR'] . " <ERR/>";
          header('Location:'. $CLIENT_ROOT . '/profile/index.php');
        }
        
      }
      else{
        $_SESSION['last_message'] = $LANG['UNABLE_RETRIEVE_EMAIL'] . " <ERR/>";
        header('Location:' . $CLIENT_ROOT . '/profile/index.php');
      }
    }
  } else {
    $_SESSION['last_message'] = $LANG['AUTHENTICATION_FAILED'] . " <ERR/>";
    header('Location:' . $CLIENT_ROOT . '/profile/index.php');    
  }

}