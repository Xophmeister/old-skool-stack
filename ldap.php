<?php
  class LDAP {
    private $session = null;
    private $broken = false;

    private $connection = false;
    private $dn;

    function __construct($hostname, $dn, &$session = null) {
      if (isset($session)) $this->session = &$session;

      $match = array()
      if (preg_match('/^(?<hostname>[^:]+)(:(?<port>\d+)$)?/', $hostname, $match) && isset($dn)) {
        $host = $match['hostname'];
        $port = isset($match['port']) ? $match['port'] : 389; 
      
        if (!($this->connection = @ldap_connect($host, $port))) {
          $this->nuke('Cannot connect to '.$hostname);
        } else {
          $this->dn = $dn;
        }
      } else {
        $this->nuke('Connection parameters not fully qualified.');
      }
    }

    function __destruct() {
      if ($this->connection) {
        @ldap_close($this->connection);
      }
    }

    private function nuke($message) {
      $this->broken = true;
      if (isset($this->session)) $this->session->log('LDAP: '.$message, true);
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
