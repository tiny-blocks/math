# Math

[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

* [Overview](#overview)
* [Installation](#installation)
* [How to use](#how-to-use)
* [License](#license)
* [Contributing](#contributing)

<div id='overview'></div> 

## Overview

Value Objects for handling arbitrary precision numbers.

<div id='installation'></div>

## Installation

```bash
composer require tiny-blocks/math
```

<div id='how-to-use'></div>

## How to use

The library exposes some concrete implementations for arbitrary precision numbers. Concrete implementations implement
the `BigNumber` interface, which provides the behaviors for the respective **BigNumbers**.

### Using the from method

With the `from` method, a new instance of type `BigNumber` is created from a valid numeric value. You can provide
a `string` or `float` value.

```php
BigDecimal::from(value: '10');
BigDecimal::from(value: 10);
```

It is possible to set a `scale` for the object through the `from` method.

```php
BigDecimal::from(value: '10', scale: 2);
```

Floating point values instantiated from a `float` may not be safe, as they are imprecise by design and may result in a
loss of precision. Always prefer to instantiate from a `string`, which supports an unlimited amount digits.

### Using the methods of mathematical operations

#### Addition

Performs an addition operation between this value and another value.

```php
$augend = BigDecimal::from(value: 1);
$addend = BigDecimal::from(value: '1');

$result = $augend->add(addend: $addend);

echo $result->toString(); # 2
```

#### Subtraction

Performs a subtraction operation between this value and another value.

```php
$minuend = BigDecimal::from(value: 1);
$subtrahend = BigDecimal::from(value: '1');

$result = $minuend->subtract(subtrahend: $subtrahend);

echo $result->toString(); # 0
```

#### Multiplication

Performs a multiplication operation between this value and another value.

```php
$multiplicand = BigDecimal::from(value: 1);
$multiplier = BigDecimal::from(value: '1');

$result = $multiplicand->multiply(multiplier: $multiplier);

echo $result->toString(); # 1
```

#### Division

Performs a division operation between this value and another value.

```php
$dividend = BigDecimal::from(value: 1);
$divisor = BigDecimal::from(value: '1');

$result = $dividend->divide(divisor: $divisor);

echo $result->toString(); # 1
```

### Using other resources

If you need to perform rounding, you can use the `withRounding` method.

Use one of the following constants to specify the [mode](https://www.php.net/manual/en/function.round.php) in which
rounding occurs:

- `HALF_UP`: Round number away from zero when halfway.

    ```php
    $value = BigDecimal::from(value: 0.9950, scale: 2);

    $result = $value->withRounding(mode: RoundingMode::HALF_UP);
  
    echo $result->toString(); # 1
    ```

- `HALF_DOWN`: Round number to zero when halfway.

    ```php
    $value = BigDecimal::from(value: 0.9950, scale: 2);

    $result = $value->withRounding(mode: RoundingMode::HALF_DOWN);
  
    echo $result->toString(); # 0.99
    ```

- `HALF_EVEN`: Round number to the nearest even value when halfway.

    ```php
    $value = BigDecimal::from(value: 0.9950, scale: 2);

    $result = $value->withRounding(mode: RoundingMode::HALF_EVEN);

    echo $result->toString(); # 1
    ```

- `HALF_ODD`: Round number to the nearest odd value when halfway.

    ```php
    $value = BigDecimal::from(value: 0.9950, scale: 2);

    $result = $value->withRounding(mode: RoundingMode::HALF_ODD);

    echo $result->toString(); # 0.99
    ```

#### Negate

Sometimes it is necessary to convert a value to negative, in these cases you can use the `negate` method.

```php
$value = BigDecimal::from(value: 1);

$result = $value->negate();

echo $result->toString(); # -1
```

#### Others

Check out other available resources by looking at the [BigNumber](src/BigNumber.php) interface.

## License

Math is licensed under [MIT](/LICENSE).

<div id='contributing'></div>

## Contributing

Please follow the [contributing guidelines](https://github.com/tiny-blocks/tiny-blocks/blob/main/CONTRIBUTING.md) to
contribute to the project.
