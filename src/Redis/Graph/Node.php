<?php
namespace Redis\Graph;

class Node
{
  public $label = '';
  public $alias = '';
  public $properties = [];

  public function __construct($name, array $properties = [])
  {
    $names = explode(':', $name);
    if (isset($names[1])) {
      $this->alias = $names[0];
      $this->label = $names[1];
    } else {
      $this->alias = $this->randomString();
      $this->label = $names[0];
    }

    $this->properties = $properties;
  }

  private function randomString($length = 10)
  {
    return substr(
      str_shuffle(str_repeat(
        'abcdefghijklmnopqrstuvwxyz', $length
      )),
      0,
      $length
    );
  }

  public function __toString()
  {
    $res = '(';
    if ($this->alias) {
      $res .= $this->alias;
    }
    if ($this->label) {
      $res .= ':' . $this->label;
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
    $res .= ')';

    return $res;
  }
}
