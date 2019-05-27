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
# /etc/supervisor/conf.d/symfony_hoeringsportal_documents.conf
[program:symfony_hoeringsportal_documents]
command = /data/www/hoeringsportal-documents/htdocs/bin/console messenger:consume amqp
environment=APP_ENV=prod
numprocs = 1
autostart = true
autorestart = true
stderr_logfile=/data/www/hoeringsportal-documents/htdocs/var/log/symfony_hoeringsportal_documents.err.log
stdout_logfile=/data/www/hoeringsportal-documents/htdocs/var/log/symfony_hoeringsportal_documents.out.log
```

```sh
supervisorctl reload
supervisorctl restart symfony_hoeringsportal_documents
```


# Docker

```sh
docker-compose pull
docker-compose up -d
```

```sh
sudo sh -c 'echo "0.0.0.0 hoeringsportal-documents.docker.localhost" >> /etc/hosts'
```

Install additional requirements:

```sh
docker-compose exec phpfpm /app/.docker/scripts/setup
```

Install `composer` stuff:

```sh
docker-compose exec phpfpm composer install
```

Create super admin:

```sh
docker-compose exec phpfpm bin/console doctrine:migrations:migrate --no-interaction
docker-compose exec phpfpm bin/console fos:user:create --super-admin super-admin super-admin@example.com password
```

Open the site in default browser:

```sh
open "http://hoeringsportal-documents.docker.localhost:$(docker-compose port reverse-proxy 80 | cut -d: -f2)"
```

Create an “Archiver” of type “pdfcombine” with a configuration similar to this:

```yaml
type: combinepdf

sharefile:
 hostname: «account».sharefile.com
 client_id: «ShareFile client id»
 secret: «ShareFile secret»
 username: «ShareFile api username»
 password: «ShareFile api password»
 root_id: «ShareFile root id»
```
