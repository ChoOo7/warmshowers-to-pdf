<?php

namespace ChoOo7;

use GuzzleHttp;

class Warmshowers
{
  const SITE_BASE_URL='https://fr.warmshowers.org/';

  protected $sessionInformation = null;

  /**
   * @var GuzzleHttp\Client|null
   */
  protected $client = null;

  protected $debug = true;


  public function __construct()
  {
    $this->client = new GuzzleHttp\Client(array('cookies'=>true));
  }

  public function getFormBuildId()
  {

    $res = $this->client->request('POST', self::SITE_BASE_URL.'/user', array(
      'debug'=>$this->debug
    ));
    $responseText = $res->getBody();
    preg_match('!<input type="hidden" name="form_build_id" value="([^"]*)" />\s+<input type="hidden" name="form_id" value="user_login" />!ism', $responseText, $matches);
    return $matches[1];
  }

  public function login($username, $password)
  {

    $form_build_id = $this->getFormBuildId();
    var_dump($form_build_id);die();

    $postData = array(
      'name'=>$username,
      'pass'=>$password,
      'form_build_id'=>$form_build_id,
      'form_id'=>'user_login',
      'op'=>'Se connecter'
    );
    var_dump($postData);

    $res = $this->client->request('POST', self::SITE_BASE_URL.'user', array(
      'form_params'=> $postData,
      'debug'=>$this->debug
    ));
    $setCookieLine = $res->getHeaderLine("Set-Cookie");
    $tmp = explode(';', $setCookieLine);
    $sessionInformation = $tmp[0];
    $this->sessionInformation = $sessionInformation;
    return $sessionInformation;
  }


  public function getCSRFToken()
  {
    $res = $this->client->request('GET', self::SITE_BASE_URL.'', array(
      'debug'=>$this->debug
    ));
    $responseText = $res->getBody();
    preg_match('!"csrf_token":"([^"]+)"!ism', $responseText, $matches);
    return $matches[1];
  }

  protected $cache = array();
  protected function getCache()
  {
    if( ! empty($this->cache))
    {
      return $this->cache;
    }
    $cacheFile = __DIR__."/.cache-getHostsByLocation";
    $cacheContent = @file_get_contents($cacheFile);
    if($cacheContent)
    {
      $this->cache = json_decode($cacheContent, true);
      return $this->cache;
    }else{
      return array();
    }
  }

  protected function setCache($cache)
  {
    $this->cache = $cache;

    $cacheFile = __DIR__."/.cache-getHostsByLocation";
    file_put_contents($cacheFile.'-tmp', json_encode($cache, JSON_PRETTY_PRINT));
    rename($cacheFile.'-tmp', $cacheFile);
  }

  public function getHostsByLocation($minLat, $maxLat, $minLon, $maxLon, $centerLat, $centerLon, $limit=30)
  {

    $cache = $this->getCache();
    $cacheKey = $minLat.'-'.$maxLat.'-'.$minLon.'-'.$maxLon.'-'.$centerLat.'-'.$centerLon.'-'.$limit;
    if(false && array_key_exists($cacheKey, $cache))
    {
      return $cache[$cacheKey];
    }

    $csrfToken = $this->getCSRFToken();

    $headers = array(
      'X-CSRF-Token'=> $csrfToken,
      'X-Requested-With'=>'XMLHttpRequest',
      'Content-Type'=>'application/x-www-form-urlencoded; charset=UTF-8',
      'Accept'=>'application/json, text/javascript, */*; q=0.01'
    );

    $postData = array(
      'minlat'=>$minLat,
      'maxlat'=>$maxLat,
      'minlon'=>$minLon,
      'maxlon'=>$maxLon,
      'centerlat'=>$centerLat,
      'centerlon'=>$centerLon,
      'limit'=>$limit
    );

    $res = $this->client->request('POST', self::SITE_BASE_URL.'services/rest/hosts/by_location', array(
      'form_params'=> $postData,
      'headers' => $headers,
      'debug'=>$this->debug
    ));

    $response = $res->getBody();
var_dump((string)$response);
    $responseAsArray = json_decode($response, true);

    $return = $responseAsArray['accounts'];
    $cache[$cacheKey] = $return;
    $this->setCache($cache);

    return $return;

  }

  protected static function cleanHtml($html)
  {

    $html = strip_tags($html);
    $html = html_entity_decode($html);
    $html = str_replace('&#039;', "'", $html);
    $html = str_replace("\n\n", "\n", $html);
    $html = str_replace("\n\n", "\n", $html);
    $html = str_replace("  ", " ", $html);
    $html = str_replace("  ", " ", $html);
    $html = str_replace("  ", " ", $html);
    $html = str_replace("  ", " ", $html);
    $html = str_replace("  ", " ", $html);
    $html = str_replace(":\n", ":", $html);
    $html = explode("\n", $html);
    $html = array_filter(array_map('trim', $html));
    $html = implode("\n", $html);
    return $html;
  }

  public function getHostInformations($uid)
  {
    $cache = $this->getCache();
    $cacheKey = 'user-'.$uid;
    if(array_key_exists($cacheKey, $cache))
    {
      return $cache[$cacheKey];
    }

    $res = $this->client->request('GET', self::SITE_BASE_URL.'user/'.$uid, array(
      'debug'=>$this->debug
    ));

    $response = $res->getBody();

    $infos = array();

    if(preg_match('!.*<h4>Cet hôte peut offrir</h4>(\s)*+<ul>(\s)*+(.*)(\s)*</ul>.*!Uis', $response, $matches))
    {
      $canOffer = str_replace('<li>', '', $matches[3]);
      $canOffer = explode('</li>', $canOffer);
      $canOffer = array_filter(array_map('trim', $canOffer));
      $infos['canOffer'] = $canOffer;
    }
    if(preg_match('!.*<span class="member-city">([^<]+)</span>.*!Uis', $response, $matches))
    {
      $adress = $matches[1];
      $infos['adress'] = $adress;
    }
    if(preg_match_all('!.*<div class="phone[^"]*"><span class="number">([^<]*)</span.*!Uis', $response, $matches))
    {
      $phones = $matches[1];
      $infos['phones'] = $phones;
    }

    if(preg_match('!.*<div class="responsive-count">\s*Réactivité: ([0-9]+)%\s*</div>\s*.*!Uis', $response, $matches))
    {
      $reactivity= $matches[1];
      $infos['reactivity'] = $reactivity;
    }
    if(preg_match('!.*<div class="account-body">\s(.*)\s*</div>.*!Uis', $response, $matches))
    {
      $description = self::cleanHtml($matches[1]);
      $infos['description'] = $description;
    }

    if(preg_match('!.*<h2>Informations sur l\'hébergement proposé</h2>(.*)<h4>.*!Uis', $response, $matches))
    {
      $logement = self::cleanHtml($matches[1]);
      $infos['logement'] = $logement;
    }
    if(preg_match('!.*<li class="personal languages-spoken">Langues parlées: <em class="placeholder">(.*)</em></li>.*!Uis', $response, $matches))
    {
      $langue = self::cleanHtml($matches[1]);
      $infos['langue'] = $langue;
    }
    if(preg_match('!.*<span class="user-picture">\s*<a href="([^"]*)" title=".*!Uis', $response, $matches))
    {
      $profilPicture = $matches[1];
      $infos['profilPicture'] = $profilPicture;
    }

    $cache[$cacheKey] = $infos;
    $this->setCache($cache);

    return $infos;
  }


}