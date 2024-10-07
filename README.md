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

### Using the fromString method

With the `fromString` method, a new instance of type `BigNumber` is created from a valid string numeric value.

```php
BigDecimal::fromString(value: '10');
BigDecimal::fromString(value: '-123.456');
```

It is possible to set a `scale` for the object through this method.

```php
BigDecimal::fromString(value: '10', scale: 2);
```

Always prefer to instantiate from a string, which supports an unlimited number of digits and ensures no loss of
precision.

### Using the fromFloat method

With the `fromFloat` method, a new instance of type `BigNumber` is created from a valid float value.

```php
BigDecimal::fromFloat(value: 10.0);
BigDecimal::fromFloat(value: -123.456);
```

It is also possible to set a `scale` for the object through this method.

```php
BigDecimal::fromFloat(value: 10.0, scale: 2);
```

### Using the methods of mathematical operations

#### Addition

Performs an addition operation between this value and another value.

```php
$augend = BigDecimal::fromString(value: '1');
$addend = BigDecimal::fromFloat(value: 1.0);

$result = $augend->add(addend: $addend);

$result->toString(); # 2
```

#### Subtraction

Performs a subtraction operation between this value and another value.

```php
$minuend = BigDecimal::fromString(value: '1');
$subtrahend = BigDecimal::fromFloat(value: 1.0);

$result = $minuend->subtract(subtrahend: $subtrahend);

$result->toString(); # 0
```

#### Multiplication

Performs a multiplication operation between this value and another value.

```php
$multiplicand = BigDecimal::fromString(value: '1');
$multiplier = BigDecimal::fromFloat(value: 1.0);

$result = $multiplicand->multiply(multiplier: $multiplier);

$result->toString(); # 1
```

#### Division

Performs a division operation between this value and another value.

```php
$dividend = BigDecimal::fromString(value: '1');
$divisor = BigDecimal::fromFloat(value: 1.0);

$result = $dividend->divide(divisor: $divisor);

$result->toString(); # 1
```

### Using other resources

If you need to perform rounding, you can use the `withRounding` method.

Use one of the following constants to specify the [mode](https://www.php.net/manual/en/function.round.php) in which
rounding occurs:

- `HALF_UP`: Round number away from zero when halfway.

    ```php
    $value = BigDecimal::fromFloat(value: 0.9950, scale: 2);
    
    $result = $value->withRounding(mode: RoundingMode::HALF_UP);
    
    $result->toString(); # 1
    ```

- `HALF_DOWN`: Round number to zero when halfway.

    ```php
    $value = BigDecimal::fromFloat(value: 0.9950, scale: 2);
  
    $result = $value->withRounding(mode: RoundingMode::HALF_DOWN);
  
    $result->toString(); # 0.99
    ```

- `HALF_EVEN`: Round number to the nearest even value when halfway.

    ```php
    $value = BigDecimal::fromFloat(value: 0.9950, scale: 2);
  
    $result = $value->withRounding(mode: RoundingMode::HALF_EVEN);
  
    $result->toString(); # 1
    ```

- `HALF_ODD`: Round number to the nearest odd value when halfway.

    ```php
    $value = BigDecimal::fromFloat(value: 0.9950, scale: 2);
  
    $result = $value->withRounding(mode: RoundingMode::HALF_ODD);
  
    $result->toString(); # 0.99
    ```

#### Negate

Sometimes it is necessary to convert a value to negative, in these cases you can use the `negate` method.

```php
$value = BigDecimal::fromFloat(value: 1);

$result = $value->negate();

$result->toString(); # -1
```

#### Others

Check out other available resources by looking at the [BigNumber](src/BigNumber.php) interface.

<div id='license'></div>

## License

Math is licensed under [MIT](LICENSE).

<div id='contributing'></div>

## Contributing

Please follow the [contributing guidelines](https://github.com/tiny-blocks/tiny-blocks/blob/main/CONTRIBUTING.md) to
contribute to the project.
