#!/bin/bash

DIR=$(dirname $(readlink -f $0))
source $DIR/config.cfg

echo '<!DOCTYPE html>'
echo '<html lan="en">'
echo '<head>'
echo '  <meta charset="utf-8">'
echo '  <meta http-equiv="refresh" content="60">'
echo '  <title>Europeana OAI-PMH harvester progress report</title>'
echo '  <link rel="stylesheet" href="http://pkiraly.github.io/css/bootstrap.min.css">'
echo '  <link rel="stylesheet" href="http://pkiraly.github.io/css/stylesheet.css">'
echo '  <link rel="stylesheet" href="http://pkiraly.github.io/css/syntax.css">'
echo '  <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">'
echo '  <link href="https://fonts.googleapis.com/css?family=Lora:400,400italic,700,700italic&amp;subset=latin,latin-ext" rel="stylesheet" type="text/css">'
echo '  <link href="https://fonts.googleapis.com/css?family=Viga&amp;subset=latin,latin-ext" rel="stylesheet" type="text/css">'
echo '<style type="text/css">'
echo '.post-content { max-width: 1200px; }'
echo 'p { margin: 5px 0; }'
echo 'td { font-size: 67% }'
echo '</style>'
echo '</head>'
echo '<body>'

echo '<section id="container"><div class="post-content"><article>'
echo '<h1>Europeana OAI-PMH Harvester<br/>progress report</h1>'
echo '<p>[status at' `date +"%Y-%m-%d %H:%M"`']</p>'
echo '<p>This report shows you some information about the ongoing harvesting which should harvest approximatelly 45 million records. Find more about the project: <a href="https://pkiraly.github.io/">Metadata Quality Assurance Framework</a>, and about the harvester: <a href="https://github.com/pkiraly/europeana-oai-pmh-client">here</a>.</p>'

echo '<h2>harvester log</h2>'
echo '<p>The 10 latest harvest requests.</p>'
echo '<pre>'
tail -50 $DIR/oai2json-report.txt
echo '</pre>'

echo '<h2>launcher log</h2>'
echo '<p>The 10 latest launched harvester requests.</p>'
echo '<pre>'
tail $DIR/launcher-report.txt
echo '</pre>'

echo '<h2>running harvesters</h2>'
echo '<p>The 10 latest launched harvester requests.</p>'
echo '<table class="table table-stripped">'
echo '<thead>'
echo '<tr>'
echo '  <th>PID</th>'
echo '  <th>status</th>'
echo '  <th>last action</th>'
echo '</tr>'
echo '</thead>'
echo '<tbody>'

RUNNERS=$(ps aux | grep "[o]ai2json.php" | sed 's, ,#,g')

for runner in $RUNNERS
do
  pid=$(echo $runner | sed 's,#, ,g' | awk '{print $2}')
  status=$(echo $runner | sed 's,#, ,g' | awk '{print $9 " " $10 " " $13}')
  dir=$(echo $runner | sed 's,#, ,g' | awk '{print $13}' | sed 's/--set=//')

  # last=$(grep $dir $DIR/oai2json-report.txt | tail -1)
  # short=$(echo $last | sed 's, [^ ]*/europeana-oai-pmh-2017/[^/]*/, ,' | sed "s, *SET: $dir,," | sed 's, STATUS broken with empty response,,')
  short=$(ls -la $OUTPUT_DIR/$dir | tail -1)

  echo "<tr>"
  echo "<td>$pid</td>"
  echo "<td>$status</td>"
  echo "<td>$short</td>"
  echo "</tr>"
done
echo '</tbody>'
echo '</table>'
#echo '</pre>'

#echo '<pre>'
#ps aux | grep "[o]ai2json.php" | awk '{print $9 " " $10 " " $11 " " $12 " " $13}'
#echo '</pre>'

echo '<h2>Number of remaining sets</h2>'
echo '<pre>'
wc -l $DIR/setlist.txt | awk '{print $1}'
echo '</pre>'

echo '<h2>file size</h2>'
echo '<p>The occupied disk space at the data directory.</p>'
echo '<pre>'
du -h $OUTPUT_DIR | sort -k2 | sed "s:$OUTPUT_DIR::"
echo '</pre>'

#export DATA=~/data/europeana-oai-pmh-2017

echo '<h2>file size</h2>'
echo '<p>The occupied disk space at the data directory.</p>'
echo '<table class="table table-striped">'
echo '<thead>'
echo "<tr><th>size</th><th>directory</th><th>last action</th></tr>"
echo '</thead>'
echo '<tbody>'
QDIRS=$OUTPUT_DIR/*

for qdir in $QDIRS
do
  du=$(du -h $qdir | awk '{print $1}')
  dir=$(echo $qdir | sed 's,^.*/europeana-oai-pmh-2017/,,' | sed 's,/,,')
  last=$(grep $dir $DIR/oai2json-report.txt | tail -1)
  short=$(echo $last | sed 's, [^ ]*/europeana-oai-pmh-2017/[^/]*/, ,' | sed "s, *SET: $dir,," | sed 's, STATUS broken with empty response,,')
  echo "<tr><td>$du</td><td>$dir</td><td>$short</td></tr>"
done
echo '</tbody>'
echo '</table>'


echo '<h2>number of files</h2>'
echo '<p>The number of JSON files in the data directory.</p>'
echo '<pre>'
ls -lR $OUTPUT_DIR/ | grep .json | wc -l
echo '</pre>'

echo '<h2>incorrect responses</h2>'
echo '<p>The responses which did not return HTTP 200 code and JSON content. They usually contain Tomcat error page which might or might not inform about the type of error.</p>'
echo '<pre>'
ls -la $DIR/errors/*.txt | awk '{print $5 " " $6 " " $7 " " $8 " " $9}' | sed "s:$DIR/errors/::"
echo '</pre>'

echo '<h2>disk space</h2>'
echo '<p>System disk space report.</p>'
echo '<pre>'
df -h | egrep "(Avail|vda)"
echo '</pre>'

# echo '<h2>Finished sets</h2>'
# echo '<pre>'
# # grep "Finished SET" $DIR/oai2json-report.txt | sed 's/^[^:]*: //' | sed 's/\(STATUS\)/--- \1/'
# grep "Finished" $DIR/oai2json-report.txt | sed 's/Finished *SET: //' | sed 's/\(STATUS\)/--- \1/'
# echo '</pre>'

echo '</article></div></section>'
echo '</body>'
echo '</html>'
