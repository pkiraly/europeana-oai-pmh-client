<?php
$configuration = parse_ini_file('config.cfg');
$options = processOptions(getopt("s:i:", array('set:', 'id:')));
$harvestableIds = readHarvestableIds('harvestable/' . $options['id'] . '.txt');

foreach ($harvestableIds as $id) {
  $cmd = sprintf('php oai2json.php --id="%s" --set="%s"', $id, $options['set']);
  exec($cmd);
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
