# Isolated Callback [![Build Status](https://secure.travis-ci.org/vatson/isolated-callback.png)](http://travis-ci.org/vatson/isolated-callback) [![Latest Stable Version](https://poser.pugx.org/vatson/isolated-callback/v/stable.png)](https://packagist.org/packages/vatson/isolated-callback) [![Total Downloads](https://poser.pugx.org/vatson/isolated-callback/downloads.png)](https://packagist.org/packages/vatson/isolated-callback)

Tiny but still powerful tool to fork it all.

Isolated callback allows to execute any callable statement in a fork and  avoid memory leaks.

## Installation

Installation is damn easy, thanks to Composer:

    composer require vatson/isolated-callback

or add the requirements to your composer.json file

    {
        "require": {
            "vatson/isolated-callback": "*"
        }
    }

and run `update`

    composer update vatson/isolated-callback

## Usage

Quick and easy. Let's create some anonymous function that generates a lot of data, but the result is a small

```php
<?php
include_once 'vendor/autoload.php';

use Vatson\Callback\IsolatedCallback;

$cb = function() {
    return array_slice(range(1, 100000), rand(1,100), rand(1,10));
};

$icb = new IsolatedCallback($cb);
$random_slice = $icb();
```

That's it. Your callback will be run in separate fork and the result will be sent to the main process.

### Callback arguments

Note, you can call it with args and bind some local vars with your lambda functions.

```php
<?php
include_once 'vendor/autoload.php';

use Vatson\Callback\IsolatedCallback;

$slice_length = rand(1,5);
$cb = function($max_range) use($slice_length) {
    return array_slice(range(1, $max_range), rand(1,$max_range), $slice_length);
};

$icb = new IsolatedCallback($cb);
$random_slice = $icb(1000);
```

### Objects as a result

Also you can send not only scalars, but simple objects (POPO). And rember, objects based on or which contain `Resources` can't be serialized as a result.

```php
<?php

use Vatson\Callback\IsolatedCallback;

$cb = function() {
    $popo_object = new \stdClass();
    $popo_object->property = 'value';
    return $popo_object;
};

$icb = new IsolatedCallback($cb);
$property = $icb()->property;
```

### Restrictions

Beware, the current implementation uses System V IPC to share a result between processes. Amount of  transferred data depends on your system configuration. The best practice is to send short but succinct results.

## Requirements

-   PHP \>= 5.3.2

-   Process Control ([PCNTL][]) - allows to make a fork

-   System V IPC ([semaphore][]) - adds an ability to share the results between distributed processes

-   *[Optional]* [Fumocker][] - mocks the php's built-in functions

-   *[Optional]* PHPUnit 3.5+ to execute the test suite

## Authors

-   [Vadim Tyukov][] ([twitter][])

-   [Slava Hatnuke][]

## License

Isolated Callback is distributed under the terms of the MIT license - see the `LICENSE` file for details

  [PCNTL]: http://php.net/manual/en/book.pcntl.php
  [semaphore]: http://www.php.net/manual/en/book.sem.php
  [Fumocker]: https://github.com/formapro/Fumocker
  [Vadim Tyukov]: mailto:brainreflex@gmail.com
  [twitter]: http://twitter.com/Elusive_Joe
  [Slava Hatnuke]: mailto:slava.hatnuke@gmail.com
