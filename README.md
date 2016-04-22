# PIMGento

![alt text][logo]
[logo]: http://i.imgur.com/q0sdWSs.png "PIMGento : "

PIMGento is a Magento 2 extension that allows you to import your catalog from Akeneo CSV files into Magento.

## How it works

PIMGento reads CSV files from Akeneo and insert data directly in Magento database.

In this way, it makes imports very fast and doesn't disturb your e-commerce website.

With PIMGento, you can import :
* Categories
* Families
* Attributes
* Options
* Variants (configurable products)
* Products

## Requirements

* Akeneo 1.3, 1.4 and 1.5
* Magento >= 2.0 CE & EE
* Set local_infile mysql variable to TRUE
* Database encoding must be UTF-8
* Add "driver_options" key to Magento default connection configuration (app/etc/env.php)

```php
'db' =>
  array (
    'table_prefix' => '',
    'connection' =>
    array (
      'default' =>
      array (
        'host' => '',
        'dbname' => '',
        'username' => '',
        'password' => '',
        'active' => '1',
        'driver_options' => array(PDO::MYSQL_ATTR_LOCAL_INFILE => true),
      ),
    ),
  ),
```

With Akeneo 1.3 or 1.4, you need to install this Bundle (https://github.com/akeneo-labs/EnhancedConnectorBundle/) in order to generate appropriate CSV files for Magento.

### Installation ###

Install module by Composer as follows:

Configure Composer to search for this repository

```shell
composer config repositories.agencednd/module-pimgento vcs https://github.com/Agence-DnD/PIMGento-2
```
Require this repository in Composer
```shell
composer require agencednd/module-pimgento
```

Enable and install module(s) in Magento:

```shell
# [Required] Import tools
php bin/magento module:enable Pimgento_Import

# [Required] Database features
php bin/magento module:enable Pimgento_Entities

# [Optional] Database logs (System > Pimgento > Log)
php bin/magento module:enable Pimgento_Log

# [Optional] Activate desired imports
php bin/magento module:enable Pimgento_Category
php bin/magento module:enable Pimgento_Family
php bin/magento module:enable Pimgento_Attribute
php bin/magento module:enable Pimgento_Option
php bin/magento module:enable Pimgento_Variant
php bin/magento module:enable Pimgento_Product
```

Check and update database setup:
```shell
php bin/magento setup:db:status
php bin/magento setup:upgrade
```

Flush Magento caches
```shell
php bin/magento cache:flush
```

## Configuration and Usage

* Configure your store language and currency before import
* Launch import from admin panel in "System > Pimgento > Import"
* After category import, set the "Root Category" for store in "Stores > Settings > All Stores"

## Command line

Launch import with command line:

```shell
php bin/magento pimgento:import --code=product --file=product.csv
```

## Roadmap

* Command line tool (bin/magento) options for imports
* Image files import
* Link related products (related, cross-sells, up-sells)
* French translation
* Pim code exclusion

## About us

Founded by lovers of innovation and design, [Agence Dn'D] (http://www.dnd.fr) assists companies for 11 years in the creation and development of customized digital (open source) solutions for web and E-commerce.
