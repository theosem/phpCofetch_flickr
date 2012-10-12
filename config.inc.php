<?php

/*
 * configuration file
	
	--->you need to register an API key for flickr
	
	Regitered under: USERNAME
	App name: XXXXXX  
	Key: XXXXXXXXXXXXXXXXXXXXX
	Secret: XXXXXXXXXXXXXXXXXX
	
 */

$cfg['useAPI'] = 'flickr';  // flickr or bing
$cfg['flickrAPIKey'] = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXX';  //put here the API key
$cfg['bingAPIKey'] = '-';
// text file with queries, 1 per line
$cfg['queriesFile'] = 'data/queries.txt';
//enable this to get recently uploaded content
$cfg['getRecent'] = false;
// save RUCOD and RWML files
$cfg['saveRUCoD'] = false;
$cfg['templateRUCoD'] = 'data/templateRUCoD.xml';
// overwrite files
$cfg['overwriteFiles'] = false;
// sleep seconds after a query finished
// used to minimize QS - queries per second limitations
$cfg['sleepAfterQuery'] = 40;
// defaults 100. The maximum allowed value is 500.
$cfg['imagesPerPage'] = 500;
$cfg['imageLimit'] = "all";
//$cfg['imageLimit'] = 10000;
//directory to use for caching (temp)
$cfg['cacheDirectory'] = 'data/tmp/';
// where to save directories with images - 1 directory for each query
$cfg['imagesDirectory'] = 'data/images/';
// where to save directories with metadata - 1 directory for each query
$cfg['metadataDirectory'] = 'data/metadata/';
$cfg['dumpDirectory'] =  'data/dump/';
// where to save RUCoD (XML and RWML) files - 1 directory for each query
$cfg['RUCoDDirectory'] = 'data/RUCoD/';
//use mongoDB for storing results metadata
$cfg['useMongo']=false;
$cfg['mongoDBname'] = 'flickrRecent';

$cfg['startMonths']=array('January','April','July','October');
$cfg['endMonths']=array('March','June','September','December');
?>
