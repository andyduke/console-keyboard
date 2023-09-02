<?php

use AndyDuke\ConsoleKeyboard\Keyboard;

require __DIR__ . '/../vendor/autoload.php';

echo "Press any key to display key code or press Q to exit:\n\n";

$k = Keyboard::create();

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
