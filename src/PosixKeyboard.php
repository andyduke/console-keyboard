<?php

namespace AndyDuke\ConsoleKeyboard;

class PosixKeyboard extends Keyboard {

  /*
  private const ESC_CODE       = "\e";
  private const SPACE_CODE     = " ";
  private const RETURN_CODE    = "\n";
  private const UP_CODE        = "\033[A";
  private const DOWN_CODE      = "\033[B";
  private const LEFT_CODE      = "\033[D";
  private const RIGHT_CODE     = "\033[C";

  private array $keymap = [
    self::ESC_CODE           => self::ESC,
    self::SPACE_CODE         => self::SPACE,
    self::ENTER_CODE         => self::ENTER,
    self::UP_CODE            => self::UP,
    self::DOWN_CODE          => self::DOWN,
    self::LEFT_CODE          => self::LEFT,
    self::RIGHT_CODE         => self::RIGHT,
  ];
  */

  /**
   * Plain text char map
   */
  private $textMap = [
    self::ENTER => "\n",
    self::SPACE => ' ',
    self::TAB   => "\t"
  ];

  /**
   * Ascii char map
   */
  private $asciiMap = [
    self::BACKSPACE => 127 //\u0008 doesn't work
  ];

  /**
   * Unicode char map
   */
  private $unicodeMap = [
    // main keys
    self::ESC   => '\u001b',

    // arrow keys
    self::UP       => '\u001b[A',
    self::DOWN     => '\u001b[B',
    self::LEFT     => '\u001b[D',
    self::RIGHT    => '\u001b[C',

    // function keys
    'f1'       => '\u001bOP',
    'f2'       => '\u001bOQ',
    'f3'       => '\u001bOR',
    'f4'       => '\u001bOS',
    'f5'       => '\u001b[15~',
    'f6'       => '\u001b[17~',
    'f7'       => '\u001b[18~',
    'f8'       => '\u001b[19~',
    'f9'       => '\u001b[20~',
    'f10'      => '\u001b[21~',
    'f11'      => '\u001b[23~',
    'f12'      => '\u001b[24~',
    'f13'      => '\u001b[25~',
    'f14'      => '\u001b[26~',
    'f15'      => '\u001b[28~',
    'f16'      => '\u001b[29~',
    'f17'      => '\u001b[31~',
    'f18'      => '\u001b[32~',
    'f19'      => '\u001b[33~',
    'ff20'     => '\u001b[34~',

    // other keys
    self::INS     => '\u001b[2~',
    self::DEL     => '\u001b[3~',
    self::HOME    => '\u001b[1~',
    self::END     => '\u001b[4~',
    self::PGUP    => '\u001b[5~',
    self::PGDOWN  => '\u001b[6~',
  ];

  protected ?string $initialTtyMode;

  protected bool $started = false;

  public function __construct($options = []) {

  }

  public function start() {
    if (!$this->started) {
      $this->initialTtyMode ??= (shell_exec('stty -g') ?: null);
      shell_exec('stty cbreak -echo');

      $this->started = true;
    }
  }

  public function stop() {
    if ($this->started) {
      if ($this->initialTtyMode) {
        shell_exec("stty {$this->initialTtyMode}");

        $this->initialTtyMode = null;
      }

      $this->started = false;
    }
  }
  
  public function readQueue(): ?key {
    $this->start();

    $input = fread(STDIN, 1024);
    //$input = $input !== false ? $input : '';

    $key = $this->translateInput($input);

    return new Key($key, $input);
  }

  private function translateInput(?string $input): ?string {
    if ($input == null) return null;

    //$result = $this->keymap[$input] ?? $input;

    // try matching the key with plain text
    foreach($this->textMap as $key => $code) {
      if ($code === $input) {
        return $key;
      }
    }

    // try matching the key with ascii
    foreach($this->asciiMap as $key => $code) {
      if (chr($code) === $input) {
        return $key;
      }
    }

    // try matching the key with unicode
    foreach($this->unicodeMap as $key => $code) {
      if ($this->unicodeToString($code) === $input) {
        return $key;
      }
    }

    return $result;
  }

  private function unicodeToString($code) {
    return json_decode('"' . $code . '"');
  }

}
