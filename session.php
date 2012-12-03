<?php
  class Session {
    private $currentPage;
    private $errorPage;

    private $errStack;

    function __construct($landingPage = 'index.php', $errorPage = 'showErrors.php') {
      $this->currentPage = end(explode('/', $_SERVER['PHP_SELF']));
      $this->errorPage = $errorPage;

      // Start session
      session_start();

      // Restart session if we're on the landing page
      if ($this->currentPage == $landingPage) {
        session_destroy();
        session_start();
      }

      // Depickle any error data
      if (isset($_SESSION['errStack'])) {
        $this->errStack = unserialize($_SESSION['errStack']);
      }
    }

    // The current page
    public function me() {
      return $this->currentPage;
    }

    // Get session value by key
    public function get($key) {
      return $_SESSION[$key];
    }

    // Set session value by key
    public function set($key, $value) {
      $_SESSION[$key] = $value;
    }

    // Append to error stack, if we're not on the error page
    public function log($desc, $fatal) {
      if ($this->currentPage != $this->errorPage) {
        $this->errStack[] = array('timestamp'   => floor(microtime(true) * 1000),
                                  'description' => $desc,
                                  'fatal'       => $fatal);
      }
    }

    // Output error stack, if applicable
    public function trace() {
      if (is_array($this->errStack)) {
        return array_reverse($this->errStack);
      } else {
        return false;
      }
    }

    // Redirect to error page if there were any fatal errors
    // This needs to be called at the end of the script header (i.e.,
    // before output is generated, which is why it can't be in the class
    // destructor). 
    public function complain() {
      $wasFatal = false;

      if ($this->currentPage != $this->errorPage && is_array($this->errStack)) {
        foreach ($this->errStack as $e) $wasFatal = $wasFatal || $e['fatal'];
      }

      if ($wasFatal) {
        $this->set('errStack', serialize($this->errStack));
        @header('Location: '.$this->errorPage);
        exit;
      }
    }
  }
?>
