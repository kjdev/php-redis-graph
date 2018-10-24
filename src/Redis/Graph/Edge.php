<?php
namespace Redis\Graph;

class Edge
{
  public $src = null;
  public $dest = null;
  public $relation = null;
  public $properties = [];

  public function __construct(
    Node $src,
    Node $dest,
    $relation = null,
    array $properties = []
  ) {
    $this->src = $src;
    $this->dest = $dest;
    $this->relation = $relation;
    $this->properties = $properties;
  }

  public function __toString()
  {
    // Source node.
    $res = '(' . $this->src->alias . ')';

    // Edge
    $res .= '-[';
    if ($this->relation) {
      $res .= ':' . $this->relation;
    }
    if ($this->properties) {
      $props = [];
      foreach ($this->properties as $key => $val) {
        if (is_int($val) || is_double($val)) {
          $props[] = $key . ':' . $val;
        } else {
          $props[] = $key . ':"' . trim((string)$val, '"') . '"';
        }
      }
      $res .= '{' . implode(',', $props) . '}';
    }
    $res .= ']->';

    // Dest node.
    $res .= '(' . $this->dest->alias . ')';

    return $res;
  }
}
