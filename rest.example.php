<?php
  require_once 'oracle.php';
  require_once 'json.php';

  $db = new Oracle('oracle.localhost:1521/MYSID', 'someuser', 'password');
  $json = new JSON();

  // GET or POST REST resource
  if (count($_GET)) {
    // GET username resource
    $results = $db->exec('select username from app_users');
    $json->payload($results);
  } else if (count($_POST)) {
    // POST user to username resource
    if (isset($_POST['username'])) {
      $dbParams = array(':uid' => $_POST['username']);
      $success = $db->exec('insert into app_users (username) values (:uid)', $dbParams);
      $json->payload($success);
    } else {
      $json->payload(null);
    }
  } else {
    // Huh?
    $json->payload(null);
  }
?>
