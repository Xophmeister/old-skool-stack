<?php
  session_destroy();
  session_start();

  $currentFile = end(explode('/', $_SERVER['PHP_SELF']));

  include "error.php";

  // I don't work after 5pm
  if (date("G") >= 17) {
    $err->add('Go away! Sleeping!');
  }

  $err->complain();
?>