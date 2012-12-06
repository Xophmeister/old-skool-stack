<?php
  require_once 'app.php';
  require_once 'oracle.php';
  require_once 'json.php';

  $app = new Application();
  $db = new Oracle('oracle.localhost:1521/MYSID', 'someuser', 'password');
  $json = new JSON();

  $app->registerPlugin('db', $db);
  $app->registerPlugin('json', $json);
  
  switch ($app->arg()) {
    case 'GET':
      $results = $app->plugin('db')->exec('select username, real_name from app_users');
      $app->plugin('json')->payload($results);
      break;
    
    case 'POST':
      $dbParams = array(':uid' => $app->arg('userName'), ':realName' => $app->arg('realName'));
      $success = $app->plugin('db')->exec('insert into app_users (username, real_name) values (:uid, :realName)', $dbParams);
      $app->plugin('json')->payload($success);
      break;
    
    case 'DELETE':
      $dbParams = array(':uid' => $app->arg('userName'));
      $success = $app->plugin('db')->exec('delete from app_users where username = :uid', $dbParams);
      $app->plugin('json')->payload($success);
      break;
   
    case 'PUT':
      $dbParams = array(':uid' => $app->arg('userName')), ':realName' => $app->arg('realName'));
      $success = $app->plugin('db')->exec('update app_users set real_name = :realName where username = :uid', $dbParams);
      $app->plugin('json')->payload($success);
      break;
    
    default:
      $app->log('HTTP/1.1 405 Method Not Allowed');
      break;
  }
?>
