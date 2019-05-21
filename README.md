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


## Combine PDF

```sh
bin/console -vvv app:pdf:combine get-data hearing-id --archiver=archiver-id # Get data from ShareFile
bin/console -vvv app:pdf:combine combine hearing-id  # Build combined pdf
bin/console -vvv app:pdf:combine share hearing-id    # Upload combined pdf to ShareFile
```

All-in-one:

```sh
bin/console -vvv app:pdf:combine run hearing-id --archiver=archiver-id # Archive combined pdf in eDoc
```

If an archiver is not specified, the first default archiver will be used.

### Cron job

Run this command regularly to combine PDFs from finished hearings:

```sh
bin/console  -vvv app:pdf:cron archiver-id
```
