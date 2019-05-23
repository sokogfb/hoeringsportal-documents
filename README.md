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

## Manually building combined pdf

Make sure that `rabbitmq-server` is running.

Run

```sh
bin/console messenger:consume amqp -vvv
```

to process the messages. Use `supervisor` or something similar to keep this
process running, e.g.:

```
# /etc/supervisor/conf.d/symfony_sync_files.conf
[program:symfony_sync_files]
command = /data/www/sync-files/htdocs/bin/console messenger:consume amqp
environment=APP_ENV=prod
numprocs = 1
autostart = true
autorestart = true
stderr_logfile=/data/www/sync-files/htdocs/var/log/symfony_sync_files.err.log
stdout_logfile=/data/www/sync-files/htdocs/var/log/symfony_sync_files.out.log
```

```sh
supervisorctl reload
supervisorctl restart symfony_sync_files
```
