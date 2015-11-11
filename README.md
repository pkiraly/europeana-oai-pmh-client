# europeana-oai-pmh-client

Requirements

* PHP
* php-curl library
* https://github.com/pkiraly/oai-pmh-lib

Configuration

Before the first run:

    cp config.sample.php config.php

Edit config.php accordingly. Right now Europeana OAI-PMH server works with authentication, so enter your 
username and password, and enter the directory you want to save the result files into.

Run

    php oai2json.php

Have fun!
