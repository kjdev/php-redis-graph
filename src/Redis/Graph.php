<?php
namespace Redis;

class Graph
{
  const CLIENT_REDIS = 'Redis';
  const CLIENT_PREDIS = 'Predis\\Client';

  private $redis;
  private $client;
  public $name;
  public $nodes = [];
  public $edges = [];

  public function __construct($name, $redis)
  {
    if (!is_object($redis)) {
      throw new \RuntimeException('Redis client object not found.');
    }

    $this->client = get_class($redis);
    if (!in_array($this->client, [self::CLIENT_REDIS, self::CLIENT_PREDIS], true)) {
      throw new \RuntimeException('Unsupported Redis client.');
    }

    $this->name = $name;
    $this->redis = $redis;

    $response = $this->redisCommand('MODULE', 'LIST');
    if (!isset($response[0]) || !is_array($response[0])
        || !in_array('graph', $response[0], true)) {
      throw new \RuntimeException('RedisGraph module not loaded.');
    }
  }

  public function addNode(Graph\Node $node)
  {
    $this->nodes[$node->alias] = $node;
    return $this;
  }

  public function addEdge(Graph\Edge $edge)
  {
    assert(isset($this->nodes[$edge->src->alias]));
    assert(isset($this->nodes[$edge->dest->alias]));
    $this->edges[] = $edge;
    return $this;
  }

  public function commit()
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

  public function query($command)
  {
    $response = $this->redisCommand('GRAPH.QUERY', $this->name, $command);
    return new Graph\Query\Result($response);
  }

  public function explain($query)
  {
    return $this->redisCommand('GRAPH.EXPLAIN', $this->name, $query);
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
        throw new \RuntimeException('Unknown Redis client.');
    }
  }
}
