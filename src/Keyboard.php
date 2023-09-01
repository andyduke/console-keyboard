<?php

namespace AndyDuke\ConsoleKeyboard;

abstract class Keyboard {

  const ESC = 'esc';
  const SPACE = 'space';
  const ENTER = 'enter';
  const UP = 'up';
  const DOWN = 'down';
  const LEFT = 'left';
  const RIGHT = 'right';

  // ???
  //use Utils\EventsTrait;
  
  public static function create($options = []) {
    if (PHP_OS == 'WINNT') {
      return new Win32Keyboard($options['win32'] ?? []);
    } else {
      return new PosixKeyboard($options['posix'] ?? []);
    }
  }

  public function __destruct() {
    $this->dispose();
  }

  abstract public function dispose();
  
  public function read(): ?string {
    $key = $this->readKey();
    return !is_null($key) ? $key->getKey() : null;
  }

  abstract public function readKey(): ?Key;

}
