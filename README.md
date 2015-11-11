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
| `output_dir` | The directory where the script will save the files |

Edit config.php accordingly. Right now Europeana OAI-PMH server works with Basic HTTP authentication, so enter your 
username and password, and enter the directory you want to save the result files into.

## Run

You can try whether it is running, and produces the right output via

    $ php oai2json.php

The output it produces is something like this:

    harvested records:     1000 / total records: 0 / last request took: 67.386s / total: 67.386s
    harvested records:     2000 / total records: 0 / last request took: 15.162s / total: 82.548s
    harvested records:     3000 / total records: 0 / last request took: 14.849s / total: 97.397s
    harvested records:     4000 / total records: 0 / last request took: 15.412s / total: 112.810s
    harvested records:     5000 / total records: 0 / last request took: 14.651s / total: 127.461s
    harvested records:     6000 / total records: 0 / last request took: 12.919s / total: 140.380s

This log tells you how many records were harvested so far, how much each request took, and also the total time taken so far. Unfortunatelly the Europeana service doesn't implemented the optional `cursor` attibute, which would tell you the number of total records (I hope it will be implemented soon). At the time of writing this there are 44 725 946 records available via the Europeana API, I guess OAI-PMH server contains the very same number of records.

Since it will take very long time I suggest to run in the background and use nohup, which lets you to log out and back in from and to the machine during the process.

    $ nohup php oai2json.php &

It will create a file called `nohup.out` in the same directory, and from time to time you can check where the process is going.

Have fun!

note: This script is part of my [Data Quality Assurance Framework](http://pkiraly.github.io) project.
