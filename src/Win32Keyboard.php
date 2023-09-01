<?php

namespace AndyDuke\ConsoleKeyboard;

class Win32Keyboard extends Keyboard {

  public const EVENT_KEYUP = 1;
  public const EVENT_KEYDOWN = 2;

  private const STD_INPUT_HANDLE = -10;

  // https://docs.microsoft.com/fr-fr/windows/console/setconsolemode
  private const ENABLE_ECHO_INPUT = 0x0004;
  private const ENABLE_PROCESSED_INPUT = 0x0001;
  private const ENABLE_WINDOW_INPUT = 0x0008;

  // https://docs.microsoft.com/fr-fr/windows/console/input-record-str
  private const KEY_EVENT = 0x0001;

  // https://learn.microsoft.com/en-us/windows/win32/api/synchapi/nf-synchapi-waitforsingleobject?redirectedfrom=MSDN
  private const WAIT_OBJECT_0 = 0x00000000;
  private const INFINITE = /*-1*/0xFFFFFFFF;

  private const ESC_KEY       = 27;
  private const SPACE_KEY     = 32;
  private const ENTER_KEY     = 13;
  private const UP_KEY        = 38;
  private const DOWN_KEY      = 40;
  private const LEFT_KEY      = 37;
  private const RIGHT_KEY     = 39;

  private const SHIFT_KEY     = 16;
  private const CTRL_KEY      = 17;
  private const ALT_KEY       = 18;
  private const LWIN_KEY      = 91;
  private const RWIN_KEY      = 92;

  private array $keymap = [
    self::ESC_KEY           => self::ESC,
    self::SPACE_KEY         => self::SPACE,
    self::ENTER_KEY         => self::ENTER,
    self::UP_KEY            => self::UP,
    self::DOWN_KEY          => self::DOWN,
    self::LEFT_KEY          => self::LEFT,
    self::RIGHT_KEY         => self::RIGHT,

    // TODO: Backspace, etc.
  ];

  protected int $inputTimeout = self::INFINITE;

  protected int $eventType = self::EVENT_KEYDOWN;

  protected bool $started = false;

  protected $win;

  protected $handle;

  private $stopping = false;

  private $arrayBufferSize = 128;

  private $oldMode;
  private $bufferSize;
  private $inputBuffer;
  private $cNumRead;

  public function __construct($options = []) {
    foreach($options as $key => $value) {
      if (property_exists($this, $key)) {
        $this->{$key} = $value;
      }
    }

    $this->loadLibrary();
  }

  public function start() {
    if (!$this->started) {
      $this->started = true;

      $this->initConsole();
    }
  }

  public function stop() {
    if ($this->started) {
      if (!is_null($this->handle) && !$this->stopping) {
        // TODO: DEBUG
        //echo "stopping...\n";

        $this->stopping = true;

        // Restore console mode
        if (!$this->win->SetConsoleMode($this->handle, $this->oldMode->cdata)) {
          throw new \Exception('Failed to restore console mode (SetConsoleMode).');
        }

        $this->win->CloseHandle($this->handle);
      }
    }
  }
    
