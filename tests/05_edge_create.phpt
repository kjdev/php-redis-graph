--TEST--
Test edge create
--SKIPIF--
--FILE--
<?php
require __DIR__ . '/../vendor/autoload.php';

use Redis\Graph;
use Redis\Graph\Node;
use Redis\Graph\Edge;

$redis = new \Predis\Client('redis://127.0.0.1:6379/');

$graph = new Graph('test', $redis);

$src = new Node('node', [
  'name' => 'src1',
]);
$dest = new Node('node', [
  'name' => 'dest1',
]);
$edge = new Edge($src, $dest, 'edge');
$graph->addNode($src);
$graph->addNode($dest);
$graph->addEdge($edge);

$src = new Node('node', [
  'name' => 'src2',
]);
$dest = new Node('node_type_2', [
  'name' => 'dest2',
]);
$edge = new Edge($src, $dest, 'edge');
$graph->addNode($src);
$graph->addNode($dest);
$graph->addEdge($edge);

$result = $graph->commit();

var_dump($result->stats());

$graph->delete();
--EXPECTF--
array(5) {
  ["labels_added"]=>
  int(2)
  ["nodes_created"]=>
  int(4)
  ["properties_set"]=>
  int(4)
  ["relationships_created"]=>
  int(2)
  ["internal_execution_time"]=>
  float(%f)
}
