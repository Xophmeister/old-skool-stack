<?php
  require_once 'app.php';

  class JSON extends Plugin {
    private $requestStart;
    private $json = array('meta'    => array('ok' => false,  // Request success
                                             't'  => 0),     // Request time
                          'payload' => null);                // Payload

    // This is all there is to it //////////////////////////////////////
    
    function __construct() {
      $this->requestStart = microtime(true);
    }

    function __destruct() {
      $this->json['meta']['t'] = microtime(true) - $this->requestStart;

      $errors = isset($this->app) ? $this->app->trace() : null;
      if (is_array($errors)) {
        $this->json['payload'] = $errors;
      } else {
        $this->json['meta']['ok'] = true;
      }

      // Output
      header('Content-type: application/json');
      echo json_encode($this->json);
    }

    public function payload($data) {
      $this->json['payload'] = $data;
    }
  }
?>
