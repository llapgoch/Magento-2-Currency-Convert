# Magento-2-Currency-Convert
This is a fork of Thanhdv2811_CurrencyConverter's module. Fixed to work with Free Currency Converter.

The currency import service in Magento 2 doesn't work any more (Fixer.io, Webservicex and Yahoo Finance Exchange). 
This extension will add other services to import the currency rate

```
$ composer require llapgoch/magento-2-currency-convert
$ php bin/magento module:enable Thanhdv2811_CurrencyConverter
$ php bin/magento setup:upgrade
```

# The currency converter
- https://currencylayer.com/
- https://free.currencyconverterapi.com/
- https://frankfurter.app/
