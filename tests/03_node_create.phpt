--TEST--
Test node create
--SKIPIF--
--FILE--
<?php
require __DIR__ . '/../vendor/autoload.php';

use Redis\Graph;
use Redis\Graph\Node;

$redis = new \Predis\Client('redis://127.0.0.1:6379/');

$graph = new Graph('test', $redis);

$node = new Node('node', [
  'name' => 'src',
]);
echo $node, PHP_EOL;

$graph->addNode($node);

$query = 'CREATE ';
foreach ($graph->nodes as $node) {
  $query .= (string)$node . ',';
}
$query = rtrim($query, ',');
echo $query, PHP_EOL;

$result = $graph->commit();
echo get_class($result), PHP_EOL;

var_dump($result->stats());

echo $graph->explain($query);

$graph->delete();
--EXPECTF--
(%s:node{name:"src"})
CREATE (%s:node{name:"src"})
Redis\Graph\Query\Result
array(4) {
  ["labels_added"]=>
  int(1)
  ["nodes_created"]=>
  int(1)
  ["properties_set"]=>
  int(1)
  ["internal_execution_time"]=>
  float(%f)
}
Create
