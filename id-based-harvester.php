<?php
define('ID_PREFIX', 'http://data.europeana.eu/item/');

$configuration = parse_ini_file('config.cfg');
$options = processOptions(getopt("s:i:t::", array('set:', 'id:', 'split::')));
$options['set'] = urldecode($options['set']);
if (isset($options['split'])) {
  $fileName = 'nulls/' . $options['id'] . '-' . $options['split'] . '.txt';
} else {
  $fileName = 'harvestable/' . $options['id'] . '.txt';
}
$harvestableIds = readHarvestableIds($fileName);

$total = count($harvestableIds);
printf("%s BEGAN set %s/%s (%d records)\n", date('H:i:s'), $options['set'], $options['split'], $total);
$counter = 0;
$milestone = 0;
foreach ($harvestableIds as $id => $value) {
  $counter++;
  $percentage = ($counter * 100 / $total);
  if ($percentage > ($milestone * 10)) {
    printf("%s %.1f%% %s/%s\n", date('H:i:s'), $percentage, $options['set'], $options['split']);
    $milestone++;
  }
  $cmd = sprintf("php oai2json.php --id='%s' --set='%s' --split='%s'", $id, $options['set'], $options['split']);
  // echo $cmd, "\n";
  exec($cmd);
}
printf("%s FINISHED set %s/%s\n", date('H:i:s'), $options['set'], $options['split']);

function processOptions($input) {
  $options = array();
  processOption($options, $input, 'set', 's');
  processOption($options, $input, 'id', 'i');
  processOption($options, $input, 'split', 't');
  return $options;
}

function processOption(&$options, $input, $long, $short) {
  if (isset($input[$long]) && !empty($input[$long])) {
    $options[$long] = urlencode($input[$long]);
  }
  if (!isset($options[$long]) && isset($input[$short]) && !empty($input[$short])) {
    $options[$long] = urlencode($input[$short]);
  }
}

function readHarvestableIds($file) {
  $ids = array();
  $handle = fopen($file, "r");
  if ($handle) {
    while (($line = fgets($handle)) !== FALSE) {
      $id = rtrim($line);
      $id = str_replace(ID_PREFIX, '', $id);
      $ids[$id] = 1;
    }
  } else {
    die("Can't open $file");
  }
  fclose($handle);
  return $ids;
}
