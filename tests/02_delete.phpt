--TEST--
Test delete
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
$graph->addNode($node);
$result = $graph->commit();

echo $graph->delete(), PHP_EOL;
--EXPECTF--
Graph removed, internal execution time: %f milliseconds
