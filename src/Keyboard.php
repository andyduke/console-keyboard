<?php

namespace AndyDuke\ConsoleKeyboard;

/**
 * Class for cross-platform reading of keystrokes in the terminal
 *
 * @link https://github.com/andyduke/console-keyboard/blob/master/README.md Documentation
 * @author Andy Chentsov <chentsov@gmail.com>
 */
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

  private bool $started = false;

  /**
   * Creates an instance of the `Keyboard` class
   * for the current platform.
   */
  public static function create($options = []) {
    if (PHP_OS == 'WINNT') {
      return new Win32Keyboard($options['win32'] ?? []);
    } else {
      return new AnsiKeyboard($options['posix'] ?? []);
    }
  }

  public function __destruct() {
    $this->stop();
  }

  /**
   * Returns the status - whether the class is in a reading loop
   *
   * @return bool
   */
  public function isStarted() {
    return $this->started;
  }

  /**
   * Initializes reading keystrokes.
   *
   * Usually not directly used, because `read()` and `readKey()`
   * call this method automatically.
   */
  public function start() {
    if (!$this->started) {
      $this->started = true;
      $this->prepare();
    }
  }

  /**
   * Stops the keystroke reading loop.
   *
   * You can use it, for example, in the Ctrl-C signal handler.
   */
  public function stop() {
    if ($this->started) {
      $this->started = false;
      $this->cleanup();
    }
  }

  abstract protected function prepare();

  abstract protected function cleanup();

  /**
   * Returns a Generator that reads a keypress and
   * returns the name of the key pressed
   *
   * @return Generator<string|null>
   */
  public function read(): \Generator {
    foreach($this->readKey() as $key) {
      yield !is_null($key) ? $key->getKey() : null;
    }
  }

  /**
   * Returns a Generator that reads a keypress and
   * returns a `Key` class with the name and
   * code of the key pressed
   *
   * @return Generator<Key|null>
   */
  public function readKey(): \Generator {
    $this->start();
    try {
      do {
        $key = $this->readQueue();
        yield $key;
      } while ($this->started);
    } finally {
      $this->stop();
    }
  }

  abstract protected function readQueue(): ?Key;

}
