### Installation ###

Specify new VCS repository in the repositories section of the "composer.json" file of the Magento 2 project (if the repositories section doesn’t exist, create it, else, add repository at the end):

```json
"repositories": [
   {
     "type": "vcs",
     "url": "https://gitlab.com/agence-dnd-extensions/magento2_pimgento.git"
   }
 ],
```

Then, install module as follows:

```shell
composer require agencednd/module-pimgento
```

The above command download module and copy it’s content to *app/code/Pimgento*.

Then, enable and install module(s) in Magento:

```shell
php bin/magento module:enable Pimgento_Import
php bin/magento module:enable Pimgento_Entities
php bin/magento module:enable Pimgento_Log
php bin/magento module:enable Pimgento_Demo
php bin/magento module:enable Pimgento_Category
php bin/magento module:enable Pimgento_Family
php bin/magento setup:db:status
php bin/magento setup:upgrade
php bin/magento cache:flush
```
