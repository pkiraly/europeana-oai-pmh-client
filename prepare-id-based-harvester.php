<?php

$harvestables = json_decode(file_get_contents("harvestable.json"));
$runners = array();
foreach ($harvestables as $harvestable) {
  if ($harvestable->sitemap != $harvestable->oai) {
    $runners[] = sprintf("%s\t%s", $harvestable->name, $harvestable->id);
  }
}
file_put_contents("id-based-harvester-list.txt", join($runners, "\n"));
