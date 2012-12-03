<?php
  require_once 'session.php';
  require_once 'oracle.php';
  require_once 'ldap.php';

  $app = new Session();

  $authenticated = false;

  // Already logged in?
  if (isset($_SESSION['loginUser']) && isset($_SESSION['authToken'])) {
    // Connect to LDAP server
    $ldap = new LDAP('ldap.localhost', 'o=ROOT', $app);

    // Get directory info for username in session
    $dirInfo = $ldap->get('cn='.$app->get('loginUser'));

    // If token field matches token in session, all is well
    if ($dirInfo['token'] == $_SESSION['authToken']) $authenticated = true;
  }

  // Login request
  if (isset($_POST['loginUser']) && isset($_POST['loginPwd'])) {
    // Connect to LDAP server
    $ldap = new LDAP('ldap.localhost', 'o=ROOT', $app);

    // Attempt user bind
    if ($ldap->bind($_POST['loginUser'], $_POST['loginPwd'])) {
      // Check the user has access to our system by looking at a DB table
      $db = new Oracle('oracle.localhost:1521/MYSID', 'someuser', 'password', $app);

      $dbParams = array(':uid' => $_POST['loginUser']);
      $users = $db->exec('select username from app_users where username = :uid', $dbParams);

      if (count($users) == 1) {
        // All is well: Get authentication token from directory and authenticate
        $dirInfo = $ldap->get('cn='.$app->get('loginUser'));

        $authenticated = true;
        $app->set('loginUser', $_POST['loginUser']);
        $app->set('authToken', $dirInfo['token']);       
      } else {
        // User doesn't exist in DB table
        $app->log('You don\'t have clearance to use this application.', true);
      }
  
    } else {
      // Couldn't bind to directory
      $app->log('Incorrect username or password.', true);
    }
  }

  if (!$authenticated) {
    $app->log('Access Denied: Cannot authenticate your credentials.', true);
  }

  // Call this when we're done to route any fatal errors to the error page
  $app->complain();
?>
