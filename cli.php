<?php

use ChoOo7\Warmshowers;
use ChoOo7\Gpx;
use ChoOo7\WarmEpub;
use PHPePub\Core\EPub;

require_once(__DIR__.'/lib/Warmshowers.php');
require_once(__DIR__.'/lib/Gpx.php');
require_once(__DIR__.'/lib/WarmEpub.php');
require_once(__DIR__.'/vendor/autoload.php');
//require_once(__DIR__.'/vendor/fastglass/sendgrid/src/autoloader.php');

if(file_exists(__DIR__.'/config.php'))
{
  require_once(__DIR__ . '/config.php');
}

$username=$argv[1];
$password=$argv[2];
$gpxFilename=$argv[3];
$outputFilename=@$argv[4];


$fromKm=@$argv[5];
$toKm=@$argv[6];

$reverseOrder=@$argv[7] == "1";
$serverName=@$argv[8];
$searchInSquareOfXMeters=@$argv[9];
$includeImage=@$argv[10] == "true";

//$includeImage = true;

if(empty($outputFilename))
{
  $outputFilename = str_replace('.gpx', '', $gpxFilename).'.epub';
}

if(empty($searchInSquareOfXMeters))
{
  $searchInSquareOfXMeters = 5000;
}
$dx = $dy = $searchInSquareOfXMeters;

$ws = new \ChoOo7\Warmshowers();
$sessionIdentifier = $ws->login($username, $password);

$gpx = new Gpx($gpxFilename);

$points = $gpx->getPointsOfGPX();

$gpx->decreasePointsNumber(3);//un point tous les 3 kms

$points = $gpx->getPointsOfGPX();

$selectedHosts = array();

//$points = array_slice($points, 0, 5);

$lastPoint = $start = $points[0];
$distance = 0;
foreach($points as $pointIndex=>$point)
{
  $lat0 = $point['lat'];
  $lon0 = $point['lon'];

  $segDistance = Gpx::distance($lastPoint['lat'], $lastPoint['lon'], $point['lat'], $point['lon']);
  $lastPoint = $point;

  $distance += $segDistance;
  if($fromKm)
  {
    if($distance < ($fromKm))
    {
      echo "\nHors carte";
      continue;
    }
  }

  if($toKm)
  {
    if($distance > ($toKm))
    {
      break;
    }
  }


  $minLat = $lat0 - (180/pi())*($dy/6378137);
  $maxLat = $lat0 + (180/pi())*($dy/6378137);

  if($minLat > $maxLat)
  {
    $tmp = $minLat;
    $minLat = $maxLat;
    $maxLat = $tmp;
  }

  $minLon = $lon0 + (180/pi())*($dx/6378137)/cos($lat0);
  $maxLon = $lon0 - (180/pi())*($dx/6378137)/cos($lat0);


  if($minLon > $maxLon)
  {
    $tmp = $minLon;
    $minLon = $maxLon;
    $maxLon = $tmp;
  }

  $centerLat = $lat0;
  $centerLon = $lon0;

  $limit = 50;
  $hosts = $ws->getHostsByLocation($minLat, $maxLat, $minLon, $maxLon, $centerLat, $centerLon, $limit);
  
  echo "\n".($pointIndex+1).'/'.count($points)." - ".round($distance)." -  ".$centerLat.','.$centerLon.' : '.count($hosts)." hosts";

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

//$selectedHosts = array_slice($selectedHosts, 0, 100, true);

if($reverseOrder)
{
  $selectedHosts = array_reverse($selectedHosts, true);
}

$hostIndex = 0;
foreach($selectedHosts as $uid=>$host)
{

  echo "\n".($hostIndex+1)."/".count($selectedHosts)." - Getting host information of ".$uid;
  $hostIndex++;

  $additionalInformations = $ws->getHostInformations($uid);
  $host = array_merge($host, $additionalInformations);

  $selectedHosts[$uid] = $host;
}

echo "\nGenerating Epub";

$epubName = basename($gpxFilename);
$epubName = str_replace('.gpx', '', $epubName);

$outputFilenameWithoutImages = str_replace('.epub', '-noimage.epub', $outputFilename);

$wep = new WarmEpub();
if($includeImage)
{
  echo "\nGenerating Epub with images";
  $wep->generateEpub($selectedHosts, $epubName, $outputFilename, true);
  echo "\nDone\n";
  echo "\nepub saved : ".$outputFilename."\n";

}else
{
  echo "\nGenerating Epub without images";
  $wep->generateEpub($selectedHosts, $epubName . " sans image", $outputFilenameWithoutImages, false);
  echo "\nDone\n";
  echo "\nepub saved : " . $outputFilenameWithoutImages . "\n";
}

echo "\nCan be converted to PDf using http://www.online-convert.com/\n";


if($serverName) {
  $httpBaseLink = "http://" . $serverName . "/generated/";
  $httpLinkWithoutImages = $httpBaseLink . basename($outputFilenameWithoutImages);
  $httpLinkWithImages = $httpBaseLink . basename($outputFilename);

  $mailBody = 'Votre fichier WarmShowers est disponible';
  $mailBody .= "\n".'Celui-ci est disponible temporairement en téléchargement à l\'adresse suviante : ';
  if($includeImage)
  {
    $mailBody .= "\n" . "Version avec image : " . $httpLinkWithImages;
  }else{
    $mailBody .= "\n"."Version sans image : ".$httpLinkWithoutImages;
  }
  $mailBody .= "\n";
  $mailBody .= "\n"."Can be converted to PDf using http://www.online-convert.com/";
  $mailBody .= "\n";
  $mailBody .= "\nPar Simon Minotto - https://github.com/ChoOo7/warmshowers-to-pdf";
  $mailBody .= "\n";

  echo "\n".$mailBody;

  if(isset($_config))
  {
    $apiKey = $_config['sendgridApiKey'];
    $from = $_config['fromEmail'];

    $sendGrid = new \SendGrid\Client($apiKey);
    $email = new \SendGrid\Email();

    $email->addTo($username)->setFrom($from)->setSubject("Warmshowers generated files")->setText($mailBody)->setHtml(nl2br($mailBody));

    $sendGrid->send($email);
  }else{
    mail($username, 'Warmshowers generated files', $mailBody);
  }

  echo "\n";
}

