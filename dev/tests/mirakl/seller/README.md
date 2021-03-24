# Tests of the Mirakl Seller Connector

This page describes how to run the different tests that the Mirakl Seller Connector embeds.

Tests are developed using [PHPUnit](https://phpunit.de) and [AspectMock](https://github.com/Codeception/AspectMock) PHP mocking frameworks.

## Requirements

- PHP 7
- Composer

## Installation

First of all, you need to install the different packages used to run the test suites.

```
cd dev/tests/mirakl/seller/
composer install
```

## Run tests

```
./vendor/bin/phpunit
```

You can target specific tests using the `--group` option of PHPUnit or by specifying a folder of your choice.

For example:

```
./vendor/bin/phpunit --group api
./vendor/bin/phpunit --group model
./vendor/bin/phpunit tests/unit/
```
