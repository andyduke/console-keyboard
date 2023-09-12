<?php

use AndyDuke\ConsoleKeyboard\Keyboard;

require __DIR__ . '/../vendor/autoload.php';

function convertRawCode($code) {
  return (PHP_OS == 'WINNT')
    ? $code
    : str_replace("\033[", '<ESC>', $code);
}

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

  echo $key->getKey() . ' (' . convertRawCode($key->getRawKey()) . ')' . PHP_EOL;
}

echo "\nExit.\n";
