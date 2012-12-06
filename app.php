<?php
  abstract class Plugin {
    private $app = null;
    public function delegate(&$app) { $this->app = &$app; }
  }

  class Application {
    private $currentPage;
    private $errorPage;

    private $httpVerb;
    private $httpParams;

    private $errStack;

    // Application Arguments ///////////////////////////////////////////

    // Get and set session value by key
    public function get($key) { return $_SESSION[$key]; }
    public function set($key, $value) { $_SESSION[$key] = $value; }

    // No parameter => HTTP verb
    // True         => complete parameters
    // Key          => parameter[Key]
    public function arg($key = null) {
      if ($key == null) {
        return $this->httpVerb;
      } else {
        if ($key == true) {
          return $this->httpParams;
        } else if (is_array($this->httpParameters) && array_key_exists($key, $this->httpParams)) {
          return $this->httpParams[$key];
        } else {
          return null;
        }
      }
    }

    // Constructor /////////////////////////////////////////////////////

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

      // Get HTTP verb and query data
      $this->httpVerb = $_SERVER['REQUEST_METHOD'];
      switch ($this->httpVerb) {
        case 'GET':
          $this->httpParams = $_GET;
          break;
        case 'POST':
          $this->httpParams = $_POST;
          break;
        default:
          parse_str(file_get_contents('php://input'), $this->httpParams);
          break;
      }
    }

    // Plugin Interface ////////////////////////////////////////////////

    private $plugins = array();

    public function registerPlugin($name, &$p) {
      if ($p instanceof Plugin && !array_key_exists($name, $this->plugins)) {
        $this->plugins[$name] = &$p;
        $this->plugins[$name]->delegate($this);
      } else {
        if (array_key_exists($name, $this->plugins)) {
          $this->log('\''.$name.'\' plugin has already been registered.');
        } else {
          $this->log('\''.$name.'\' is not a valid plugin.');
        }
      }
    }

    public function plugin($name) {
      if (array_key_exists($name, $this->plugins)) {
        return $this->plugins[$name];
      }
    }

    // Error Logging ///////////////////////////////////////////////////

    // Append to error stack, if we're not on the error page
    public function log($desc, $fatal = true) {
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
