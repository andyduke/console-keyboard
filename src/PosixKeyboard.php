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
    "\n"  => self::ENTER,
    ' '   => self::SPACE,
    "\t"  => self::TAB,

    /*
    self::ENTER => "\n",
    self::SPACE => ' ',
    self::TAB   => "\t",
    */
  ];

  /**
   * Ascii char map
   */
  private $asciiMap = [
    127 => self::BACKSPACE, //\u0008 doesn't work

    /*
    self::BACKSPACE => 127 //\u0008 doesn't work
    */
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

    /*
    // main keys
    self::ESC      => '\u001b',

    // arrow keys
    self::UP       => '\u001b[A',
    self::DOWN     => '\u001b[B',
    self::LEFT     => '\u001b[D',
    self::RIGHT    => '\u001b[C',

    // function keys
    self::F1       => '\u001b[11~',
    self::F2       => '\u001b[12~',
    self::F3       => '\u001b[13~',
    self::F4       => '\u001b[14~',
    self::F5       => '\u001b[15~',
    self::F6       => '\u001b[17~',
    self::F7       => '\u001b[18~',
    self::F8       => '\u001b[19~',
    self::F9       => '\u001b[20~',
    self::F10      => '\u001b[21~',
    self::F11      => '\u001b[23~',
    self::F12      => '\u001b[24~',
    self::F13      => '\u001b[25~',
    self::F14      => '\u001b[26~',
    self::F15      => '\u001b[28~',
    self::F16      => '\u001b[29~',
    self::F17      => '\u001b[31~',
    self::F18      => '\u001b[32~',
    self::F19      => '\u001b[33~',
    self::F20      => '\u001b[34~',

    // other keys
    self::INS      => '\u001b[2~',
    self::DEL      => '\u001b[3~',
    self::HOME     => '\u001b[1~',
    self::END      => '\u001b[4~',
    self::PGUP     => '\u001b[5~',
    self::PGDOWN   => '\u001b[6~',
    */
  ];

  protected ?string $initialTtyMode;

  public function __construct($options = []) {

  }

  protected function prepare() {
    $this->initialTtyMode ??= (shell_exec('stty -g') ?: null);
    shell_exec('stty cbreak -echo');
  }

  protected function cleanup() {
    if ($this->initialTtyMode) {
      shell_exec("stty {$this->initialTtyMode}");

      $this->initialTtyMode = null;
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
    //foreach($this->textMap as $key => $code) {
    foreach($this->textMap as $code => $key) {
      if ($code === $input) {
        return $key;
      }
    }

    // try matching the key with ascii
    //foreach($this->asciiMap as $key => $code) {
    foreach($this->asciiMap as $code => $key) {
      if (chr($code) === $input) {
        return $key;
      }
    }

    // try matching the key with unicode
    //foreach($this->unicodeMap as $key => $code) {
    foreach($this->unicodeMap as $code => $key) {
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
