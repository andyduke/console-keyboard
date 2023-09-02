<?php

namespace AndyDuke\ConsoleKeyboard;

abstract class Keyboard {

  const ESC = 'esc';
  const SPACE = 'space';
  const ENTER = 'enter';

  const TAB = 'tab';
  const BACKSPACE = 'backspace';

  const INS = 'insert';
  const DEL = 'delete';
  const HOME = 'home';
  const END = 'end';
  const PGUP = 'page-up';
  const PGDOWN = 'page-down';

  const F1 = 'F1';
  const F2 = 'F2';
  const F3 = 'F3';
  const F4 = 'F4';
  const F5 = 'F5';
  const F6 = 'F6';
  const F7 = 'F7';
  const F8 = 'F8';
  const F9 = 'F9';
  const F10 = 'F10';
  const F11 = 'F11';
  const F12 = 'F12';
  const F13 = 'F13';
  const F14 = 'F14';
  const F15 = 'F15';
  const F16 = 'F16';
  const F17 = 'F17';
  const F18 = 'F18';
  const F19 = 'F19';
  const F20 = 'F20';
  const F21 = 'F21';
  const F22 = 'F22';
  const F23 = 'F23';
  const F24 = 'F24';

  const UP = 'up';
  const DOWN = 'down';
  const LEFT = 'left';
  const RIGHT = 'right';

  // ???
  //use Utils\EventsTrait;

  private bool $started = false;
  
  public static function create($options = []) {
    if (PHP_OS == 'WINNT') {
      return new Win32Keyboard($options['win32'] ?? []);
    } else {
      return new PosixKeyboard($options['posix'] ?? []);
    }
  }

  public function __destruct() {
    $this->stop();
  }

  protected function start() {
    if (!$this->started) {
      $this->prepare();
    }
  }
  
  protected function stop() {
    if ($this->started) {
      $this->cleanup();
    }
  }

  abstract protected function prepare();
  
  abstract protected function cleanup();
  
  public function read(): ?string {
    $key = $this->readKey();
    return !is_null($key) ? $key->getKey() : null;
  }

  public function readKey(): ?Key {
    $this->start();
    return $this->readQueue();
  }

  abstract protected function readQueue(): ?Key;

}
