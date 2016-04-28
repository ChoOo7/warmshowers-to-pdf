<?php

namespace ChoOo7;

class Gpx
{

  protected $fileContentAsXml = null;
  protected $points = array();

  public function __construct($filename)
  {
    $this->openFile($filename);
  }

  public function openFile($filename)
  {
    $this->fileContentAsXml = simplexml_load_file($filename);
  }

  public function getPointsOfGPX($offset=0, $limit=null)
  {
    $this->loadPointsOfGPX();

    return array_slice($this->points, $offset, $limit);
  }

  public function loadPointsOfGPX()
  {
    if(empty($this->points))
    {
      foreach($this->fileContentAsXml->trk as $trk)
      {
        foreach($trk->trkseg as $trkseg)
        {
          foreach($trkseg->trkpt as $trkpt)
          {
            $lat = (string)$trkpt['lat'];
            $lon = (string)$trkpt['lon'];
            $this->points[] = array('lon'=>$lon, 'lat'=>$lat);
          }
        }
      }
    }
  }

  public function decreasePointsNumber($minDistanceBeetweenPointsInKm = 5000)
  {
    $minDistanceBeetweenPointsInKm = 5;
    $this->loadPointsOfGPX();
    $points = array();
    $actualPoint = null;
    foreach($this->points as $point)
    {
      if($actualPoint == null)
      {
        $actualPoint = $point;
        $points[] = $actualPoint;
        continue;
      }
      if(self::distance($actualPoint['lat'], $actualPoint['lon'], $point['lat'], $point['lon'], 'K') >= $minDistanceBeetweenPointsInKm)
      {
        $actualPoint = $point;
        $points[] = $actualPoint;
        continue;
      }
    }
    $this->points = $points;
  }

  public static function distance($lat1, $lon1, $lat2, $lon2, $unit = 'K') {

    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    $unit = strtoupper($unit);

    if ($unit == "K") {
      return ($miles * 1.609344);
    } else if ($unit == "N") {
      return ($miles * 0.8684);
    } else {
      return $miles;
    }
  }

}