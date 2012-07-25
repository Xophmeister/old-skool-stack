<?php
  require_once "error.php";

  class Oracle {
    private $connection;
    private $connected;

    function __construct($connectionString, $username, $password) {
      global $err;

      if (!($this->connection = @oci_pconnect($username, $password, $connectionString))) {
        $err->add('Cannot connect to '.$username.'@'.$connectionString);
        $this->connected = false;
      } else {
        $this->connected = true;
      }
    }

    function __destruct() {
      if ($this->connected) {
        oci_close($this->connection);
      }
    }

    public function execute($SQL, $parameters = null, $autoCommit = true) {
      global $err;

      if ($this->connected) {
        // Parameterised queries must use bind variables; these are
        // passed as a dictionary of variable => value (n.b., no
        // reference semantics, so we can't get OUT data from stored
        // procedures, etc.)

        // Prepare statement
        if (!($stid = @oci_parse($this->connection, $SQL))) {
          $err->add('Could not prepare query.');
          return false;
        } else {
          // Bind any parameters
          $bindSuccess = true;
          if (count($parameters) > 0)
            foreach ($parameters as $key => $val)
              $bindSuccess = $bindSuccess && @oci_bind_by_name($stid, $key, $parameters[$key]);

          if (!$bindSuccess) {
            $err->add('Could not bind parameters to query.');
            return false;
          } else {
            // Execute
            if (!@oci_execute($stid, $autoCommit?OCI_COMMIT_ON_SUCCESS:OCI_NO_AUTO_COMMIT)) {
              $err->add('Could not execute query.');
              return false;
            } else {
              // Get result set and return
              $records = array();

              if (oci_statement_type($stid) == "SELECT" && oci_fetch_all($stid, $records, null, null, OCI_FETCHSTATEMENT_BY_ROW) !== false) {
                oci_free_statement($stid);
                return $records;
              } else {
                oci_free_statement($stid);
                return true;
              }
            }
          }
        }
      } else {
        $err->add('Cannot execute query; not connected.');
        return false;
      }
    }

    public function commit() {
      global $err;

      if ($this->connected) {
        return @oci_commit($this->connection);
      } else {
        $err->add('Cannot commit transaction; not connected.');
        return false;
      }      
    }
  }
?>