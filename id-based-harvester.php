<?php
$configuration = parse_ini_file('config.cfg');
$options = processOptions(getopt("s:i:", array('set:', 'id:')));
$harvestableIds = readHarvestableIds('harvestable/' . $options['id'] . '.txt');

foreach ($harvestableIds as $id) {
  $cmd = sprintf('php oai2json.php --id="%s" --set="%s"', $id, $options['set']);
  exec($cmd);
}

function processOptions($input) {
  $options = array();
  processOption($options, $input, 'set', 's');
  processOption($options, $input, 'id', 'i');
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
      $ids[rtrim($line)] = 1;
    }
  } else {
    die("Can't open $file");
  }
  fclose($handle);
  return $ids;
}
