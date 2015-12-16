<?php
define('MAX_THREADS', 10);
define('SET_FILE_NAME', 'id-based-harvester-list.txt');

$threads = exec('ps aux | grep "[i]d-based-harvester.php" | wc -l');
# echo 'threads: ', $threads, "\n";
if ($threads >= MAX_THREADS) {
  exit();
}

if (filesize(SET_FILE_NAME) > 3) {
  $contents = file_get_contents(SET_FILE_NAME);
  $lines = explode("\n", $contents);
  $line = array_shift($lines);
  $contents = join("\n", $lines);
  file_put_contents('setlist.txt', $contents);

  list($set, $id) = explode("\t", $line);
  printf("%s launching set: %s\n", date("Y-m-d H:i:s"), $set);
  exec('nohup php id-based-harvester.php --set="' . $set . '" --id="' . $id . '">>oai2json-report.txt 2>>oai2json-report.txt &');
}
