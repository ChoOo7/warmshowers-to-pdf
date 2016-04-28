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