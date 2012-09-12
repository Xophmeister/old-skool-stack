<?php
  require_once 'session.php';

  class ErrorStack {
    private $errorPage;
    private $stack;

    private function currentPage() {
      return end(explode('/', __FILE__));
    }

    function __construct($errorPage = 'showErrors.php') {
      $this->errorPage = $errorPage;
    }

    public function add($desc, $fatal) {
      if ($this->currentPage() != $this->errorPage) {
        $this->stack[] = array('timestamp'   => time(),
                               'description' => $desc,
                               'fatal'       => $fatal);
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
      $fatal = false;

      if ($this->currentPage() != $this->errorPage && is_array($this->stack)) {
        foreach ($this->stack as $err)
          $fatal = $fatal || $err['fatal'];
      }

      if ($fatal) {
        $pickled = serialize($this);
        $_SESSION['err'] = $pickled;

        @header('Location: '.$this->errorPage);
        exit;
      }
    }
  }
?>
