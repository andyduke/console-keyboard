<?php

namespace AndyDuke\ConsoleKeyboard;

class PosixKeyboard extends Keyboard {

  private const ESC_KEY       = "\e";
  private const SPACE_KEY     = " ";
  private const RETURN_KEY    = "\n";
  private const UP_KEY        = "\033[A";
  private const DOWN_KEY      = "\033[B";
  private const LEFT_KEY      = "\033[D";
  private const RIGHT_KEY     = "\033[C";

  private array $keymap = [
    self::ESC_KEY           => self::ESC,
    self::SPACE_KEY         => self::SPACE,
    self::ENTER_KEY         => self::ENTER,
    self::UP_KEY            => self::UP,
    self::DOWN_KEY          => self::DOWN,
    self::LEFT_KEY          => self::LEFT,
    self::RIGHT_KEY         => self::RIGHT,
  ];

  protected ?string $initialTtyMode;

  public function __construct($options = []) {
    $this->initialTtyMode ??= (shell_exec('stty -g') ?: null);

    shell_exec('stty cbreak -echo');
  }

  public function dispose() {
    if ($this->initialTtyMode) {
      shell_exec("stty {$this->initialTtyMode}");

      $this->initialTtyMode = null;
    }
  }
  
  public function readKey(): ?key {
    $input = fread(STDIN, 1024);
    //$input = $input !== false ? $input : '';

    $key = $this->translateInput($input);

    return new Key($key, $input);
  }

  private function translateInput(?string $input): ?string {
    if ($input == null) return null;

    $result = $this->keymap[$input] ?? $input;

    return $result;
  }

}
