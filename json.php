<?php
  require_once 'error.php';

  class JSON {
    private $errorStack;
    private $broken = false;

    private $requestStart;
    
    private $json = array('meta'    => array('success' => false,                // Request success
                                             'count'   => 0,                    // Total record count
                                             'page'    => array('now'  => 1,    // Current page
                                                                'size' => 10),  // Records per page (i.e., pages = ceil(meta.count / meta.page.size))
                                             't'       => 0),                   // Request time
                          'payload' => array());                                // Payload

    function __construct(&$errorStack) {
      $this->errorStack = $errorStack;
      $this->requestStart = microtime(true);
    }

    function __destruct() {
      $err = $this->errorStack->show();
      if (is_array($err)) {
        $this->json['meta']['success'] = false;
        $this->json['payload'] = $err;
      } else {
        $this->json['meta']['success'] = true;
      }

      if ($this->json['meta']['count'] == 0) $this->json['meta']['count'] = count($this->json['payload']);
      $this->json['meta']['t'] = microtime(true) - $this->requestStart;

      // Output
      header('Content-type: application/json');
      echo json_encode($this->json);
    }

    public function payload($data) {
      $this->json['payload'] = $data;
    }
  }
?>
