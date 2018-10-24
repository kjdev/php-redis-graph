--TEST--
Test connection
--SKIPIF--
--FILE--
<?php
require __DIR__ . '/../vendor/autoload.php';

use Redis\Graph;

$redis = new \Predis\Client('redis://127.0.0.1:6379/');

$graph = new Graph('test', $redis);
echo get_class($graph), PHP_EOL;
--EXPECT--
Redis\Graph
