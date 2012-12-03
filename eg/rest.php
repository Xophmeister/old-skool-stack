<?php
  require_once 'oracle.php';
  require_once 'rest.php';

  $db = new Oracle('oracle.localhost:1521/MYSID', 'someuser', 'password');
  $rest = new REST();
  
  switch ($rest->v()) {
    case 'GET':
      $results = $db->exec('select username, real_name from app_users');
      $rest->payload($results);
      break;
    
    case 'POST':
      $dbParams = array(':uid' => $rest->v('userName'), ':realName' => $rest->v('realName'));
      $success = $db->exec('insert into app_users (username, real_name) values (:uid, :realName)', $dbParams);
      $rest->payload($success);
      break;
    
    case 'DELETE':
      $dbParams = array(':uid' => $rest->v('userName'));
      $success = $db->exec('delete from app_users where username = :uid', $dbParams);
      $rest->payload($success);
      break;
   
    case 'PUT':
      $dbParams = array(':uid' => $rest->v('userName')), ':realName' => $rest->v('realName'));
      $success = $db->exec('update app_users set real_name = :realName where username = :uid', $dbParams);
      $rest->payload($success);
      break;
    
    default:
      $rest->payload(null);
      break;
  }
?>
