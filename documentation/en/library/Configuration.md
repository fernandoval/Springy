# Configuration

* [About](#about)
* [Properties](#properties)
* [Public Methods](#public-methods)
  * [Details](#methods-details)

## About

Configuration is a static class you can use to load system configurations and do not need create objects to access it.

## Properties

The class has no properties you can access directly.

|Property|Type|Description|
|---|---|---|
|**$confs**|*private static*|An array with all configurations loaded|

## Public Methods

|Name|Type|Description|
|---|---|---|
|**get()**|*public static*|Get the value of a configuration register.|
|**set()**|*public static*|Set the value of a configuration register.|
|**load()**|*public static*|Load a configuration file.|

## Methods Details

### get()
This method gets a value of a configuration register.
```php
    public static function get($local, $var = null)
```
The `$local` parameter defines the configuration filename (without suffix and extension).

The `$var` parameter defines the configuration register. It can be a dotted entry if your configuration register is an array and you want a specific register from it.

You can pass `$local` in dotted form and omit `$var`. In this case, the first segment will be considered the filename and the rest the configuration register.

Returns *null* if there is no entry for `$var` in `$local` or throws a 500 error if the configuration file does not exists.

**Sample:**
```php
    $databaseType = FW\Configuration::get('db', 'default.database_type');
```

### set()
This method changes the value of a configuration register during current application execution. The value will not be save in configuration file.
```php
    public static function set($local, $var, $value = null)
```
The `$local` parameter defines the configuration filename (without suffix and extension).

The `$var` parameter defines the configuration register. It can be a dotted entry if your configuration register is an array and you want a specific register from it.

The `$value` if a mixed value to set to `$var`.

**Sample:**
```php
    $databaseType = FW\Configuration::set('db', 'default.database_type', 'MySQL');
```


### load()
This method loads a configuration file. It is impliced called by **get()** if the configuration file was not read yet.
```php
    public static function load($local)
```
The `$local` parameter defines the configuration filename (without suffix and extension).

Returns *true* if the file was loaded or throws a 500 error if the configuration file does not exists.

**Sample:**
```php
    $confLoaded = FW\Configuration::load('db');
```
