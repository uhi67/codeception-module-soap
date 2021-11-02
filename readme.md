# Codeception Module SOAP

A SOAP module for Codeception.

_Version 1.1_

[![Latest Stable Version](https://poser.pugx.org/uhi67/codeception-module-soap/v/stable)](https://github.com/uhi67/codeception-module-soap/releases)
[![Total Downloads](https://poser.pugx.org/uhi67/codeception-module-soap/downloads)](https://packagist.org/packages/uhi67/codeception-module-soap)
[![License](https://poser.pugx.org/uhi67/codeception-module-soap/license)](/LICENSE)

## Requirements

* `PHP 7.1` or higher.

## Installation

```
composer require "uhi67/module-soap" --dev
```

## Documentation

See [the module documentation](https://codeception.com/docs/modules/SOAP).

## License

`Codeception Module SOAP` is open-sourced software licensed under the [MIT](/LICENSE) License.

© Codeception PHP Testing Framework

## Changes

### 1.1 (2021-11-02)

- sendSoapRequest now creates request from array correctly
- consolidated namespace usage
- seeSoapResponseContainsXPath, dontSeeSoapResponseContainsXPath fixed
- grabSoapRequest, grabSoapResponse, grabSoapResult, seeResponseIsValidOnSchema added
