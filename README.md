# PHP based harvester for Europeana OAI-PMH Service

This script downloads all Europeana records via OAI-PMH protocol. The result are a number of files (named as europeana-oai-pmh-import-dddddddd.json), each contain one record per line in JSON format.
Be aware: a full harvest takes more than a week and resulted 250+ GB content.
More about the service see http://labs.europeana.eu/api/oai-pmh-introduction.

## Requirements

* PHP
* php-curl library
* https://github.com/pkiraly/oai-pmh-lib

## Configuration

Before the first run:

    cp config.sample.php config.php

There are three configuration settings:

| name     | description |
| ---      | ---         |
| username | User name requested from Europeana |
| password | Password given by Europeana |
| output_dir | The directory where the script will save the files |

Edit config.php accordingly. Right now Europeana OAI-PMH server works with Basic HTTP authentication, so enter your 
username and password, and enter the directory you want to save the result files into.

## Run

    php oai2json.php

Have fun!
