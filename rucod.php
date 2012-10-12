<?php

/**
 * @authors Thodoris Semertzidis, Stavroula Manolopoulou,George Litos
 *
 * create RUCoD files from image search engine queries
 *
 * see config.inc.php for configuration options *
 create RUCoD files from image search engine queries
 *
 * see config.inc.php for configuration options
 *
 * flick API
 * http://www.flickr.com/services/api/
 * bing API
 * http://msdn.microsoft.com/en-us/library/dd251056.aspx
 * 
 */
require_once 'config.inc.php';

$version = "0.2";
date_default_timezone_set('Europe/Athens');
set_time_limit(0);
ob_implicit_flush();
error_reporting(E_ALL ^ E_NOTICE);

echo "ImageSearch2RUCoD version ".$version."\n";

$content = file_get_contents($cfg['queriesFile']);
$queries = explode("\n", $content);

//print_r($queries);

if ($cfg['useAPI'] == 'flickr') {
  require_once 'phpFlickr-3.1/phpFlickr.php';
  $flickr = new phpFlickr($cfg['flickrAPIKey']);
  //default file cache age is 600sec
  $flickr->enableCache("fs", $cfg['cacheDirectory'], 60*60*24);
} else if ($cfg['useAPI'] == 'bing') {
//  $bing = new BingAPI($cfg['bingAPIKey']);
}

if($cfg['useMongo']){
try 
{
    $m = new Mongo(); // connect
    $db = $m->selectDB($cfg['mongoDBname']);
}
catch ( MongoConnectionException $e ) 
{
    echo '<p>Couldn\'t connect to mongodb, is the "mongo" process running?</p>';
    exit();
}

}

