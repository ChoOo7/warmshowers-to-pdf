<?php

if( ! file_exists(__DIR__.'/vendor/autoload.php'))
{
  echo "Error - Composer not installer. Please run php composer.phar install";
  exit(1);
}
if( ! function_exists("simplexml_load_file"))
{
  echo "Error - please install php-xml (sudo apt-get install php-xml)";
  exit(1);
}
//TODO : modifier & tester si l'extention php zip est installée
if( ! function_exists("simplexml_load_file"))
{
  echo "Error - please install php-xml (sudo apt-get install php-xml)";
  exit(1);
}

if( ! is_writable(__DIR__.'/generated/'))
{
  echo "The path ".__DIR__.'/generated/'.' is not writable';
  exit(1);
}

if( ! is_writable(__DIR__.'/cache/'))
{
  echo "The path ".__DIR__.'/cache/'.' is not writable';
  exit(1);
}
