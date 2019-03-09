<?php
namespace Redis\Graph\Query;

class Result
{
  public $keys = [];
  public $values = [];
  public $stats = [];
  private $count = 0;

  public function __construct($response)
  {
    $this->parseResult($response);
    $this->parseStats($response);
  }

  public function stats($key = '')
  {
    if (array_key_exists($key, $this->stats)) {
      return $this->stats[$key];
    }
    return $this->stats;
  }

  public function fetch()
  {
    if (count($this->values) <= $this->count) {
      return false;
    }
    $this->count++;
    return $this->values[($this->count - 1)];
  }

  public function fetchAll()
  {
    return $this->values;
  }

  public function prettyPrint()
  {
    if (empty($this->keys)) {
      return;
    }

    $length = [];
    if (!empty($this->values)) {
      foreach ($this->values as $value) {
        foreach (array_map('strlen', $value) as $key => $len) {
          if (!isset($length[$key]) || $length[$key] < $len) {
            $length[$key] = $len;
          }
        }
      }
    } else {
      foreach ($this->keys as $key) {
        $length[$key] = strlen($key);
      }
    }

    foreach ($length as $key => $len) {
      $length[$key] = max(strlen($key), $len);
    }

    $line = function () use ($length) {
      foreach ($length as $len) {
        echo '+', str_repeat('-', $len + 2);
      }
      echo '+', PHP_EOL;
    };
    $line();

    foreach ($this->keys as $val) {
      echo '| ', str_pad((string)$val, $length[$val]), ' ';
    }
    echo '|', PHP_EOL;
    $line();

    if (!empty($this->values)) {
      foreach ($this->values as $value) {
        foreach ($value as $key => $val) {
          echo '| ', str_pad((string)$val, $length[$key]), ' ';
        }
        echo '|', PHP_EOL;
      }
      $line();
    }
  }

  private function parseResult($response)
  {
    if (!isset($response[0]) || !is_array($response[0])) {
      return;
    }
    $this->keys = array_shift($response[0]);
    foreach ($response[0] as $val) {
      $this->values[] = array_combine($this->keys, $val);
    }
  }

  private function parseStats($response)
  {
    if (!isset($response[1]) || !is_array($response[1])) {
      return;
    }

    foreach ($response[1] as $line) {
      if (preg_match('/^Labels added:(.*)$/', $line, $matches)) {
        $this->stats['labels_added'] = (int)(trim($matches[1]));
      } elseif (preg_match('/^Nodes created:(.*)$/', $line, $matches)) {
        $this->stats['nodes_created'] = (int)(trim($matches[1]));
      } elseif (preg_match('/^Nodes deleted:(.*)$/', $line, $matches)) {
        $this->stats['nodes_deleted'] = (int)(trim($matches[1]));
      } elseif (preg_match('/^Relationships deleted:(.*)$/', $line, $matches)) {
        $this->stats['relationships_deleted'] = (int)(trim($matches[1]));
      } elseif (preg_match('/^Properties set:(.*)$/', $line, $matches)) {
        $this->stats['properties_set'] = (int)(trim($matches[1]));
      } elseif (preg_match('/^Relationships created:(.*)$/', $line, $matches)) {
        $this->stats['relationships_created'] = (int)(trim($matches[1]));
      } elseif (preg_match(
        '/^Query internal execution time: *([0-9\.]*) .*$/', $line, $matches)
      ) {
        $this->stats['internal_execution_time'] = (double)(trim($matches[1]));
      }
    }
  }
}
