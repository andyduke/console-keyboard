<?php

namespace AndyDuke\ConsoleKeyboard;

/**
 * Implementation of a class for reading keystrokes in the terminal for Linux/MacOS
 */
class AnsiKeyboard extends Keyboard {

  private const CTRL_C = 'ctrl-c';

  /**
   * Char map
   */
  private $keyMap = [
    // plain text sequences
    "\n"  => self::ENTER,
    ' '   => self::SPACE,
    "\t"  => self::TAB,

    // special keys
    "\003" => self::CTRL_C,
    "\x7F" => self::BACKSPACE,

    // main keys
    "\u{001b}" => self::ESC,

    // arrow keys
    "\u{001b}[A" => self::UP,
    "\u{001b}[B" => self::DOWN,
    "\u{001b}[D" => self::LEFT,
    "\u{001b}[C" => self::RIGHT,

    // function keys
    "\u{001b}[1P"  => self::F1,
    "\u{001b}[1Q"  => self::F2,
    "\u{001b}[1R"  => self::F3,
    "\u{001b}[1S"  => self::F4,
    "\u{001b}[11~" => self::F1,
    "\u{001b}[12~" => self::F2,
    "\u{001b}[13~" => self::F3,
    "\u{001b}[14~" => self::F4,
    "\u{001b}[15~" => self::F5,
    "\u{001b}[17~" => self::F6,
    "\u{001b}[18~" => self::F7,
    "\u{001b}[19~" => self::F8,
    "\u{001b}[20~" => self::F9,
    "\u{001b}[21~" => self::F10,
    "\u{001b}[23~" => self::F11,
    "\u{001b}[24~" => self::F12,
    "\u{001b}[25~" => self::F13,
    "\u{001b}[26~" => self::F14,
    "\u{001b}[28~" => self::F15,
    "\u{001b}[29~" => self::F16,
    "\u{001b}[31~" => self::F17,
    "\u{001b}[32~" => self::F18,
    "\u{001b}[33~" => self::F19,
    "\u{001b}[34~" => self::F20,

    // other keys
    "\u{001b}[2~" => self::INS,
    "\u{001b}[3~" => self::DEL,
    "\u{001b}[1~" => self::HOME,
    "\u{001b}[7~" => self::HOME,
    "\u{001b}[H"  => self::HOME,
    "\u{001b}[4~" => self::END,
    "\u{001b}[8~" => self::END,
    "\u{001b}[F"  => self::END,
    "\u{001b}[5~" => self::PGUP,
    "\u{001b}[6~" => self::PGDOWN,
  ];

  private $queue;

  protected ?string $initialTtyMode;

  protected bool $handleCtrlC = true;

  public function __construct($options = []) {
    foreach($options as $key => $value) {
      if (property_exists($this, $key)) {
        $this->{$key} = $value;
      }
    }
  }

  protected function prepare() {
    $this->initialTtyMode = (shell_exec('stty -g') ?: null);
    shell_exec('stty cbreak -echo' . ($this->handleCtrlC ? ' -isig' : ''));
  }

  protected function cleanup() {
    if ($this->initialTtyMode) {
      shell_exec("stty {$this->initialTtyMode}");

      $this->initialTtyMode = null;
    }

    $this->queue = null;
  }

  protected function readQueue(): ?Key {
    $this->start();

    if (is_null($this->queue) || !$this->queue->valid()) {
      $this->queue = $this->inputQueue();
    }

    $key = !empty($this->queue) ? $this->queue->current() : null;
    if (!empty($this->queue)) $this->queue->next();

    if ($key === null) {
      $this->queue = null;
    }

    if ($this->handleCtrlC && $key == self::CTRL_C) {
      $this->stop();
      return null;
    }

    return $key;
  }

  private function inputQueue(): \Generator {
    $input = fread(STDIN, 1024);

    // Handle Ansi code
    $keyCode = $this->translateInput($input);

    // Handle string
    if (is_null($keyCode)) {
      $cnt = \mb_strlen($input);
      for ($i = 0; $i < $cnt; $i++) {
        $rawKey = \mb_substr($input, $i, 1);

        $keyCode = $this->translateInput($rawKey);
        if (is_null($keyCode)) {
          $keyCode = $rawKey;
        }

        $key = new Key($keyCode, $rawKey);
        yield $key;
      }
    } else {
      $key = new Key($keyCode, $input);
      yield $key;
    }
  }

  private function translateInput(?string $input): ?string {
    if ($input == null) return null;

    // try matching the key with table
    if (isset($this->keyMap[$input])) {
      return $this->keyMap[$input];
    }

    return null;
  }

}
