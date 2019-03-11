# ShareFile2eDoc

## Installation

```sh
composer install --no-dev --optimize-autoloader
bin/console doctrine:migrations:migrate --no-interaction
```

## Usage

To archive content from ShareFile to eDoc, run a command like this:

```sh
bin/console app:sharefile2edoc:archive «archiver»
```

Add `-vv`, e.g. `bin/console app:sharefile2edoc:archive «archiver» -vv` to get
information on what's going on.
