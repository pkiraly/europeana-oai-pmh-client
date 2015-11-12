<?php
define('LN', "\n");
define('FILE_NAME_TEMPLATE', 'europeana-oai-pmh-import-%08d.json');
define('ID_PREFIX', 'http://data.europeana.eu/item/');
define('DAY',    60 * 60 * 24);
define('MONTH',  28 * DAY);
define('YEAR',  365 * DAY);

require_once('../oai-pmh-lib/OAIHarvester.php');
require_once('config.php');

$total = 0;

$times = array(
  't0' => microtime(TRUE),
  't1' => microtime(TRUE),
);
$params = array('metadataPrefix' => 'edm');
do {
  $outName = sprintf(FILE_NAME_TEMPLATE, $total);
  $out = fopen($configuration['output_dir'] . $outName, "w");
  $harvester = new OAIHarvester('ListRecords', 'http://oai.europeana.eu/oaicat/OAIHandler', $params);
  $harvester->setAuthentication($configuration['username'], $configuration['password']);
  $times['fetch'] = microtime(TRUE);
  $harvester->fetchContent();
  $times['fetch'] = (microtime(TRUE) - $times['fetch']);
  $harvester->processContent();
  $currentRecordCount = 0;
  while (($record = $harvester->getNextRecord()) != null) {
    $currentRecordCount++;
    $metadata = processRecord($record);
    if (!empty($metadata)) {
      fwrite($out, json_encode($metadata) . LN);
    }
  }
  $doNext = false;
  $token = array();
  if ($harvester->hasResumptionToken()) {
    $token = $harvester->getResumptionToken();
    if (isset($token['text'])) {
      $total += $harvester->getRecordCount();
      $params = array('resumptionToken' => $token['text']);
      $doNext = true;
    }
  }
  printReport($token, $currentRecordCount);
} while ($doNext);

/**
 * Processing an individual record
 */
function processRecord($record) {
  $metadata = array();
  $isDeleted = (isset($record['header']['@status']) && $record['header']['@status'] == 'deleted');
  $id = $record['header']['identifier'];
  if (!$isDeleted) {
    $metadata = dom_to_array($record['metadata']['childNode']);
    $metadata['qIdentifier'] = $record['header']['identifier'];
    $metadata['identifier'] = str_replace(ID_PREFIX, '', $record['header']['identifier']);
    $metadata['sets'] = $record['header']['setSpec'];
  }
  return $metadata;
}

/**
 * Transform DOM object to an array
 */
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

/**
 * Prints a one line summary
 */
function printReport($token, $currentRecordCount) {
  global $times;

  $t2 = microtime(TRUE);
  printf("harvested records: %8d / total records: %d / last request took: %.3fs (fetch: %.3fs) / total: %s / resumptionToken: %s\n",
    (isset($token['attributes']['cursor']) ? $token['attributes']['cursor'] : $currentRecordCount),
    $token['attributes']['completeListSize'],
    ($t2 - $times['t1']),
    $times['fetch'],
    formatInterval((int)($t2 - $times['t0'])),
    $token['text']
  );
  $times['t1'] = $t2;
}

/**
 * This class is a copy of glavic's code available at http://php.net/manual/en/dateinterval.format.php#113204
 */
class DateIntervalEnhanced extends DateInterval {
  public function recalculate() {
    $from = new DateTime;
    $to = clone $from;
    $to = $to->add($this);
    $diff = $from->diff($to);
    foreach ($diff as $k => $v) {
      $this->$k = $v;
    }
    return $this;
  }
}

/**
 * Format timespan in seconds as hour:min:sec format. If it is more than a day or month or year, id adds day, month, year info as well.
 */
function formatInterval($timespan) {
  $interval = new DateIntervalEnhanced('PT' . $timespan . 'S');
  if ($timespan < DAY) {
    $format = '%H:%I:%S';
  } elseif ($timespan < MONTH) {
    $format = '%Dd %H:%I:%S';
  } elseif ($timespan < YEAR) {
    $format = '%Mm %Dd %H:%I:%S';
  } else {
    $format = '%yy %Mm %Dd %H:%I:%S';
  }
  return $interval->recalculate()->format($format);
}