<?php
  class ErrorStack {
    private $errorPage;
    private $stack;

    function __construct($errorPage = 'showErrors.php') {
      $this->errorPage = $errorPage;
    }

    public function add($desc) {
      global $currentFile;

      if ($currentFile != $this->errorPage) {
        $this->stack[] = array('timestamp'   => time(),
                               'description' => $desc);
      }
    }

    public function show() {
      if (is_array($this->stack)) {
        return array_reverse($this->stack);
      } else {
        return false;
      }
    }

    public function complain() {
      global $currentFile;

      if (count($this->stack) > 0 && $currentFile != $this->errorPage) {
        $pickled = serialize($this);
        $_SESSION['err'] = $pickled;

        @header('Location: '.$this->errorPage);
        exit;
      }
    }
  }

  // Initialise
  // (n.b., Needs to be within a session)
  if (isset($_SESSION['err'])) {
    $err = unserialize($_SESSION['err']);
  } else {
    $err = new ErrorStack();
  }
?>