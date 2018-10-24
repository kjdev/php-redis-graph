--TEST--
Test edge traversal
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
  'name' => 'src11',
]);
$dest = new Node('node', [
  'name' => 'dest11',
]);
$edge = new Edge($src, $dest, 'edge');
$graph->addNode($src);
$graph->addNode($dest);
$graph->addEdge($edge);

$src = new Node('node', [
  'name' => 'src12',
]);
$dest = new Node('node', [
  'name' => 'dest12',
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

$graph->commit();

$query = 'MATCH (a)-[:edge]->(b:node) RETURN a, b';
$result = $graph->query($query);
$result->prettyPrint();
while ($row = $result->fetch()) {
  var_dump($row);
}

$query = 'MATCH (s:node)-[:edge]->(d:node) WHERE s.name = "src11" RETURN s,d';
$result = $graph->query($query);
$result->prettyPrint();
while ($row = $result->fetch()) {
  var_dump($row);
}

$query = 'MATCH (s:node)-[:edge]->(d:node) WHERE s.name = "src2" RETURN s,d';
$result = $graph->query($query);
$result->prettyPrint();
while ($row = $result->fetch()) {
  var_dump($row);
}

$query = 'MATCH (s:node)-[:edge]->(d:node_type_2) WHERE s.name = "src2" RETURN s,d';
$result = $graph->query($query);
$result->prettyPrint();
while ($row = $result->fetch()) {
  var_dump($row);
}

$graph->delete();
--EXPECTF--
+--------+--------+
| a.name | b.name |
+--------+--------+
| src12  | dest12 |
| src11  | dest11 |
+--------+--------+
array(2) {
  ["a.name"]=>
  string(5) "src12"
  ["b.name"]=>
  string(6) "dest12"
}
array(2) {
  ["a.name"]=>
  string(5) "src11"
  ["b.name"]=>
  string(6) "dest11"
}
+--------+--------+
| s.name | d.name |
+--------+--------+
| src11  | dest11 |
+--------+--------+
array(2) {
  ["s.name"]=>
  string(5) "src11"
  ["d.name"]=>
  string(6) "dest11"
}
+--------+--------+
| s.name | d.name |
+--------+--------+
+--------+--------+
| s.name | d.name |
+--------+--------+
| src2   | dest2  |
+--------+--------+
array(2) {
  ["s.name"]=>
  string(4) "src2"
  ["d.name"]=>
  string(5) "dest2"
}
