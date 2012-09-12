<?php
  if (end(explode('/', __FILE__)) == 'index.php') session_destroy();
  session_start();
?>
