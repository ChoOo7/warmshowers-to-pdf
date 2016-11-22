<?php

namespace ChoOo7;

use PHPePub\Core\EPub;

class WarmEpub
{

  protected $author = "Simon Minotto";

  public function __construct()
  {

  }

  public function generateEpub($hosts, $bookName, $outputFilename, $includeImage = true)
  {
    $book = new EPub();
    $book->setTitle($bookName);
    $book->setAuthor($this->author, $this->author);
    $book->setLanguage("fr");


    $cover = $this->getContentStart($bookName) . "<h1>" . $bookName . "</h1>\n";
    if ($this->author) {
      $cover .= "<h2>Par: ".$this->author."</h2>\n";
    }
    $cover .= $this->getContentEnd();
    $book->addChapter("Informations", "Cover.html", $cover);
    $book->buildTOC();

    //Constructions des données
    $lots = array_chunk($hosts, 10);
    foreach($lots as $lotIndex=>$subHosts)
    {
      ini_set('max_execution_time', 3600);
      $chapterName = "Lot ".($lotIndex+1).'/'.count($lots);

      $content = "";

      $hostIndex = 0;
      $firstCity = null;
      foreach($subHosts as $host)
      {
        if($firstCity == null && $host['city'])
        {
          $firstCity = $host['city'];
          $chapterName.=' - '.$host['city'];
          echo "\nChapter ".$chapterName;
        }
        echo "\n\t".$host['uid'];
        $hostIndex++;
        $content.='<hr />';
        $content.='<h2>'.$host['name'].'</h2>';
        $content.='<p>'.$host['fullname'].'</p>';
        $content.='<p>User id : '.$host['uid'].'</p>';
        $content.='<p>Profile page : '.'https://en.warmshowers.org/user/'.$host['uid'].'</p>';        
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
        if($includeImage && @$host['profilPicture'])
        {
          $content.='<p><img src="'.$host['profilPicture'].'" /></p>';
        }

      }

      $content = '<h1>'.$chapterName.'</h1>'.$content;

      $book->addChapter(
        $chapterName,
        $chapterName.".html",
        $this->getContentStart($chapterName) . $content."\n" . $this->getContentEnd(),
        false,
        EPub::EXTERNAL_REF_ADD//Include images in ebook
      );
    }

    $book->finalize();

    $book->saveBook($outputFilename, '/');
  }

  public function getDataFromHosts($hosts)
  {
    $data = array();

    foreach($hosts as $host)
    {
      $item = array();
      $item['city'] = $host['city'];
      $item['hostUid'] = $host['uid'];
      $item['profileLink'] = 'https://en.warmshowers.org/user/'.$host['uid'];
      $item['name'] = $host['name'];
      $item['full name'] = $host['fullname'];
      $item['adress'] = @$host['street'].' '.@$host['city'];
      $item['adress 2'] = @$host['adress'];
      $item['position'] = $host['position'];
      $item['accomodation'] = (@$host['logement']);
      if( ! empty($host['phones']))
      {
        $item['phones'] = implode("\n", @$host['phones']);
      }
      $item['reactivity'] = @$host['reactivity'];
      $item['description'] = @$host['description'];
      $item['distanceFromRoute'] = round($host['distance'], 10);
      $item['langages'] = @$host['langue'];
      $item['canOffer'] = implode("\n", @$host['canOffer']);
      
      $data[]=$item;
    }
    return $data;
  }

  public function generateXslx($hosts, $outputFilename)
  {
    $data = $this->getDataFromHosts($hosts);

    $header = array_keys($data[0]);

    $excel = new \PHPExcel();
    $sheet = $excel->getActiveSheet();
    $row = 1;
    foreach($header as $k=>$v)
    {
      $sheet->setCellValueByColumnAndRow($k, $row, $v);
    }
    foreach($data as $item)
    {
      $row++;
      $item = array_values($item);//to have numerical index
      foreach($item as $k=>$v)
      {
        $sheet->setCellValueByColumnAndRow($k, $row, $v);
      }
    }
    $objWriter = new \PHPExcel_Writer_Excel2007($excel);
    $objWriter->save($outputFilename);
  }

  public function generateJson($hosts, $outputFilename)
  {
    $data = $this->getDataFromHosts($hosts);

    file_put_contents($outputFilename, json_encode($data, JSON_PRETTY_PRINT));
  }

  protected function getContentStart($bookName)
  {
    $content_start =
      "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
      . "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"\n"
      . "    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"
      . "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
      . "<head>"
      . "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n"
      . "<link rel=\"stylesheet\" type=\"text/css\" href=\"styles.css\" />\n"
      . "<title>".$bookName."</title>\n"
      . "</head>\n"
      . "<body>\n";
    return $content_start;
  }
  protected function getContentEnd()
  {
    $content_end = "</body>\n</html>\n";
    return $content_end;
  }

}