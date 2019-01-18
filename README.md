# ShareFile2eDoc

## Installation

```sh
composer install --no-dev --optimize-autoloader
bin/console doctrine:migrations:migrate --no-interaction
```


```sh
bin/console app:sharefile2edoc:sync -vv -- '-4 hours'
```
