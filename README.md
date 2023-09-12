
# ConsoleKeyboard

Cross-platform reading of keyboard events in the terminal.

Makes it possible to process key presses in the terminal (including arrow keys, escape, enter, etc.) in Linux, Windows, MacOS.

> **Attention:** only works with PHP 7.4 and above, and uses FFI on Windows.


## Usage

First, you need to create the `Keyboard` class using the static `create()` method, which will create the desired version of the class for the current platform:
```php
$k = Keyboard::create();
```

Then you need to use a `foreach` loop to read the incoming keystrokes:
```php
foreach($k->read() as $key) {

}
```

> You don't have to setup console modes to hide the output of the keys pressed - the `Keyboard` class does this automatically when the loop starts and restores the console mode when exiting the loop.

> **Attention:** Pressing Ctrl-C interrupts the reading cycle.


Inside the loop you do the processing of each key press, for example displaying the name of each key pressed:
```php
$k = Keyboard::create();
foreach($k->read() as $key) {
  echo $key . PHP_EOL;
}
```

Inside the loop, you can implement any logic, checking which key is pressed, for example, in the above example you can add an exit from the loop using the `q` key:
```php
$k = Keyboard::create();
foreach($k->read() as $key) {
  if ($key == 'q') {
    break;
  }

  echo $key . PHP_EOL;
}
```

For keys such as arrow keys, escape, enter, spacebar, insert, delete, etc., the `Keyboard` class defines a [set of constants](#key-constants) that can be used in comparison:
```php
$k = Keyboard::create();
foreach($k->read() as $key) {
  if ($key == 'q' || $key == Keyboard::ESC) {
    break;
  }

  echo $key . PHP_EOL;
}
```

If you want to get not only the name of the key pressed but also the code (for Linux/MacOS this is ANSI code, for Windows this is scan code), you should use the `readKey()` method to read keys, instead of `read()`.

The `readKey()` method returns a Key object containing the key name and code, which are accessible using the `getKey()` and `getRawKey()` methods, respectively.

A Key object can be compared to a string, which casts it to a string and returns the name of the key. An example of displaying the names and codes of the keys pressed; the `q` key exits the reading loop:
```php
$k = Keyboard::create();
foreach($k->readKey() as $key) {
  if ($key == 'q') {
    break;
  }

  echo $key->getKey() . ' (' . $key->getRawKey() . ')' . PHP_EOL;
}
```


### Key Constants

| Constant    | Key name    |
|-------------|-------------|
| ESC         | Escape      |
| SPACE       | Space       |
| ENTER       | Enter       |
| TAB         | Tab         |
| BACKSPACE   | Backspace   |
| INS         | Insert      |
| DEL         | Delete      |
| HOME        | Home        |
| END         | End         |
| UP          | Up arrow    |
| DOWN        | Down arrow  |
| LEFT        | Left arrow  |
| RIGHT       | Right arrow |
| PGUP        | Page Up     |
| PGDOWN      | Page Down   |
| F1          | F1 key      |
| F2          | F2 key      |
| F3          | F3 key      |
| F4          | F4 key      |
| F5          | F5 key      |
| F6          | F6 key      |
| F7          | F7 key      |
| F8          | F8 key      |
| F9          | F9 key      |
| F10         | F10 key     |
| F11         | F11 key     |
| F12         | F12 key     |
| F13         | F13 key     |
| F14         | F14 key     |
| F15         | F15 key     |
| F16         | F16 key     |
| F17         | F17 key     |
| F18         | F18 key     |
| F19         | F19 key     |
| F20         | F20 key     |
| F21         | F21 key     |
| F22         | F22 key     |
| F23         | F23 key     |
| F24         | F24 key     |


## Why Generator?

The `read()` and `readKey()` methods return a [generator](https://www.php.net/manual/en/language.generators.overview.php), so you must use a `foreach` loop or another way of working with iterators to read keyboard input.

This is implemented this way because it allows the `Keyboard` class to automatically setup console modes at the beginning of reading console input and restore console modes after the end of the reading cycle.


## License

This project is licensed under the terms of the BSD 3-Clause license.
