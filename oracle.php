<?php
  require_once 'error.php';

  class Oracle {
    private $errorStack;
    private $broken = false;

    private $persistent;
    private $connection = false;

    private function nuke($message, $context = null) {
      $status = 'Oracle: '.$message;
      if ($ociErr = oci_error($context)) $status .= '\n'.$ociErr['code'].': '.$ociErr['message'];

      $this->errorStack->add($status, true);
      $this->broken = true;
    }

    function __construct(&$errorStack, $connectionString, $username, $password, $persistent = true) {
      $this->errorStack = $errorStack;
      $this->persistent = $persistent;

      if (empty($connectionString) || empty($username) || empty($password)) {
        $this->nuke('Connection parameters not fully qualified.');
      } else {
        if ($this->persistent) {
          $this->connection = @oci_pconnect($username, $password, $connectionString);
        } else {
          $this->connection = @oci_connect($username, $password, $connectionString);
        }

        if (!$this->connection) {
          $this->nuke('Could not connect to '.$username.'@'.$connectionString);
        }
      }
    }

    function __destruct() {
      if ($this->connection && !$this->persistent) {
        @oci_close($this->connection);
      }
    }

    public function execute($SQL, &$parameters = null, $autoCommit = true) {
      if (!$this->broken && $this->connection) {
        // Parameterised queries must use bind variables; these are
        // passed by reference as a dictionary of variable => value. We
        // use the reference for things like SP OUT parameters, etc.

        // Prepare statement
        if (!($stid = @oci_parse($this->connection, $SQL))) {
          $this->nuke('Could not prepare query.', $this->connection);
          return false;
        } else {
          // Bind any parameters
          $bindSuccess = true;
          if (count($parameters) > 0)
            foreach ($parameters as $key => $val)
              $bindSuccess = $bindSuccess && @oci_bind_by_name($stid, $key, $parameters[$key]);

          if (!$bindSuccess) {
            $this->nuke('Could not bind parameters to query.', $stid);
            return false;
          } else {
            // Execute
            if (!@oci_execute($stid, $autoCommit?OCI_COMMIT_ON_SUCCESS:OCI_DEFAULT)) {
              $this->nuke('Could not execute query.', $stid);
              return false;
            } else {
              // Get result set and return
              $records = array();

              if (@oci_statement_type($stid) == 'SELECT' && @oci_fetch_all($stid, $records, null, null, OCI_FETCHSTATEMENT_BY_ROW) !== false) {
                @oci_free_statement($stid);
                return $records;
              } else {
                @oci_free_statement($stid);
                return true;
              }
            }
          }
        }
      } else {
        $this->nuke('Cannot execute query; invalid state.');
        return false;
      }
    }

    public function commit() {
      if (!$this->broken && $this->connection) {
        return @oci_commit($this->connection);
      } else {
        $this->nuke('Cannot commit transaction; invalid state.');
        return false;
      }  
    }

    public function rollback() {
      if (!$this->broken && $this->connection) {
        return @oci_rollback($this->connection);
      } else {
        $err->add('Cannot rollback transaction; invalid state.');
        return false;
      }  
    }
  }
?>
