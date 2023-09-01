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

  private const ESC_CODE       = 27;
  private const SPACE_CODE     = 32;
  private const ENTER_CODE     = 13;

  private const TAB_CODE       = 9;
  private const BACKSPACE_CODE = 8;

  private const INS_CODE       = 45;
  private const DEL_CODE       = 46;
  private const HOME_CODE      = 36;
  private const END_CODE       = 35;
  private const PGUP_CODE      = 33;
  private const PGDOWN_CODE    = 34;

  private const UP_CODE        = 38;
  private const DOWN_CODE      = 40;
  private const LEFT_CODE      = 37;
  private const RIGHT_CODE     = 39;

  private const CAPSLOCK_CODE   = 20;
  private const NUMLOCK_CODE    = 144;
  private const SCROLLLOCK_CODE = 145;

  private const SHIFT_CODE     = 16;
  private const CTRL_CODE      = 17;
  private const ALT_CODE       = 18;
  private const LWIN_CODE      = 91;
  private const RWIN_CODE      = 92;

  private array $keymap = [
    self::ESC_CODE           => self::ESC,
    self::SPACE_CODE         => self::SPACE,
    self::ENTER_CODE         => self::ENTER,
    self::TAB_CODE           => self::TAB,
    self::BACKSPACE_CODE     => self::BACKSPACE,
    self::INS_CODE           => self::INS,
    self::DEL_CODE           => self::DEL,
    self::HOME_CODE          => self::HOME,
    self::END_CODE           => self::END,
    self::PGUP_CODE          => self::PGUP,
    self::PGDOWN_CODE        => self::PGDOWN,
    self::UP_CODE            => self::UP,
    self::DOWN_CODE          => self::DOWN,
    self::LEFT_CODE          => self::LEFT,
    self::RIGHT_CODE         => self::RIGHT,

    self::CAPSLOCK_CODE      => 'capslock',
    self::NUMLOCK_CODE       => 'numlock',
    self::SCROLLLOCK_CODE    => 'scrolllock',
  ];

  protected int $inputTimeout = self::INFINITE;

  protected int $eventType = self::EVENT_KEYDOWN;

  protected bool $started = false;

  protected $win;

  protected $handle;

  private $queue;

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
    if (is_null($this->queue) || !$this->queue->valid()) {
      $this->queue = $this->inputQueue();
    }

    $key = $this->queue->current();
    $this->queue->next();

    if ($key === null) {
      $this->queue = null;
    }

    return $key;
  }

  private function inputQueue(): \Generator {
    $keys = [];

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

            if (!$this->isControlKey($keyCode)) {
              $count = $keyEvent->wRepeatCount;

              for ($c = 0; $c < $count; $c++) {
                $keys[] = [
                  'char'    => $keyChar,
                  'code'    => $keyCode,
                ];
              }
            }

            break;
          }
        }
      }

    } while (empty($keys) && !$this->stopping);

    if ($this->stopping) {
      return null;
    }

    // Clear console input buffer
    if (!$this->win->FlushConsoleInputBuffer($this->handle)) {
      throw new \Exception('Failed to clear console input buffer (FlushConsoleInputBuffer).');
    }

    if (!is_null($keys)) {
      $keys = array_map(
        function($input) {
          $key = $this->translateInput($input['code'], $input['char']);
          return new Key($key, $input['code']);
        },
        $keys
      );
    } else {
      //echo "Key code: "; var_export($keyCode); echo "\n";
      //echo "Key char: "; var_export($keyChar); echo "\n";
      //echo "Null key...\n";
    }

    foreach($keys as $key) {
      yield $key;
    }
  }

  protected function isControlKey(int $keyCode): bool {
    return ($keyCode == self::SHIFT_CODE) ||
           ($keyCode == self::CTRL_CODE) ||
           ($keyCode == self::ALT_CODE) ||
           ($keyCode == self::LWIN_CODE) ||
           ($keyCode == self::RWIN_CODE);
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
