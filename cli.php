<?php

use ChoOo7\Warmshowers;
use ChoOo7\Gpx;
use PHPePub\Core\EPub;

require_once(__DIR__.'/lib/Warmshowers.php');
require_once(__DIR__.'/lib/Gpx.php');
require_once(__DIR__.'/vendor/autoload.php');

$username=$argv[1];
$password=$argv[2];
$gpxFilename=$argv[3];


$searchInSquareOfXMeters = 3000;
$dx = $dy = $searchInSquareOfXMeters;

$ws = new \ChoOo7\Warmshowers();
$sessionIdentifier = $ws->login($username, $password);

$gpx = new Gpx($gpxFilename);

$points = $gpx->getPointsOfGPX();

$gpx->decreasePointsNumber(3);

$points = $gpx->getPointsOfGPX();


$selectedHosts = array();

//$points = array_slice($points, 0, 5);

foreach($points as $point)
{
  $lat0 = $point['lat'];
  $lon0 = $point['lon'];


  //$lat = $lat0 + (180/pi())*($dy/6378137);
  //$lon = $lon0 + (180/pi())*($dx/6378137)/cos($lat0);

  $minLat = $lat0 - (180/pi())*($dy/6378137);
  $maxLat = $lat0 + (180/pi())*($dy/6378137);

  $minLon = $lon0 - (180/pi())*($dx/6378137)/cos($lat0);
  $maxLon = $lon0 + (180/pi())*($dx/6378137)/cos($lat0);

  $centerLat = $lat0;
  $centerLon = $lon0;

  $limit = 100;

  $hosts = $ws->getHostsByLocation($minLat, $maxLat, $minLon, $maxLon, $centerLat, $centerLon, $limit);
  echo "\n".$centerLat.','.$centerLon.' : '.count($hosts)." hosts";

  foreach($hosts as $host)
  {
    $hostUId = $host['uid'];
    $hostDistnace = $host['distance'];

    if($host['notcurrentlyavailable'] == "1")
    {
      continue;
    }

    if( ! array_key_exists($hostUId, $selectedHosts))
    {
      $selectedHosts[$hostUId] = $host;
    }else{
      //Si la distance actuelle est plus faible
      if($selectedHosts[$hostUId]['distance'] > $host['distance'])
      {
        $selectedHosts[$hostUId] = $host;
      }
    }
  }
}

$selectedHosts = array_slice($selectedHosts, 0, 100, true);

foreach($selectedHosts as $uid=>$host)
{

  echo "\n"." Getting host information of ".$uid;

  $additionalInformations = $ws->getHostInformations($uid);
  $host = array_merge($host, $additionalInformations);

  $selectedHosts[$uid] = $host;
}

echo "\nGenerating Epub";

$content_start =
  "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
  . "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
  . "    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
  . "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
  . "<head>"
  . "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n"
  . "<link rel=\"stylesheet\" type=\"text/css\" href=\"styles.css\" />\n"
  . "<title>".basename($gpxFilename)."</title>\n"
  . "</head>\n"
  . "<body>\n";
$content_end = "</body>\n</html>\n";

$book = new EPub();
$book->setTitle(basename($gpxFilename));
$authorname = "Simon Minotto";
$book->setAuthor($authorname, $authorname);
$book->setLanguage("fr");
$cover = $content_start . "<h1>" . "test" . "</h1>\n";
if ($authorname) {
  $cover .= "<h2>By: $authorname</h2>\n";
}
$cover .= $content_end;




$book->addChapter("Notices", "Cover.html", $cover);
$book->buildTOC();

$lots = array_chunk($selectedHosts, 10);
foreach($lots as $lotIndex=>$hosts)
{
  $chapterName = "Lot ".($lotIndex+1);

  $content = "";

  $hostIndex = 0;
  $firstCity = null;
  foreach($hosts as $host)
  {
    if($firstCity == null && $host['city'])
    {
      $firstCity = $host['city'];
      $chapterName.=' - '.$host['city'];
      echo "\nChapter ".$chapterName;
    }
    $hostIndex++;
    $content.='<hr />';
    $content.='<h2>'.$host['name'].'</h2>';
    $content.='<p>'.$host['fullname'].'</p>';
    $content.='<p>User id : '.$host['uid'].'</p>';
    $content.='<p>Adresse : '.@$host['street'].' '.@$host['city'].'</p>';
    $content.='<p>Adresse 2: '.@$host['adress'].'</p>';
    $content.='<p>Position: '.$host['position'].'</p>';
    $content.='<p>Logement: '.nl2br(@$host['logement']).'</p>';
    if(@$host['phones']) {
      $content .= '<p>Tel: ' . nl2br(implode("\n", @$host['phones'])) . '</p>';
    }
    $content.='<p>Reactivité: '.nl2br(@$host['reactivity']).'</p>';
    $content.='<p>Description: '.nl2br(@$host['description']).'</p>';
    $content.='<p>Distance: '.(round($host['distance'], 10)).'</p>';
    $content.='<p>Langue: '.nl2br(@$host['langue']).'</p>';
    if(@$host['canOffer'])
    {
      $content.='<p>canOffer: '.nl2br(implode("\n", @$host['canOffer'])).'</p>';
    }
    if(@$host['profilPicture'])
    {
      $content.='<p><img src="'.$host['profilPicture'].'" /></p>';
    }

  }

  $content = '<h1>'.$chapterName.'</h1>'.$content;

  $book->addChapter(
    $chapterName,
    $chapterName.".html",
    $content_start . $content."\n" . $content_end,
    false,
    EPub::EXTERNAL_REF_ADD
  );
}

$book->addChapter(
  "Chapter 1",
  "Chapter1.html",
  $content_start . "<h1>Chapter 1</h1>\n<p>Plenty of test content</p>\n" . $content_end
);
$book->addChapter(
  "Chapter 2",
  "Chapter2.html",
  $content_start . "<h1>Chapter 2</h1>\n<p>Plenty of test content</p>\n" . $content_end
);


$book->finalize();
$book->saveBook($gpxFilename.'.epub', '/');


var_dump(count($selectedHosts));
//var_dump(array_slice($selectedHosts, 0, 1));


//var_dump($hosts);

die();