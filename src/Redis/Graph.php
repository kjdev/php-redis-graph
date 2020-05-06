<?php
namespace Redis;

use Redis\Graph\Edge;
use Redis\Graph\Node;
use Redis\Graph\Query\Result;
use RuntimeException;

class Graph
{
  const CLIENT_REDIS = 'Redis';
  const CLIENT_PREDIS = 'Predis\\Client';

  private $redis;
  private $client;
  private $labels = [];
  private $properties = [];
  private $relations = [];

  public $name;
  public $nodes = [];
  public $edges = [];

  public function __construct($name, $redis)
  {
    if (!is_object($redis)) {
      throw new RuntimeException('Redis client object not found.');
    }

    $this->client = get_class($redis);
    if (!in_array($this->client, [self::CLIENT_REDIS, self::CLIENT_PREDIS], true)) {
      throw new RuntimeException('Unsupported Redis client.');
    }

    $this->name = $name;
    $this->redis = $redis;

    $response = $this->redisCommand('MODULE', 'LIST');
    if (!isset($response[0]) || !is_array($response[0])
        || !in_array('graph', $response[0], true)) {
      throw new RuntimeException('RedisGraph module not loaded.');
    }
  }

  public function addNode(Node $node): self
  {
    $this->nodes[] = $node;
    return $this;
  }

  public function getNode(int $var): Node
  {
    if (isset($this->nodes[$var])) {
      return $this->nodes[$var];
    }

    $label = $this->getLabel($var);
    return new Node(":{$label}");
  }

  public function addEdge(Edge $edge): self
  {
    assert(in_array($edge->src, $this->nodes, true));
    assert(in_array($edge->dest, $this->nodes, true));
    $this->edges[] = $edge;
    return $this;
  }

  public function commit(): Result
  {
    $query = 'CREATE ';
    foreach ($this->nodes as $node) {
      $query .= (string)$node . ',';
    }
    foreach ($this->edges as $edge) {
      $query .= (string)$edge . ',';
    }

    // Discard leading comma.
    $query = rtrim($query, ',');

    return $this->query($query);
  }

  public function query($command): Result
  {
    $response = $this->redisCommand(
      'GRAPH.QUERY',
      $this->name,
      $command,
      '--compact'
    );
    return new Result($this, $response);
  }

  public function explain($query): string
  {
    return implode(
      PHP_EOL,
      $this->redisCommand('GRAPH.EXPLAIN', $this->name, $query)
    );
  }

  public function delete()
  {
    return $this->redisCommand('GRAPH.DELETE', $this->name);
  }

  private function redisCommand()
  {
    switch ($this->client) {
      case self::CLIENT_REDIS:
        return call_user_func_array(
          [$this->redis, 'rawCommand'],
          func_get_args()
        );
      case self::CLIENT_PREDIS:
        return $this->redis->executeRaw(func_get_args());
      default:
        throw new RuntimeException('Unknown Redis client.');
    }
  }

  private function call(string $procedure): array
  {
    $response = [];

    $result = $this->query("CALL {$procedure}");
    foreach ($result->values as $var) {
      $response[] = current($var);
    }

    return $response;
  }

  public function getLabel(int $var): string
  {
    if (count($this->labels) === 0) {
      $this->labels = $this->call('db.labels()');
    }
    return $this->labels[$var] ?? '';
  }

  public function getProperty(int $var): string
  {
    if (count($this->properties) === 0) {
      $this->properties = $this->call('db.propertyKeys()');
    }
    return $this->properties[$var] ?? '';
  }

  public function getRelation(int $var): string
  {
    if (count($this->relations) === 0) {
      $this->relations = $this->call('db.relationshipTypes()');
    }
    return $this->relations[$var] ?? '';
  }
}
