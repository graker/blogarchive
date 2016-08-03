<?php

/**
 * Galleries parser: takes a list of urls to parse Drupal 6 image galleries
 * Outputs a file with gallery name, file links and names, also downloads and saves each image to folder
 * Should be used to recreate galleries on October site
 */

namespace Graker\BlogArchive\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class D6ParseGalleries extends Command
{
  /**
   * @var string The console command name.
   */
  protected $name = 'blogarchive:d6_parse_galleries';
  
  /**
   * @var string The console command description.
   */
  protected $description = 'Parses Drupal 6 gallery pages to get picture names and download files';
  
  
  /**
   * @var array of gallery urls
   */
  protected $galleries = array();
  
  /**
   * Execute the console command.
   * @return void
   */
  public function fire()
  {
    $galleries_file = $this->argument('input_file');
    
    mkdir('galleries');
    
    $galleries_info = file($galleries_file);
    foreach ($galleries_info as $gallery_line) {
      list($gallery, $folder) = explode(';', $gallery_line);
      $this->processGallery($gallery, $folder);
    }
  }
  
  /**
   * Get the console command arguments.
   * @return array
   */
  protected function getArguments()
  {
    return [
      ['input_file', InputArgument::REQUIRED, 'Text file with list of galleries, [url;folder_to_save], one per line'],
    ];
  }
  
  /**
   * Get the console command options.
   * @return array
   */
  protected function getOptions()
  {
    return [];
  }
  
  
  /**
   *
   * Processes one gallery with url given
   *
   * @param string $gallery
   * @param string $folder
   */
  protected function processGallery($gallery, $folder) {
    $this->output->writeln('Processing gallery ' . $gallery);
    
    mkdir('galleries/' . $folder);
    
    $dom = $this->createDOM(file_get_contents($gallery));
    // find images list
    $ul = NULL;
    foreach ($dom->getElementsByTagName('ul') as $element) {
      if ($element->getAttribute('class') == 'images') {
        $ul = $element;
        break;
      }
    }
    if (!$ul) {
      $this->error('Can\'t find images list');
      return ;
    }
    
    $this->info('Processing images');
    $image_info = '';
    foreach ($ul->getElementsByTagName('img') as $image) {
      $title = $image->getAttribute('title');
      $src = $image->getAttribute('src');
      $this->output->writeln('Found image ' . $title . ' at ' . $src . ', saving');
      $this->saveImage($src, $folder);
      $image_info .= $src . '; ' . $title . "\n";
    }
    
    file_put_contents('galleries/' . $folder . '/' . $folder . '.txt', $image_info);
  }
  
  
  /**
   *
   * Creates dom document from gallery's page html to work with
   *
   * @param $html
   * @return \DOMDocument
   */
  protected function createDOM($html) {
    //disable errors for broken html
    libxml_use_internal_errors(true);
    $dom = new \DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    return $dom;
  }
  
  
  /**
   *
   * Downloads and saves image file
   *
   * @param $src
   * @param $folder
   */
  protected function saveImage($src, $folder) {
    // remove thumb
    $image_url = str_replace('thumbnail.', '', $src);
    // get filename
    $array = explode('.', $image_url);
    $extension = array_pop($array);
    $name = array_pop($array);
    $array = explode('/', $name);
    $name = array_pop($array);
    $image_url = str_replace(' ', '%20', $image_url);
    
    file_put_contents("galleries/$folder/$name.$extension", file_get_contents($image_url));
  }
  
}
