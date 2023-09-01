<?php

namespace AndyDuke\ConsoleKeyboard;

class Key {

  protected string $key;

  protected $rawKey;

  public function __construct(string $key, $rawKey) {
    $this->key = $key;
    $this->rawKey = $rawKey;
  }

  public function getKey(): string {
    return $this->key;
  }

  public function getRawKey() {
    return $this->rawKey;
  }

  public function __toString(): string {
    return $this->key;
  }

}