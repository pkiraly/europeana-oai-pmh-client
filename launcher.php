<?php
define('MAX_THREADS', 10);
define('SET_FILE_NAME', 'setlist.txt');

$threads = exec('ps aux | grep "[o]ai2json.php" | wc -l');
echo 'threads: ', $threads, "\n";
if ($threads >= MAX_THREADS) {
  exit();
}

if (filesize(SET_FILE_NAME) > 3) {
  $contents = file_get_contents(SET_FILE_NAME);
  $lines = explode("\n", $contents);
  $set = array_shift($lines);
  $contents = join("\n", $lines);
  file_put_contents('setlist.txt', $contents);
  echo 'set: ', $set, "\n";
  exec('nohup php oai2json.php --set="' . $set . '" >>oai2json-report.txt 2>>oai2json-report.txt &');
}
