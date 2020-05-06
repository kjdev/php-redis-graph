<?php
namespace Redis\Graph\Query;

use Redis\Graph;
use Redis\Graph\Edge;
use Redis\Graph\Node;

class Result
{
  const COLUMN_UNKNOWN = 0;
  const COLUMN_SCALAR = 1;
  const COLUMN_NODE = 2;
  const COLUMN_RELATION = 3;

  const PROPERTY_UNKNOWN = 0;
  const PROPERTY_NULL = 1;
  const PROPERTY_STRING = 2;
  const PROPERTY_INTEGER = 3;
  const PROPERTY_BOOLEAN = 4;
  const PROPERTY_DOUBLE = 5;

  public $values = [];
  public $stats = [];
  private $count = 0;
  private $headers = [];
  private $graph;

  public function __construct(Graph $graph, $response)
  {
    if (!isset($response) || !is_array($response)) {
      return;
    }

    $this->graph = $graph;

    if (count($response) === 1) {
      $this->parseStatistics($response[0]);
    } else {
      $this->parseResults($response);
      $this->parseStatistics(end($response));
    }
  }

  public function stats(string $key = '')
  {
    if ($key === '') {
      return $this->stats;
    }
    if (array_key_exists($key, $this->stats)) {
      return $this->stats[$key];
    }
    return false;
  }

  public function fetch()
  {
    if (count($this->values) <= $this->count) {
      return false;
    }
    $this->count++;
    return $this->values[($this->count - 1)];
  }

  public function fetchAll(): array
  {
    return $this->values;
  }

  public function prettyPrint()
  {
    if (count($this->values) === 0) {
      return;
    }

    $length = [];
    foreach ($this->values as $value) {
      foreach ($value as $key => $val) {
        if (is_scalar($val)) {
          $length[$key] = max(strlen($key), strlen((string) $val));
        } elseif ($val instanceof Node) {
          $length[$key] = max(
            strlen($key),
            strlen(get_class($val) . "@{$val->label}")
          );
        } elseif ($val instanceof Edge) {
          $length[$key] = max(
            strlen($key),
            strlen(get_class($val) . '@' . (string) $val->relation)
          );
        } elseif (is_object($val)) {
          $length[$key] = max(strlen($key), strlen(get_class($val)));
        } else {
          $length[$key] = strlen($key);
        }
      }
    }

    $line = function () use ($length) {
      foreach ($length as $len) {
        echo '+', str_repeat('-', $len + 2);
      }
      echo '+', PHP_EOL;
    };
    $line();

    foreach ($length as $key => $val) {
      echo '| ', str_pad($key, $val), ' ';
    }
    echo '|', PHP_EOL;
    $line();

    foreach ($this->values as $value) {
      foreach ($value as $key => $val) {
        if (is_scalar($val) || is_null($val)) {
          echo '| ', str_pad((string) $val, $length[$key]), ' ';
        } elseif ($val instanceof Node) {
          echo '| ',
            str_pad(get_class($val) . "@{$val->label}", $length[$key]),
            ' ';
        } elseif ($val instanceof Edge) {
          echo '| ',
            str_pad(
              get_class($val) . '@' . (string) $val->relation,
              $length[$key]
            ),
            ' ';
        } elseif (is_object($val)) {
          echo '| ', str_pad(get_class($val), $length[$key]), ' ';
        } else {
          echo '| ', str_pad('*', $length[$key]), ' ';
        }
      }
      echo '|', PHP_EOL;
    }
    $line();
  }

  private function parseStatistics(array $response)
  {
    foreach ($response as $line) {
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

  private function parseResults(array $response)
  {
    $this->headers = $this->parseHeader($response);

    // Empty header.
    if (count($this->headers) === 0) {
      return;
    }

    $keys = [];
    foreach ($this->headers as $value) {
      $keys[] = $value[1] ?? '';
    }

    $records = $this->parseRecords($response);

    foreach ($records as $record) {
      $this->values[] = array_combine($keys, $record);
    }
  }

  private function parseHeader(array $response): array
  {
    // An array of column name/column type pairs.
    return $response[0];
  }

  private function parseRecords(array $response): array
  {
    $records = [];

    foreach ($response[1] as $row) {
      $record = [];
      foreach ($row as $i => $var) {
        switch ($this->headers[$i][0] ?? self::COLUMN_UNKNOWN) {
          case self::COLUMN_SCALAR:
            $record[] = $this->parseScalar($var);
            break;
          case self::COLUMN_NODE:
            $record[] = $this->parseNode($var);
            break;
          case self::COLUMN_RELATION:
            $record[] = $this->parseEdge($var);
            break;
          default:
            trigger_error('Unknown column type.', E_USER_WARNING);
        }
      }
      $records[] = $record;
    }

    return $records;
  }

  private function parseScalar(array $var)
  {
    $type = (int) $var[0];
    $value = $var[1];

    switch ($type) {
      case self::PROPERTY_NULL:
        $scalar = null;
        break;
      case self::PROPERTY_STRING:
        $scalar = (string) $value;
        break;
      case self::PROPERTY_INTEGER:
        $scalar = (int) $value;
        break;
      case self::PROPERTY_BOOLEAN:
        $scalar = (bool) $value;
        break;
      case self::PROPERTY_DOUBLE:
        $scalar = (float) $value;
        break;
      case self::PROPERTY_UNKNOWN:
        trigger_error('Unknown scalar type.', E_USER_WARNING);
    }

    return $scalar ?? null;
  }

  private function parseNode(array $var): Node
  {
    // Node ID (integer),
    // [label string offset (integer)],
    // [[name, value type, value] X N]

    $id = (int) $var[0];

    $label = '';
    if (count($var[1]) !== 0) {
      $label = $this->graph->getLabel($var[1][0]);
    }

    $properties = $this->parseEntityProperties($var[2]);

    return new Node(":{$label}", $properties);
  }

  private function parseEntityProperties(array $props): array
  {
    // [[name, value type, value] X N]

    $properties = [];

    foreach ($props as $prop) {
      $name = $this->graph->getProperty($prop[0]);
      $value = $this->parseScalar(array_slice($prop, 1));
      $properties[$name] = $value;
    }

    return $properties;
  }

  private function parseEdge(array $var): Edge
  {
    // Edge ID (integer),
    // reltype string offset (integer),
    // src node ID offset (integer),
    // dest node ID offset (integer),
    // [[name, value, value type] X N]

    $id = (int) $var[0];
    $relation = $this->graph->getRelation($var[1]);
    $src = $this->graph->getNode($var[2]);
    $dest = $this->graph->getNode($var[3]);
    $properties = $this->parseEntityProperties($var[4]);

    return new Edge($src, $dest, $relation, $properties);
  }
}
