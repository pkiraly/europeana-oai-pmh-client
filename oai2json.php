<?php
define('LN', "\n");
define('FILE_NAME_TEMPLATE', 'europeana-oai-pmh-import-%08d.json');
define('ID_PREFIX', 'http://data.europeana.eu/item/');
define('DAY',    60 * 60 * 24);
define('MONTH',  28 * DAY);
define('YEAR',  365 * DAY);
define('MAX_RETRY', 3);
// define('OAI_ENDPOINT', 'http://oai.europeana.eu/oaicat/OAIHandler');
// define('OAI_ENDPOINT', 'http://record-management.eanadev.org:8080/oaicat/OAIHandler');
define('OAI_ENDPOINT', 'http://oai.europeana.eu/oaicat/OAIHandler');

require_once('../oai-pmh-lib/OAIHarvester.php');
// require_once('config.php');
$configuration = parse_ini_file('config.cfg');

$options = processOptions(getopt("s::i::f::t::", array('set::', 'id::', 'file::', 'split::')));

$total = 0;
$times = array(
  't0' => microtime(TRUE),
  't1' => microtime(TRUE),
);

$params = array('metadataPrefix' => 'edm');
if (isset($options['set'])) {
  $params['set'] = $options['set'];
  define('SET_BASED_FILE_NAME_TEMPLATE', $options['set'] . '/%08d.json');
  $dir = $configuration['OUTPUT_DIR'] . '/' . $options['set'];
  if (!file_exists($dir)) {
    if (!mkdir($dir)) {
      die('Failed to create directory: ' . $dir);
    }
  }
}

$verb = 'ListRecords';
if (isset($options['id'])) {
  $options['set'] = urldecode($options['set']);
  $params['identifier'] = (strpos($options['id'], ID_PREFIX) !== FALSE) ? $options['id'] : ID_PREFIX . $options['id'];
  unset($params['set']);
  define('ID_BASED_FILE_NAME_TEMPLATE', $options['set'] . '/individuals-' . $options['split'] . '.json');
  // $out = fopen($configuration['OUTPUT_DIR'] . getOutputFileName($total), "a+");
  $verb = 'GetRecord';
}

do {
  $harvester = new OAIHarvester($verb, OAI_ENDPOINT, $params);
  $harvester->setAuthentication($configuration['username'], $configuration['password']);
  $harvester->setDebugInfo($options['set']);
  $times['fetch'] = microtime(TRUE);

  $retry = 0;
  printf("%s - %s - fetch content\n", date('H:i:s'), $options['set']);
  do {
    $harvester->fetchContent();
    if ($retry > 0)
      printf("%s - %s - Needs retrying at %d (%d)\n", date('H:i:s'), $options['set'], $total, $retry);
    $fetchOk = checkFetchState($harvester, $total, $retry);
    printf("%s - %s - fetchOK: %s\n", date('H:i:s'), $options['set'], json_encode($fetchOk));
  } while ($fetchOk === FALSE && ++$retry <= MAX_RETRY);
  $times['fetch'] = (microtime(TRUE) - $times['fetch']);
  printf("%s - %s - fetched\n", date('H:i:s'), $options['set']);

  $currentRecordCount = 0;
  if ($fetchOk) {
    $outFileName = $configuration['OUTPUT_DIR'] . getOutputFileName($total);
    printf("%s - Outfile: %s\n", date('H:i:s'), $outFileName);
    $out = isset($options['id']) ? fopen($outFileName, "a+") : fopen($outFileName, "w");
    if ($out === FALSE || !is_resource($out)) {
      die(sprintf("%s - Unable to open file %s", date('H:i:s'), $outFileName));
    }
    $harvester->processContent();
    while (($record = $harvester->getNextRecord()) != null) {
      $currentRecordCount++;
      $metadata = processRecord($record);
      if (!empty($metadata)) {
        fwrite($out, json_encode($metadata) . LN);
      }
    }
    fclose($out);
    exec("gzip $outFileName");
  }
  $doNext = false;
  $token = array();
  if (!$fetchOk) {
    $status = 'broken with wrong HTTP response';
  } else {
    if (!$harvester->hasResumptionToken()) {
      $status = 'finished';
    } else {
      $token = $harvester->getResumptionToken();
      if (!isset($token['text'])) {
        $status = 'broken with wrong token: ' . json_encode($token);
      } else {
        if ($harvester->getRecordCount() == -1) {
          $status = 'broken with empty response';
        } else {
          $total += $harvester->getRecordCount();
          $params = array('resumptionToken' => $token['text']);
          $doNext = true;
          $status = 'to be continued';
        }
      }
    }
  }
  printReport($token, $currentRecordCount);
} while ($doNext);

