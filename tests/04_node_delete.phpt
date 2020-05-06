--TEST--
Test node delete
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
$graph->commit();

$query = "MATCH (t:node) WHERE t.name = 'src' RETURN t";
$result = $graph->query($query);
$result->prettyPrint();
var_dump($result->fetchAll());

// DELETE
echo 'DELETE', PHP_EOL;
$query = "MATCH (t:node) WHERE t.name = 'src' DELETE t";
$result = $graph->query($query);
$result->prettyPrint();
var_dump($result->fetchAll());
var_dump($result->stats());

$query = "MATCH (t:node) WHERE t.name = 'src' RETURN t";
$result = $graph->query($query);
$result->prettyPrint();
var_dump($result->fetchAll());

$graph->delete();
--EXPECTF--
+-----------------------+
| t                     |
+-----------------------+
| Redis\Graph\Node@node |
+-----------------------+
array(1) {
  [0]=>
  array(1) {
    ["t"]=>
    object(Redis\Graph\Node)#%d (3) {
      ["label"]=>
      string(4) "node"
      ["alias"]=>
      string(0) ""
      ["properties"]=>
      array(1) {
        ["name"]=>
        string(3) "src"
      }
    }
  }
}
DELETE
array(0) {
}
array(2) {
  ["nodes_deleted"]=>
  int(1)
  ["internal_execution_time"]=>
  float(%f)
}
array(0) {
}
