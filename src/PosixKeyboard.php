<?php

namespace AndyDuke\ConsoleKeyboard;

class PosixKeyboard extends Keyboard {

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

    $result = $this->keymap[$input] ?? $input;

    return $result;
  }

}