if($cfg['getRecent']){
echo "retrieving recently uploaded images on flickr\n";

$imagesDirectory = $cfg['imagesDirectory'] ."recent/";
 
 if (!file_exists($imagesDirectory))
    if (@mkdir($imagesDirectory, 0777, true) == false)
      echo "Error creating directory ".$imagesDirectory."\n";
	  
	  
$getRecent = $flickr->photos_getRecent("",array("description", "license", "date_upload", 
"date_taken", "owner_name", "icon_server", "original_format", "last_update", "geo", "tags", 
"machine_tags", "o_dims", "views", "media", "path_alias", "url_s", "url_q", "url_m", "url_z", "url_b"),500);

//print_r($getRecent);

$numPages = $getRecent["photos"]["pages"];
echo "number of pages: ".$numPages."\n";


$counter=0;
for($i=0; $i< $numPages; $i++){
	if($i > 0){
	$getRecent = $flickr->photos_getRecent("",array("description", "license", "date_upload", 
"date_taken", "owner_name", "icon_server", "original_format", "last_update", "geo", "tags", 
"machine_tags", "o_dims", "views", "media", "path_alias", "url_s", "url_q", "url_m", "url_z", "url_b"),500,($i+1));
	echo "page number: ".$i."\n";
	}
	foreach ($getRecent["photos"]["photo"] as $photo) 
	{
	// write image file
      $url = $photo['url_z'];//"http://farm" . $photo['farm'] . ".staticflickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "_z.jpg";
      $imageFile = $imagesDirectory . $photo['id'] . '.jpg';
      if (!file_exists($imageFile) or $cfg['overwriteFiles'] == true) {
        echo "#".$counter." saving image file ".$imageFile."\n";
        file_put_contents($imageFile, file_get_contents($url));
		$db->photos->insert( $photo );
		$counter++;
	  } else
        echo "skipping existing image file $imageFile\n";
		
	}//end foreach page
}//end for pages
	
echo $counter." recent photos collected\n";
echo "from the ".$numPages." pages fetched\n";
}
else{

foreach ($queries as $query) {

  $query = trim($query);
  echo "\nSearching for '" . $query . "'...\n";
//stop if query is empty
  if (empty($query))
    die("ERROR: empty query");

  $pageCounter = 0;
  $numPages = 0;
  $photoCounter = 0;

  // 4 quarters in each year
 for($year=2005;$year<2013;$year++){ 
 for($quarter=0;$quarter<4;$quarter++){
  $startDate = "1 ".$cfg['startMonths'][$quarter]." ".$year;
  $startDateTimestamp = strtotime($startDate);
  $endDate = "30 ".$cfg['endMonths'][$quarter]." ".$year; 
  $endDateTimestamp = strtotime($endDate);
  if ($cfg['useAPI'] == 'flickr') {
    // http://www.flickr.com/services/api/flickr.photos.search.html
    $search = $flickr->photos_search(array("text" => $query, "per_page" => $cfg['imagesPerPage'], "sort" => "date-posted-asc",
	"min_upload_date"=>$startDate, "max_upload_date"=>$endDate,
	"extras"=>array('description', 'license', 'date_upload', 'date_taken', 'owner_name', 'tags', 'machine_tags', 'o_dims', 'views', 'media', 'path_alias', 'url_sq', 'url_t', 'url_s', 'url_m','url_l')
	));
  }
//  else if ($cfg['useAPI'] == 'bing') {
//
//  }

  $RUCoDDirectory = $cfg['RUCoDDirectory'] . $query ."_".$year."_".$quarter. "/";
  $imagesDirectory = $cfg['imagesDirectory'] . $query ."_".$year."_".$quarter. "/";
  $metaDirectory = $cfg['metadataDirectory'] . $query ."_".$year."_".$quarter. "/";
  $dumpDirectory = $cfg['dumpDirectory'] . $query ."_".$year."_".$quarter. "/";
  
  $numPages = $search['pages'];

  echo "Found " . $search['total'] . " images, ".$numPages." pages\n";
  $pageCounter = 1;

  //go to next query if not found
  if ($numPages == 0) {
    echo ("Empty query, skipping...\n");
    continue;
  }

  // create dirs
  if (!file_exists($RUCoDDirectory))
    if (@mkdir($RUCoDDirectory, 0777, true) == false)
      echo "Error creating directory ".$RUCoDDirectory."\n";
  if (!file_exists($imagesDirectory))
    if (@mkdir($imagesDirectory, 0777, true) == false)
      echo "Error creating directory ".$imagesDirectory."\n";
  if (!file_exists($metaDirectory))
    if (@mkdir($metaDirectory, 0777, true) == false)
      echo "Error creating directory ".$metaDirectory."\n";
	  if (!file_exists($dumpDirectory))
    if (@mkdir($dumpDirectory, 0777, true) == false)
      echo "Error creating directory ".$dumpDirectory."\n";

	  
  for ($pp = 0; $pp < $numPages; $pp++) {

    echo "Fetching page: ".$pageCounter."\n";

    if ($pageCounter > 1)
      $search = $flickr->photos_search(array("text" => $query, "per_page" => $cfg['imagesPerPage'], "sort" => "date-posted-asc", "page" => $pageCounter,
	  "min_upload_date"=>$startDate, "max_upload_date"=>$endDate,
	  "extras"=>array('description', 'license', 'date_upload', 'date_taken', 'owner_name', 'tags', 'machine_tags', 'o_dims', 'views', 'media', 'path_alias', 'url_sq', 'url_t', 'url_s', 'url_m','url_l')
	  ));

    foreach ($search['photo'] as $photo) {

//print_r($photo);
//exit;     
	// $owner = $flickr->people_getInfo($photo['owner']);
      $photoInfo = $flickr->photos_getInfo($photo['id'], $photo['secret']);
      $geoloc = $flickr->photos_geo_getLocation($photo['id']);
	   //get photo tags
       //$tags = $flickr->tags_getListPhoto($photo['id']);
	   
	  $record = array("photo"=>$photo,"photoinfo"=>$photoInfo,"geoloc"=>$geoloc);
	  
	  $tags = $record['photoinfo']['photo']['tags']['tag'];
	  $owner = $record['photoinfo']['photo']['owner'];
	  
	 // print_r($record['photoinfo']);
	 // continue;
	  
 //$tags = $photo['tags'];
 //echo "\n--------------------------------\n".$tags."\n\n---------------------\n";
 
  // echo "\n---\n".$record['photoinfo']['photo']['dates']['posted']."\n";
  // echo $record['photoinfo']['photo']['dates']['taken'];
  
    if($cfg['useMongo']){
	$db->photos->insert($record);
	}
	  //not used
      //$license = $flickr->photos_licenses_getInfo();
      //echo $geoloc['location']['latitude'] . "-" . $geoloc['location']['longitude'] . "\n";

      $photoCounter++;
      echo "#".$photoCounter." photo id:" . $photo['id'] . "\n";
      $myFile = $RUCoDDirectory . $photo['id'] . "_rucod.xml";

      if ($cfg['saveRUCoD'] == true) {
        if (!file_exists($myFile) or $cfg['overwriteFiles'] == true) {
          echo "saving file $myFile\n";
          $fh = fopen($myFile, 'w') or die("ERROR: can't open file");

          $xmlData = file_get_contents($cfg['templateRUCoD']);
          //
          $ContentObjectName = $photo['title'];
          $xmlData = str_replace("{{{ContentObjectName}}}", $ContentObjectName, $xmlData);
          $globalTags = "";
          //$xmlData = str_replace("{{{Tags}}}", $globalTags, $xmlData);
          //CDATA for <>& or other html characters http://www.w3schools.com/xml/xml_cdata.asp
          $FreeText = "<![CDATA[" . $photoInfo['description'] . "]]>";
          $xmlData = str_replace("{{{FreeText}}}", $FreeText, $xmlData);

//          $xmlData .="<Tags>";
//          edo vale ta tags tou RuCOD sinolika (oxi enos mono media...)
//          $xmlData .= "</Tags>";

          $MediaName = $photo['title'];
          $xmlData = str_replace("{{{MediaName}}}", $MediaName, $xmlData);

 
          for ($i = 0; $i < count($tags); $i++) {
            $MetaTag .= "        <MetaTag name=\"UserTag\" type=\"xsd:string\">" . $tags[$i]['_content'] . "</MetaTag>\n";
          }
          $xmlData = str_replace("{{{MetaTag}}}", $MetaTag, $xmlData);

          $MediaUri = "http://farm" . $photo['farm'] . ".staticflickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "_z.jpg";
          $MediaPreview = "http://farm" . $photo['farm'] . ".staticflickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "_s.jpg";
          $xmlData = str_replace("{{{MediaUri}}}", $MediaUri, $xmlData);
          $xmlData = str_replace("{{{MediaPreview}}}", $MediaPreview, $xmlData);

          $Author = $photo['owner_name'];//$owner['username'];
          $xmlData = str_replace("{{{Author}}}", $Author, $xmlData);
          //$xmlData = str_replace("{{{License}}}", $license, $xmlData);
          //

//theosem: fetch size from API (if needed)
//    $imgsize = "--";
//    $xmlData .= "</MediaCreationInformation><Size>" . $imgsize . "</Size></MultimediaContent>
//		<RealWorldInfo>
//		<MetadataUri filetype=\"rwml\">";
          //todo: add server path if needed
          $rwml = $photo['id'] . "_rucod.rwml";
          $xmlData = str_replace("{{{RealWorldInfoMetadataUri}}}", $rwml, $xmlData);

          fwrite($fh, $xmlData);
          fclose($fh);
        } else
          echo "skipping existing file ".$myFile."\n";

        //Fetch geo and date/time and write a rwml file
        $rwmlFile = $RUCoDDirectory . $photo['id'] . "_rucod.rwml";
        if (!file_exists($rwmlFile) or $cfg['overwriteFiles'] == true) {
          echo "Saving file ".$rwmlFile."\n";
          $fh1 = fopen($rwmlFile, 'w') or die("ERROR: can't open file");
          $rwmlData = "<RWML><ContextSlice><DateTime><Date>";
          $mydatesReplaced = str_replace(" ", "T", $photoInfo['dates']['taken']);
          $rwmlData .= $mydatesReplaced;  //2010-04-24T17:23:25Z
          $rwmlData .= "Z</Date></DateTime><Location type=\"gml\"><gml:CircleByCenterPoint numArc=\"1\"><gml:pos>";
          $rwmlData .= $geoloc['location']['latitude'] . " " . $geoloc['location']['longitude'];   //	44.494205 11.34378
          $rwmlData .= "</gml:pos><gml:radius uom=\"M\">10</gml:radius></gml:CircleByCenterPoint></Location><Direction><Heading>0</Heading><Tilt>0</Tilt><Roll>0</Roll></Direction><Weather><Condition>Unknown</Condition><Temperature></Temperature><WindSpeed></WindSpeed><Humidity></Humidity></Weather></ContextSlice></RWML>";
          fwrite($fh1, $rwmlData);
          fclose($fh1);
        } else
          echo "skipping existing file ".$rwmlFile."\n";
      }
      // write image file
      $url = "http://farm" . $photo['farm'] . ".staticflickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "_z.jpg";
      $imageFile = $imagesDirectory . $photo['id'] . '.jpg';
      $imageExists="";
	  if (!file_exists($imageFile) or $cfg['overwriteFiles'] == true) {
        echo "saving image file $imageFile\n";
		
        $imageExists = file_put_contents($imageFile, file_get_contents($url));
      } else
        echo "skipping existing image file ".$imageFile."\n";

		if($imageExists===FALSE)
		continue;
		
	//write metadata file
	$metadataFile = $metaDirectory.$photo['id'].'.meta.txt';
	if (!file_exists($metadataFile) or $cfg['overwriteFiles'] == true) {
		$fh2 = fopen($metadataFile, 'w') or die("ERROR: can't open metadata file");       
	    echo "saving image file ".$metadataFile."\n";
		$metadataStr = "";
        $metadataStr .= "photo ID: ".$photo['id']."\n";
		$metadataStr .= "time taken: ".$record['photoinfo']['photo']['dates']['taken']."\n";
		$metadataStr .= "time uploaded: ".$record['photoinfo']['photo']['dates']['posted']."\n";
		$metadataStr .= "views: ".$record['photoinfo']['photo']['views']."\n";
		$metadataStr .= "tags: ";
		for($it=0;$it<count($record['photoinfo']['photo']['tags']['tag']);$it++){
		$metadataStr .= $record['photoinfo']['photo']['tags']['tag'][$it]['raw']." ";
		}
 		fwrite($fh2, $metadataStr);
       fclose($fh2);
      } else
        echo "skipping existing image file ".$imageFile."\n";
	
	$dumpFile = $dumpDirectory.$photo['id'].'.dump.txt';
	if (!file_exists($dumpFile) or $cfg['overwriteFiles'] == true) {

		echo "saving image file ".$dumpFile."\n";
		ob_start();
		print_r( $record );
		$output = ob_get_clean();

		file_put_contents( $dumpFile,  $output );
      } else
        echo "skipping existing image file ".$imageFile."\n";
	

      //skip after limit
      if (is_numeric($cfg['imageLimit'])) {
        if ($photoCounter >= $cfg['imageLimit']) {
          echo "limit of " . $cfg['imageLimit'] . "images reached, skipping query\n";
          break 2;
        }
      }
    }
    $pageCounter++;
  } //for each page
//delay e.g 2min.
  if (is_numeric($cfg['sleepAfterQuery'])) {
    echo "Sleeping for ".$sleep." sec.\n";
    sleep($cfg['sleepAfterQuery']);
  }
}
}
}
}
echo "\nFinished.\n";
?>
