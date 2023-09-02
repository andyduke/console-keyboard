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

foreach($k->readKey() as $key) {
  // Ctrl-C or Error
  if (is_null($key)) {
    break;
  }

  if ($key == 'q') {
    break;
  }

  echo $key->getKey() . ' (' . $key->getRawKey() . ')' . "\n";
}

echo "\nExit.\n";
