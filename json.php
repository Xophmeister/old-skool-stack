<?php
  class JSON {
    private $session = null;
    private $broken = false;

    private $requestStart;
    
    private $json = array('meta'    => array('ok' => false,  // Request success
                                             't'  => 0),     // Request time
                          'payload' => null);                // Payload

    function __construct(&$session = null) {
      if (isset($session)) $this->session = &$session;
      $this->requestStart = microtime(true);
    }

    function __destruct() {
      $this->json['meta']['t'] = microtime(true) - $this->requestStart;

      if (isset($this->session)) {
        $errors = $this->session->trace();

        if (is_array($errors)) {
          $this->json['payload'] = $errors;
        } else {
          $this->json['meta']['ok'] = true;
        }
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
