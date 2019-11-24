Loader
======

Generic data/config loader with advanced features

## Usage:

```php
$loader = Loader::create();
$data = $loader->load('my.yaml');
```

## Features

### File formats:

* JSON
* JSON5 (comments, trailing commas, etc)
* YAML

### JSON References ($ref)

You can use json references in any of the supported file formats (json, json5, yaml)

example:

```yaml
localExample:
  $ref: example.json5
remoteExample:
  $ref: https://example.web/example.json
```

This will "include" the referenced files as if they were part of the main file.

The file type is determined based on file extension or (in case of remote files) the HTTP response header

### Variable interpolation (expressions)

You can reference other variables anywhere in your files:

```yaml
preferences:
  color: green
text: My favorite color is {{hello.color}}!
```

### Helper functions

You can use various helper functions inside of the variable blocks.

* strtoupper
* strtolower
* ucfirst
* array_merge_recursive: recursively merge 2 arrays (can be nested multiple times)
* dict: turn key/value dictionaries into arrays of key+value items

You can register your own helpers too:

```php
$interpolator->register(
  'myHelper',
  function ($arguments, $text) {
    // do something with the input arguments and return
    return ucfirst($text);
  }
)
```

### Variables in references:

You can also use variables in references:

```yaml
license: MIT
licenseUrl: https://opensource.org/licenses/{{ config.license }}
```

## License

MIT. Please refer to the [license file](LICENSE) for details.

## Brought to you by the LinkORB Engineering team

<img src="http://www.linkorb.com/d/meta/tier1/images/linkorbengineering-logo.png" width="200px" /><br />
Check out our other projects at [linkorb.com/engineering](http://www.linkorb.com/engineering).

Btw, we're hiring!

