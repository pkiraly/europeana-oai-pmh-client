<?php
define('URL', 'http://www.europeana.eu/api/v2/search.json?wskey=api2demo'
            . '&query=europeana_collectionName:%%22%s%%22&rows=100&cursor=%s&profile=minimal');
define('URL_TRUNCATED', 'http://www.europeana.eu/api/v2/search.json?wskey=api2demo'
            . '&query=europeana_collectionName:%s&rows=100&cursor=%s&profile=minimal');

$set = urlencode($argv[1]);
$fileName = 'harvestable/' . strstr($set, '_', true) . '.txt';
$out = fopen($fileName, "w+");
$rows = 100;
$cursor = '*';
$start = 1;
$total = -1;
do {
  $template = substr($set, -3) == '%2A' ? URL_TRUNCATED : URL;
  $url = sprintf($template, $set, $cursor);
  // printf("URL: %s\n", $url);
  $response = json_decode(file_get_contents($url));
  if ($total == -1)
    $total = $response->totalResults;
  if (isset($response->items)) {
    foreach ($response->items as $record) {
      fwrite($out, sprintf("%s\n", substr($record->id, 1)));
    }
    $start += $rows;
  }
  if (isset($response->nextCursor))
    $cursor = $response->nextCursor;
  printf("%d vs %d -- %d %s\n", $start, $total, isset($response->nextCursor), $cursor);
} while (isset($response->nextCursor));
fclose($out);
