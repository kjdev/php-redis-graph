RedisGraph PHP Client
=====================

[![Build Status](https://travis-ci.org/kjdev/php-redis-graph.svg?branch=master)](https://travis-ci.org/kjdev/php-redis-graph)

[RedisGraph](https://github.com/RedisGraph/RedisGraph)

Install
-------

``` sh
composer require kjdev/redis-graph
```

As Redis's client library, use either.

- `predis/predis`

    > `composer require predis/predis`

- `ext-redis`

    > `pecl install redis`


Example
-------

``` php
require __DIR__ . '/vendor/autoload.php';

use Redis\Graph;
use Redis\Graph\Node;
use Redis\Graph\Edge;

$redis = new Predis\Client('redis://127.0.0.1:6379/');
// OR
// $redis = new Redis();
// $redis->connect('127.0.0.1', 6379);

$graph = new Graph('social', $redis);

$john = new Node('person', [
  'name' => 'John Doe',
  'age' => 33,
  'gender' => 'male',
  'status' => 'single'
]);
$graph->addNode($john);

$japan = new Node('country', [
  'name' => 'Japan'
]);
$graph->addNode($japan);

$edge = new Edge($john, $japan, 'visited', ['purpose' => 'pleasure']);
$graph->addEdge($edge);

$graph->commit();

$query = 'MATCH (p:person)-[v:visited {purpose:"pleasure"}]->(c:country) RETURN p.name, p.age, v.purpose, c.name';

$result = $graph->query($query);

// Print resultset
$result->prettyPrint();

// Iterate through resultset
while ($row = $result->fetch()) {
  var_dump($row);
}
// var_dump($result->fetchAll());

// All done, remove graph.
$graph->delete();
```
