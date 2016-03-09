### Installation ###

Install module by Composer as follows:

```shell
composer require agencednd/module-pimgento
```

Enable and install module(s) in Magento:

```shell
php bin/magento module:enable Pimgento_Import
php bin/magento module:enable Pimgento_Entities
php bin/magento module:enable Pimgento_Log
php bin/magento module:enable Pimgento_Demo
php bin/magento module:enable Pimgento_Category
php bin/magento module:enable Pimgento_Family
//php bin/magento module:enable Pimgento_Attribute [under development]
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