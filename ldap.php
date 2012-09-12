<?php
  require_once 'error.php';

  class LDAP {
    private $errorStack;
    private $broken = false;

    private $connection = false;
    private $dn;

    private function nuke($message) {
      $this->errorStack->add('LDAP: '.$message, true);
      $this->broken = true;
    }

    function __construct(&$errorStack, $hostname, $dn, $port = 389) {
      $this->errorStack = $errorStack;

      if (empty($hostname) || empty($dn) || empty($port)) {
        $this->nuke('Connection parameters not fully qualified.');
      } else {
        if (!($this->connection = @ldap_connect($hostname, $port))) {
          $this->nuke('Cannot connect to '.$hostname.':'.$port);
        } else {
          $this->dn = $dn;
        }
      }
    }

    function __destruct() {
      if ($this->connection) {
        @ldap_close($this->connection);
      }
    }

    public function get($filter) {
      if (!$this->broken && $this->connection) {
        if (!($searchLDAP = @ldap_search($this->connection, $this->dn, $filter))) {
          $this->nuke('Error searching directory.');
          return false;
        } else {
          $results = @ldap_get_entries($this->connection, $searchLDAP);
          if ($results['count'] == 0) {
            $this->nuke('Directory name \''.$filter.'\' not found.');
            return false;
          } else {
            return $results;
          }
        }
      } else {
        $this->nuke('Cannot get directory data; invalid state.');
        return false;
      }
    }

    public function bind($user, $password) {
      if (!$this->broken && $this->connection) {
        if (!($info = $this->get('cn='.$user))) {
          $this->nuke('User not found.');
          return false;
        } else {
          if ($info['count'] == 1) {
            if (@ldap_bind($this->connection, $info[0]['dn'], $password)) {
              return true;
            } else {
              $this->nuke('Could not authenticate \''.$user.'\': Invalid password?');
              return false;
            }
          } else {
            $this->nuke('Multiple users found.');
            return false;
          }
        }
      } else {
        $this->nuke('Cannot bind to directory; invalid state.');
        return false;
      }
    }
  }
?>
