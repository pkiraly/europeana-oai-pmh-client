# Harvester for Europeana OAI-PMH Service

This PHP script downloads all Europeana records via OAI-PMH protocol. The result are a number of files (named as `europeana-oai-pmh-import-dddddddd.json`), each contain 1000 records, one record per line in JSON format.
Be aware: a full harvest takes more than a week and resulted 250+ GB content.
More about the service see http://labs.europeana.eu/api/oai-pmh-introduction.

## Requirements

* PHP5
* php5-curl library
* https://github.com/pkiraly/oai-pmh-lib

## Configuration

Before the first run:

    cp config.sample.php config.php

There are three configuration settings:

| name     | description |
| ---      | ---         |
| `username` | User name requested from Europeana |
| `password` | Password given by Europeana |
| `output_dir` | The directory where the script should save the JSON files |

Edit config.php accordingly. Right now Europeana OAI-PMH server works with Basic HTTP authentication, so enter your 
username and password, and enter the directory you want to save the result files into.

## Run

You can try whether it is running, and produces the right output via

    $ php oai2json.php

The output it produces is something like this:

    harvested records:     1000 / total records: 0 / last request took: 12.686s (fetch: 11.765s) / total: 00:00:12 / token: HOQKN_228 / HTTP response 200 text/xml;charset=UTF-8
    harvested records:     2000 / total records: 0 / last request took: 14.294s (fetch: 12.776s) / total: 00:00:26 / token: HOQKN_228 / HTTP response 200 text/xml;charset=UTF-8
    harvested records:     3000 / total records: 0 / last request took: 14.292s (fetch: 12.865s) / total: 00:00:41 / token: HOQKN_228 / HTTP response 200 text/xml;charset=UTF-8
    harvested records:     4000 / total records: 0 / last request took: 13.723s (fetch: 12.466s) / total: 00:00:54 / token: HOQKN_228 / HTTP response 200 text/xml;charset=UTF-8
    harvested records:     5000 / total records: 0 / last request took: 14.050s (fetch: 12.554s) / total: 00:01:09 / token: HOQKN_228 / HTTP response 200 text/xml;charset=UTF-8
    harvested records:     6000 / total records: 0 / last request took: 12.641s (fetch: 11.724s) / total: 00:01:21 / token: HOQKN_228 / HTTP response 200 text/xml;charset=UTF-8

This log tells you how many records were harvested so far, how much each request took (the fetch part is the HTTP request without any client-side processing phase), and also the total time taken so far. It also gives you the resumptionToken, which would be useful if the process brokes somewehere and you would like to continue it without restarting. Unfortunatelly the Europeana service doesn't implemented the optional `cursor` attibute, which would tell you the number of total records (I hope it will be implemented soon). At the time of writing this there are 44 725 946 records available via the Europeana API, I guess OAI-PMH server contains the very same number of records.

Since it will take very long time I suggest to run in the background and use nohup, which lets you to log out and back in from and to the machine during the process.

    $ nohup php oai2json.php &

It will create a file called `nohup.out` in the same directory, and from time to time you can check where the process is going.

## Error handling

As mentioned in the service's web page the OAI-PMH service is in beta varsion. For daily practice it means that from time to time the server doesn't behave perfectly, and returns Tomcat (that's the Java application container in which the service works) error messages. Those messages are not well formed XML and HTTP 200 responses and usually reflects on some temporary issues, the client resends the original request maximum three times, hoping that meantime those issues have gone. The request and response messages for this retry cycles are stored in the `errors` directory. If you find such errors, please report them to the Europeana stuff those might be useful in debugging.

## Status reports

The process takes long time, and during that time I am curious what happens right now, how the process is going, so I attached a simple bash script which collects the following information:

* the last 10 messages in the nohup.out file
* occupied disk space at the data directory
* number of JSON files in the data directory
* the "error" files, which contains the information about the responses which did not return HTTP 200 code and JSON content
* System disk space

I just setup a cron job, which runs the script in every minute, and output the report into a file in the machine's web server:

    $ crontab -l
    $ */1 * * * * /path/to/europeana-oai-pmh-client/report-html.sh > /var/www/html/report.html

The report requires a confg file as well, so efore the first run:

    $ cp config.sample.cfg config.cfg
    $ nano config.cfg

There is only one value (now) you have to set up: `DATA_DIR`, which is the same as for the php script's `output_dir`, The directory where the script should save the JSON files.

Have fun!

note: This script is part of my [Metadata Quality Assurance Framework](http://pkiraly.github.io) project.
