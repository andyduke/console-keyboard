
# ConsoleKeyboard

Cross-platform reading of keyboard events in the terminal.

## Usage

TODO: TBD


## Why Generator?

The `read()` and `readKey()` methods return a [generator](https://www.php.net/manual/en/language.generators.overview.php), so you must use a foreach loop or another way of working with iterators to read keyboard input.

This is done because it allows the `Keyboard` class to automatically switch console modes at the beginning of reading console input and restore console modes after the end of the reading cycle.