echo $harvester->getRequestUrl(), LN;
printf("%s - Finished %s STATUS %s\n", date('H:i:s'), (isset($options['set']) ? ' SET: ' . $options['set'] : ''), $status);


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

function checkFetchState($harvester, $total, $retry) {
  global $options;

  // print 'URL: ' . $harvester->getRequestUrl() . LN;
  if ($harvester->getHttpCode() == 200 && $harvester->getContentType() == 'text/xml;charset=UTF-8') {
    $fetchOk = TRUE;
  } else {
    $content = 'URL:' . LN . $harvester->getRequestUrl() . LN . LN;
    $content .= 'REQUEST HEADER:' . LN . $harvester->getRequestHeader() . LN . LN;
    $content .= 'RESPONSE HEADER:' . LN . $harvester->getHttpHeader() . LN . LN;
    $content .= 'CONTENT:' . LN . $harvester->getContent();
    if (isset($options['set'])) {
      $fileName = sprintf('errors/%s-%d-%d.txt', $options['set'], $total, $retry);
    } else {
      $fileName = sprintf('errors/%d-%d.txt', $total, $retry);
    }
    file_put_contents($fileName, $content);
    $fetchOk = FALSE;
  }
  return $fetchOk;
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
  global $times, $harvester, $options;

  $cursor = isset($token['attributes']['cursor']) ? $token['attributes']['cursor'] : $currentRecordCount;
  $completeListSize = isset($token['attributes']['completeListSize']) ? $token['attributes']['completeListSize'] : 0;
  $t2 = microtime(TRUE);
  $report = sprintf("%s - harvested records: %8d / total records: %d / last request took: %.1fs (fetch: %.1fs) / total: %s / token: %s",
    date('H:i:s'),
    $cursor,
    $completeListSize,
    ($t2 - $times['t1']),
    $times['fetch'],
    formatInterval((int)($t2 - $times['t0'])),
    (isset($token['text']) ? $token['text'] : 'unknown')
  );

  if (isset($options['set'])) {
    $report .= sprintf(" / set %s", $options['set']);
  }

  $report .= sprintf(" / HTTP response %d %s", $harvester->getHttpCode(), $harvester->getContentType());
  echo $report, LN;
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

function getOutputFileName($total) {
  if (defined('ID_BASED_FILE_NAME_TEMPLATE')) {
    $fileNameTpl = ID_BASED_FILE_NAME_TEMPLATE;
  } else if (defined('SET_BASED_FILE_NAME_TEMPLATE')) {
    $fileNameTpl = SET_BASED_FILE_NAME_TEMPLATE;
  } else {
    $fileNameTpl = FILE_NAME_TEMPLATE;
  }
  // echo 'file name template: ', $fileNameTpl, LN;
  return sprintf($fileNameTpl, $total);
}

function processOptions($input) {
  $options = array();
  processOption($options, $input, 'set', 's', TRUE);
  processOption($options, $input, 'id', 'i');
  processOption($options, $input, 'file', 'f');
  processOption($options, $input, 'split', 't');
  return $options;
}

function processOption(&$options, $input, $long, $short, $encode = FALSE) {
  if (isset($input[$long]) && !empty($input[$long])) {
    $options[$long] = $input[$long];
  }
  if (!isset($options[$long]) && isset($input[$short]) && !empty($input[$short])) {
    $options[$long] = $input[$short];
  }
  if (isset($options[$long]) && $encode)
    $options[$long] = urlencode($options[$long]);
}