  protected function readQueue(): ?Key {
    $result = null;
    $keyCode = null;
    $keyChar = null;

    do {

      if ($this->stopping) {
        return null;
      }

      // Wait for console input events
      if ($this->win->WaitForSingleObject($this->handle, $this->inputTimeout) != self::WAIT_OBJECT_0) {
        // Timeout
        return null;
      }

      if ($this->stopping) {
        return null;
      }

      // Get number of console input events
      if (!$this->win->GetNumberOfConsoleInputEvents($this->handle, \FFI::addr($this->bufferSize))) {
        throw new \Exception('Failed to get number of console input events (GetNumberOfConsoleInputEvents).');
      }

      $eventsCount = $this->bufferSize->cdata;

      if ($eventsCount >= 1) {
        if (!$this->win->ReadConsoleInputW(
              $this->handle,                   // input buffer handle
              $this->inputBuffer,              // buffer to read into
              $this->arrayBufferSize,          // size of read buffer
              \FFI::addr($this->cNumRead)) ) { // number of records read
          throw new \Exception('Failed to read console input event data (ReadConsoleInputW).');
        }

        //var_export($this->cNumRead->cdata);
        //echo "\n";

        // Read input events
        for ($j = $this->cNumRead->cdata - 1; $j >= 0; $j--) {
          if ($this->inputBuffer[$j]->EventType === self::KEY_EVENT) {
            $keyEvent = $this->inputBuffer[$j]->Event->KeyEvent;

            // Check event type
            $eventType = $keyEvent->bKeyDown ? self::EVENT_KEYDOWN : self::EVENT_KEYUP;

            //var_export($event_type); echo ' != '; var_export($this->eventType); echo "\n";

            //echo "."; var_export($eventType);

            if ($eventType != $this->eventType) continue;

            //var_export($keyEvent);

            //var_export($keyEvent->uChar->AsciiChar);

            //echo "*";

            $keyCode = $keyEvent->wVirtualKeyCode;
            $keyChar = $keyEvent->uChar->AsciiChar;

            if ($this->isControlKey($keyCode)) {
              $keyCode = null;
              $keyChar = null;
            }

            break;
          }
        }
      }

    } while (is_null($keyCode) && !$this->stopping);

    if ($this->stopping) {
      return null;
    }

    // Clear console input buffer
    if (!$this->win->FlushConsoleInputBuffer($this->handle)) {
      throw new \Exception('Failed to clear console input buffer (FlushConsoleInputBuffer).');
    }

    if (!is_null($keyCode)) {
      $key = $this->translateInput($keyCode, $keyChar);

      $result = new Key($key, $keyCode);
    } else {
      //echo "Key code: "; var_export($keyCode); echo "\n";
      //echo "Key char: "; var_export($keyChar); echo "\n";
      //echo "Null key...\n";
    }

    return $result;
  }

  protected function isControlKey(int $keyCode): bool {
    return ($keyCode == self::SHIFT_KEY) ||
           ($keyCode == self::CTRL_KEY) ||
           ($keyCode == self::ALT_KEY) ||
           ($keyCode == self::LWIN_KEY) ||
           ($keyCode == self::RWIN_KEY);
  }

  private function loadLibrary() {
    // Check if FFI available
    if (!extension_loaded('ffi')) {
      // Try to dynamically load ffi extension
      if (!dl('php_ffi.dll')) {
        throw new \Exception('Could not load FFI extension.');
      }
    }

    // Load win32 library
    $this->win = \FFI::load(__DIR__ . '/headers/windows.h');
  }

  private function initConsole() {
    // Get console handle
    $this->handle = $this->win->GetStdHandle(self::STD_INPUT_HANDLE);

    // Allocate variables
    $this->oldMode = $this->win->new('DWORD');
    $this->bufferSize = $this->win->new('DWORD');
    $this->inputBuffer = $this->win->new("INPUT_RECORD[$this->arrayBufferSize]");
    $this->cNumRead = $this->win->new('DWORD');

    // Get console mode
    if (!$this->win->GetConsoleMode($this->handle, \FFI::addr($this->oldMode))) {
      throw new \Exception('Failed to get console mode (GetConsoleMode).');
    }

    // Set console mode
    $newConsoleMode = self::ENABLE_WINDOW_INPUT | self::ENABLE_PROCESSED_INPUT;
    if (!$this->win->SetConsoleMode($this->handle, $newConsoleMode)) {
      throw new \Exception('Failed to set new console mode (SetConsoleMode).');
    }

    // Clear previous console input buffer
    if (!$this->win->FlushConsoleInputBuffer($this->handle)) {
      throw new \Exception('Failed to clear console input buffer (FlushConsoleInputBuffer).');
    }
  }

  private function translateInput(int $code, string $char): ?string {
    $result = $this->keymap[$code] ?? $char;

    return $result;
  }

}
