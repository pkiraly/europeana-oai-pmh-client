<?php
define('MAX_THREADS', 1);
define('SET_FILE_NAME', 'setlist.txt');

# echo 'threads: ', $threads, "\n";
# if ($threads >= MAX_THREADS) {
#   exit();
# }

$endTime = time() + 60;
$i = 1;
while (time() < $endTime) {
  $threads = exec('ps aux | grep "[o]ai2json.php" | wc -l');
  # $threads = exec('ps aux | grep "[=]' . $Rfile . '" | wc -l');
  # echo 'threads: ', $threads, "\n";
  if ($threads < MAX_THREADS) {
    if (filesize(SET_FILE_NAME) > 3) {
      launch_threads($threads);
    }
  }
  sleep(2);
}

function launch_threads($running_threads) {

  if (filesize(SET_FILE_NAME) > 3) {
    $contents = file_get_contents(SET_FILE_NAME);
    $lines = explode("\n", $contents);
    # $set = array_shift($lines);
    $sets = [];
    $slots = MAX_THREADS - $running_threads;
    for ($i = 1; $i <= $slots; $i++) {
      if (count($lines) > 0) {
        $set = array_shift($lines);
        if ($set != "") {
          $sets[] = $set;
        }
      }
    }
    printf("Running threads: %d, slots: %d, new files: %d\n", $running_threads, $slots, count($files));
    $contents = join("\n", $lines);
    file_put_contents('setlist.txt', $contents);

    foreach ($sets as $set) {
      printf("%s launching set: %s\n", date("Y-m-d H:i:s"), $set);
      exec('nohup php oai2json.php --set="' . $set . '" >>oai2json-report.txt 2>>oai2json-report.txt &');
    }
  }
}
