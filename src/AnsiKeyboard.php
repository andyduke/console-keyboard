<?php

namespace AndyDuke\ConsoleKeyboard;

/**
 * Implementation of a class for reading keystrokes in the terminal for Linux/MacOS
 */
class AnsiKeyboard extends Keyboard {

  /**
   * Plain text char map
   */
  private $textMap = [
    "\n"  => self::ENTER,
    ' '   => self::SPACE,
    "\t"  => self::TAB,
  ];

  /**
   * Ascii char map
   */
  private $asciiMap = [
    127 => self::BACKSPACE, // \u0008 doesn't work
  ];

  /**
   * Unicode char map
   */
  private $unicodeMap = [
    // main keys
    '\u001b' => self::ESC,

    // arrow keys
    '\u001b[A' => self::UP,
    '\u001b[B' => self::DOWN,
    '\u001b[D' => self::LEFT,
    '\u001b[C' => self::RIGHT,

    // function keys
    '\u001b[1P'  => self::F1,
    '\u001b[1Q'  => self::F2,
    '\u001b[1R'  => self::F3,
    '\u001b[1S'  => self::F4,
    '\u001b[11~' => self::F1,
    '\u001b[12~' => self::F2,
    '\u001b[13~' => self::F3,
    '\u001b[14~' => self::F4,
    '\u001b[15~' => self::F5,
    '\u001b[17~' => self::F6,
    '\u001b[18~' => self::F7,
    '\u001b[19~' => self::F8,
    '\u001b[20~' => self::F9,
    '\u001b[21~' => self::F10,
    '\u001b[23~' => self::F11,
    '\u001b[24~' => self::F12,
    '\u001b[25~' => self::F13,
    '\u001b[26~' => self::F14,
    '\u001b[28~' => self::F15,
    '\u001b[29~' => self::F16,
    '\u001b[31~' => self::F17,
    '\u001b[32~' => self::F18,
    '\u001b[33~' => self::F19,
    '\u001b[34~' => self::F20,

    // other keys
    '\u001b[2~' => self::INS,
    '\u001b[3~' => self::DEL,
    '\u001b[1~' => self::HOME,
    '\u001b[7~' => self::HOME,
    '\u001b[H'  => self::HOME,
    '\u001b[4~' => self::END,
    '\u001b[8~' => self::END,
    '\u001b[F'  => self::END,
    '\u001b[5~' => self::PGUP,
    '\u001b[6~' => self::PGDOWN,
  ];

  protected ?string $initialTtyMode;

  public function __construct($options = []) {

  }

  protected function prepare() {
    $this->initialTtyMode = (shell_exec('stty -g') ?: null);
    shell_exec('stty cbreak -echo');
  }

  protected function cleanup() {
    if ($this->initialTtyMode) {
      shell_exec("stty {$this->initialTtyMode}");

      $this->initialTtyMode = null;
    }
  }

  protected function readQueue(): ?Key {
    $this->start();

    $input = fread(STDIN, 1024);

    $key = $this->translateInput($input);

    return new Key($key, $input);
  }

  private function translateInput(?string $input): ?string {
    if ($input == null) return null;

    // try matching the key with plain text
    foreach($this->textMap as $code => $key) {
      if ($code === $input) {
        return $key;
      }
    }

    // try matching the key with ascii
    foreach($this->asciiMap as $code => $key) {
      if (chr($code) === $input) {
        return $key;
      }
    }

    // try matching the key with unicode
    foreach($this->unicodeMap as $code => $key) {
      if ($this->unicodeToString($code) === $input) {
        return $key;
      }
    }

    return $input;
  }

  private function unicodeToString($code) {
    return json_decode('"' . $code . '"');
  }

}
