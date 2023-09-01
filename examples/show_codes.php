<?php

use AndyDuke\ConsoleKeyboard\Keyboard;
use AndyDuke\ConsoleKeyboard\Win32Keyboard;

require __DIR__ . '/../vendor/autoload.php';

$k = null;

echo "Press any key to display key code or press Q to exit:\n\n";

$k = Keyboard::create([
  'win32' => [
    //'eventType' => Win32Keyboard::EVENT_KEYUP,
  ],
]);

if (PHP_OS == 'WINNT') {

  function win_ctrl_handler(int $event) {
    switch ($event) {
      case PHP_WINDOWS_EVENT_CTRL_C:
      case PHP_WINDOWS_EVENT_CTRL_BREAK:
        $GLOBALS['k']->stop();
        break;
    }
  }

  sapi_windows_set_ctrl_handler('win_ctrl_handler');

}

$key = null;
do {
  $key = $k->readKey();

  // Ctrl-C or Error
  if (is_null($key)) {
    break;
  }

  echo $key->getKey() . ' (' . $key->getRawKey() . ')' . "\n";
} while ($key != 'q');

echo "\nExit.\n";
