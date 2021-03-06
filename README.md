[![Build Status](https://img.shields.io/travis/retailcrm/magento-module/master.svg?style=flat-square)](https://travis-ci.org/retailcrm/magento-module)
[![GitHub release](https://img.shields.io/github/release/retailcrm/magento-module.svg?style=flat-square)](https://github.com/retailcrm/magento-module/releases)
[![PHP version](https://img.shields.io/badge/PHP->=5.6-blue.svg?style=flat-square)](https://php.net/)

Magento module
==============

Magento 2 module for interaction with [RetailCRM](http://www.retailcrm.ru) ([Documentation](https://docs.retailcrm.pro/Users/Integration/SiteModules/Magento) page)

Module allows:

* Exchange the orders data with RetailCRM
* Configure relations between dictionaries of retailCRM and Magento (statuses, payments, delivery types and etc)
* Generate [ICML](http://www.retailcrm.ru/docs/Developers/ICML) (Intaro Markup Language) export file for catalog loading by RetailCRM

## ICML

By default ICML file is being generated by module every 4 hours. You can find file in the web root folder with name "retailcrm_{{shop_code}}.xml". For example, http://example.org/retailcrm_default.xml

### Manual install

1) Run into your project root directory:

1) Unpack the archive with the module in the directory "app/code/Retailcrm/Retailcrm". In the file "app/etc/config.php" in array `modules` add an element `'Retailcrm_Retailcrm' => 1`

2) Run into your project directory:

```bash
composer require retailcrm/api-client-php ~5.0
```

This module is compatible with Magento up to version 2.2.8
