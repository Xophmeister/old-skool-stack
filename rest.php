<?php
  // RESTful API wrapper that outputs JSON
  class REST {
    private $session = null;

    private $requestStart;
    
    private $json = array('meta'    => array('ok' => false,  // Request success
                                             't'  => 0),     // Request time
                          'payload' => null);                // Payload

    private $verb;
    private $parameters;

    function __construct(&$session = null) {
      if (isset($session)) $this->session = &$session;
      $this->requestStart = microtime(true);

      $this->verb = $_SERVER['REQUEST_METHOD'];
      switch ($this->verb) {
        case 'GET':
          $this->parameters = $_GET;
          break;
        case 'POST':
          $this->parameters = $_POST;
          break;
        default:
          parse_str(file_get_contents('php://input'), $this->parameters);
          break;
      }
    }

    function __destruct() {
      $this->json['meta']['t'] = microtime(true) - $this->requestStart;

      $errors = isset($this->session) ? $this->session->trace() : null;
      if (is_array($errors)) {
        $this->json['payload'] = $errors;
      } else {
        $this->json['meta']['ok'] = true;
      }

      // Output
      header('Content-type: application/json');
      echo json_encode($this->json);
    }

    // No parameter => verb
    // True         => complete parameters
    // Key          => parameter[Key]
    public function v($key = null) {
      if ($key == null) {
        return $this->verb;
      } else {
        if ($key == true) {
          return $this->parameters;
        } else if (is_array($this->parameters) && array_key_exists($key, $this->parameters)) {
          return $this->parameters[$key];
        } else {
          return null;
        }
      }
    }

    public function payload($data) {
      $this->json['payload'] = $data;
    }
  }
?>
