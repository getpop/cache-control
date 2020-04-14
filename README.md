# Cache Control

<!--
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]
-->

Add HTTP caching to the response

## Install

Via Composer

``` bash
composer require getpop/cache-control dev-master
```

**Note:** Your `composer.json` file must have the configuration below to accept minimum stability `"dev"` (there are no releases for PoP yet, and the code is installed directly from the `master` branch):

```javascript
{
    ...
    "minimum-stability": "dev",
    "prefer-stable": true,
    ...
}
```

## How it works

It adds a mandatory directive `<cacheControl>` to all fields, which has a max-age value set for each field. 

The response will send a `Cache-Control` header with the lowest max-age from all the requested fields, or `no-store` if any field has max-age: 0.

## Examples

> **Note:**<br/>Click on the following links below, and inspect the response headers using Chrome or Firefox's developer tools' Network tab.

Operators have a max-age of 1 year:

```php
/?query=
  echo(Hello world!)
```

<a href="https://newapi.getpop.org/api/graphql/?query=echo(Hello+world!)">[View query results]</a>

By default, fields have a max-age of 1 hour:

```php
/?query=
  echo(Hello world!)|
  posts.
    title
```

<a href="https://newapi.getpop.org/api/graphql/?query=echo(Hello+world!)|posts.title">[View query results]</a>

Composed fields are also taken into account when computing the lowest max-age:

```php
/?query=
  echo(posts())
```

<a href="https://newapi.getpop.org/api/graphql/?query=echo(posts())">[View query results]</a>

`"time"` field is not to be cached (max-age: 0):

```php
/?query=
  time
```

<a href="https://newapi.getpop.org/api/graphql/?query=time">[View query results]</a>

Ways to not cache a response:

a. Add field `"time"` to the query:

```php
/?query=
  time|
  echo(Hello world!)|
  posts.
    title
```

<a href="https://newapi.getpop.org/api/graphql/?query=time|echo(Hello+world!)|posts.title">[View query results]</a>

b. Override the default `maxAge` configuration for a field, by adding argument `maxAge: 0` to directive `<cacheControl>`:

```php
/?query=
  echo(Hello world!)|
  posts.
    title<cacheControl(maxAge:0)>
```

<a href="https://newapi.getpop.org/api/graphql/?query=echo(Hello+world!)|posts.title<cacheControl(maxAge:0)>">[View query results]</a>

## Standards

[PSR-1](https://www.php-fig.org/psr/psr-1), [PSR-4](https://www.php-fig.org/psr/psr-4) and [PSR-12](https://www.php-fig.org/psr/psr-12).

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
composer test
```

## Static Analysis

Execute [phpstan](https://github.com/phpstan/phpstan) with level 8 (strictest mode):

``` bash
composer analyse
```

To run checks for level 0 (or any level from 0 to 8):

``` bash
./vendor/bin/phpstan analyse -l 0 src tests
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email leo@getpop.org instead of using the issue tracker.

## Credits

- [Leonardo Losoviz][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/getpop/cache-control.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/getpop/cache-control/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/getpop/cache-control.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/getpop/cache-control.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/getpop/cache-control.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/getpop/cache-control
[link-travis]: https://travis-ci.org/getpop/cache-control
[link-scrutinizer]: https://scrutinizer-ci.com/g/getpop/cache-control/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/getpop/cache-control
[link-downloads]: https://packagist.org/packages/getpop/cache-control
[link-author]: https://github.com/leoloso
[link-contributors]: ../../contributors
