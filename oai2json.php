<?php
define('LN', "\n");
define('FILE_NAME_TEMPLATE', 'europeana-oai-pmh-import-%08d.json');
define('ID_PREFIX', 'http://data.europeana.eu/item/');

require_once('../oai-pmh-lib/OAIHarvester.php');
require_once('config.php');

$total = 0;
$outName = sprintf(FILE_NAME_TEMPLATE, 0);
$out = fopen($configuration['output_dir'] . $outName, "w");

$t0 = $t1 = microtime(TRUE);
$params = array('metadataPrefix' => 'edm');
do {
  $harvester = new OAIHarvester('ListRecords', 'http://oai.europeana.eu/oaicat/OAIHandler', $params);
  $harvester->setAuthentication($configuration['username'], $configuration['password']);
  $harvester->fetchContent();
  $harvester->processContent();
  while (($record = $harvester->getNextRecord()) != null) {
  	$isDeleted = (isset($record['header']['@status']) && $record['header']['@status'] == 'deleted');
  	$id = $record['header']['identifier'];
  	if (!$isDeleted) {
      $metadata = dom_to_array($record['metadata']['childNode']);
      $metadata['qIdentifier'] = $record['header']['identifier'];
      $metadata['identifier'] = str_replace(ID_PREFIX, '', $record['header']['identifier']);
      $metadata['sets'] = $record['header']['setSpec'];
      fwrite($out, json_encode($metadata) . LN);
    }
  }
  $doNext = false;
  if ($harvester->hasResumptionToken()) {
    $token = $harvester->getResumptionToken();
    if (isset($token['text'])) {
      $t2 = microtime(TRUE);
      printf("harvested records: %8d / total records: %d / last request took: %.3fs / total: %.3fs\n", $token['attributes']['cursor'], $token['attributes']['completeListSize'], ($t2 - $t1), ($t2 - $t0));
      $t1 = $t2;
      $total += $harvester->getRecordCount();
      $params = array('resumptionToken' => $token['text']);
      $doNext = true;
      $outName = sprintf(FILE_NAME_TEMPLATE, $total);
      $out = fopen($configuration['output_dir'] . $outName, "w");
    }
  }
} while ($doNext);

function dom_to_array($node, $parent_name = NULL) {
  $name = $node->nodeName;
  $metadata = array();

  // copy attributes
  foreach ($node->attributes as $attr) {
    $metadata['@' . $attr->name] = $attr->value;
  }

  // process children
  foreach ($node->childNodes as $child) {
    // process children elements
    if ($child->nodeType == XML_ELEMENT_NODE) {
      if ($node->childNodes->length == 1) {
        $metadata[$child->nodeName] = dom_to_array($child, $name);
      } else {
        $metadata[$child->nodeName][] = dom_to_array($child, $name);
      }
    }
    // copy text value
    elseif ($child->nodeType == XML_TEXT_NODE) {
      $value = trim($child->nodeValue);
      if (!empty($value)) {
        $metadata['#value'] = str_replace("\n", ' ', $value);
      }
    }
  }
  if ($parent_name !== NULL) {
    if (count($metadata) == 1 && isset($metadata['#value'])) {
      $metadata = $metadata['#value'];
    }
  }

  return $metadata;
}

