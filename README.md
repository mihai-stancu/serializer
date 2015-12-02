Serializer
================================================================================

Contents
--------------------------------------------------------------------------------

A recursive normalizer that uses symfony/property-info to deduce data types of elements in an object graph.

A type hinted normalizer that retains type information about the object graph being normalized.

A collection of (symfony compliant) encoders for the following formats:

* [Bencode](http://en.wikipedia.org/wiki/Bencode) as a pure PHP implementation provided by [rych/bencode](http://github.com/rchouinard/bencode).
* [BSON](http://en.wikipedia.org/wiki/BSON) as a PHP extension provided by the `mongo` extension.
* [CBOR](http://cbor.io/) as a pure PHP implementation provided by [2tvenom/cborencode](http://github.com/2tvenom/cborencode).
* [Export](http://php.net/manual/ro/function.var-export.php) as a PHP core functionality.
* [Form](http://php.net/manual/en/function.http-build-query.php) as a PHP core functionality.
* [IGBinary](http://github.com/igbinary/igbinary) as a PHP extension provided by the `igbinary` extension -- as a drop-in replacement for `PHP Serialize`.
* [Ini](http://github.com/zendframework/zend-config) PHP implementation provided by [zendframework/zend-config](http://github.com/zendframework/zend-config).
* [MsgPack](http://msgpack.org/) as a PHP extension provided by the `msgpack` extension.
* [Rison](http://github.com/Nanonid/rison) as a pure PHP implementation provided by [deceze/Kunststube-Rison](http://github.com/deceze/Kunststube-Rison).
* [`//Sereal`](http://github.com/Sereal/Sereal) as a pure PHP implementation provided by [tobyink/sereal](http://github.com/tobyink/php-sereal).
* [Serialize](http://php.net/manual/ro/function.serialize.php) as a PHP core functionality.
* [`//Smile`](http://en.wikipedia.org/wiki/Smile_(data_interchange_format)) as a PHP extension provided by the `libsmile` extension.
* [Tnetstring](http://en.wikipedia.org/wiki/Netstring) as a pure PHP implementation provided by [phuedx/tnetstring](http://github.com/phuedx/tnetstring).
* [UBJSON](http://ubjson.org/) as a pure PHP implementation provided by [dizews/php-ubjson](http://github.com/dizews/php-ubjson).
* [YAML](http://en.wikipedia.org/wiki/YAML) as a PHP extension provided by the `yaml` extension or a pure PHP implementation provided by [symfony/yaml](http://github.com/symfony/yaml).


Installation
--------------------------------------------------------------------------------

Choose one ore more encoding formats from the suggestions in `composer.json`. If
the chosen format is described below as PHP extension you will have to install
said extension. If it is a pure PHP implementation you will be able to require
it via composer.

```bash
composer require mihai-stancu/serializer
composer require your-chosen/encoder-package
```

```php
// in AppKernel::registerBundles()
$bundles = array(
    // ...
    new MS\SerializerBundle\MSSerializerBundle(),
    // ...
);
```

Usage
--------------------------------------------------------------------------------

After having installed the bundle and at least one of the suggested encoding
formats, that encoding format will be registered as a serialization format for the
[symfony/serializer](http://symfony.com/doc/current/components/serializer.html).

```php
$encoderName = array_rand(
    array(
        'bencode', 
        'bson', 
        'cbor',
        'export',
        'form',
        'igbinary',
        'ini',
        'msgpack',
        'rison',
        //'sereal',
        'serial',
        //'smile',
        'tnetstring',
        'ubjson',
        'yaml',
    )
);

$serializer = $container->get('serializer');
$string = $serializer->serialize($data, $encoderName);
$data = $serializer->unserialize($data, $class, $encoderName);
```
