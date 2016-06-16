<?php
/**
 * blogarchive:d6_preprocess_import command
 * Used to preprocess CSV file to import blog posts exported from Drupal 6 content
 */

namespace Graker\BlogArchive\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Writer;
use SplTempFileObject;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Drupal6ImportPreprocessor extends Command {

  /**
   * @var string The console command name.
   */
  protected $name = 'blogarchive:d6_preprocess_import';

  /**
   * @var string The console command description.
   */
  protected $description = 'Preprocess CSV file to import blog posts exported from Drupal 6 nodes.';


  /**
   * @var int position of content column
   */
  protected $content_index = 0;


  /**
   * @var int position of teaser column
   */
  protected $teaser_index = 0;


  /**
   * @var int position of link column
   */
  protected $link_index = 0;


  /**
   * @var int position of categories column
   */
  protected $categories_index = 0;


  /**
   * @var string path where to place new file links
   */
  protected $file_links = '';


  /**
   * Execute the console command.
   * @return void
   */
  public function fire()
  {
    $filename = $this->argument('input_file');
    $this->output->writeln("Processing file $filename");

    $csv = Reader::createFromPath($filename);
    if (!$csv) {
      $this->error('Can not parse CSV file');
      return;
    }
    $first_row = $csv->fetchOne();
    // set up column positions
    if (!$this->findColumn($first_row, 'content', $this->content_index)) {
      return;
    }
    if (!$this->findColumn($first_row, 'teaser', $this->teaser_index)) {
      return;
    }
    if (!$this->findColumn($first_row, 'link', $this->link_index)) {
      return;
    }
    if (!$this->findColumn($first_row, 'categories', $this->categories_index)) {
      return;
    }

    //process rows
    $this->file_links = $this->option('files');
    $rows = $csv->fetchAll();
    array_shift($rows); // remove header
    $count = count($rows);
    $this->info("CSV file is parsed. It has $count rows. Processing them now...");
    foreach ($rows as &$row) {
      $this->processRow($row);
    }

    //save updated rows
    $output_file = $this->argument('output_file');
    $this->info('Processing finished. Saving...');
    $writer = Writer::createFromFileObject(new SplTempFileObject);
    $writer->insertOne($first_row);
    $writer->insertAll($rows);
    if (!file_put_contents($output_file, $writer->__toString())) {
      $this->error("Failed to write to $output_file");
    } else {
      $this->output->writeln("Processed CSV is written to $output_file");
    }
  }


  /**
   *
   * Processes one row of CSV data
   *
   * @param array $row the row to be processed and changed
   */
  protected function processRow(&$row) {
    $this->checkTeaser($row);
    $this->getLink($row);
    $this->processCategories($row);
    if ($this->file_links) {
      $this->processFileLinks($row);
    }
  }


  /**
   *
   * Checks if this row has teaser equal to content
   * Remove the teaser in this case (i.e. no excerpt needed)
   *
   * @param array $row the row to be processed and changed
   */
  protected function checkTeaser(&$row) {
    if ($row[$this->content_index] == $row[$this->teaser_index]) {
      $row[$this->teaser_index] = '';
      $this->output->writeln("Removing teaser for node $row[0]");
    }
  }


  /**
   *
   * Parses node's slug from the link field given
   * In D6 Views link comes as an anchor tag with full URL in href attribute
   * For slug, we should get the last part of the URL, after last / symbol
   *
   * @param array $row the row to be processed and changed
   */
  protected function getLink(&$row) {
    $start_pos = strpos($row[$this->link_index], '"');
    if (!$start_pos) {
      $this->error("Error parsing link for node $row[0]");
      return ;
    }
    $str = substr($row[$this->link_index], $start_pos+1);
    $close_pos = strpos($str, '"');
    $str = substr($str, 0, $close_pos);
    $parts = explode('/', $str);
    $link = array_pop($parts);
    $row[$this->link_index] = $link;
  }


  /**
   *
   * Replaces standard D6 ", " delimiter in categories with "|"
   *
   * @param array $row the row to be processed and changed
   */
  protected function processCategories(&$row) {
    $row[$this->categories_index] = str_replace(', ', '|', $row[$this->categories_index]);
  }


  /**
   *
   * Replaces sites/default/files with a path in $this->file_links for each anchor's href and img's src
   * Applies both for teaser and content
   *
   * @param $row
   */
  protected function processFileLinks(&$row) {
    $this->replaceLinks($row[$this->content_index]);
    $this->replaceLinks($row[$this->teaser_index]);
  }


  /**
   *
   * Replaces links in html given from sites/default/files to path in $this->file_links
   *
   * @param string $html
   */
  protected function replaceLinks(&$html) {
    if (!$html) {
      //for empty teasers string will be empty
      return ;
    }
    $old_files = '"/sites/default/files';
    $html = str_replace($old_files, '"' . $this->file_links, $html);
//    Proper way needs more work (wrap $html with div, save this div only, then remove wrapping)
//    $old_files = '/sites/default/files';
//    //disable errors for broken html
//    libxml_use_internal_errors(true);
//    $dom = new \DOMDocument();
//    $dom->encoding = 'UTF-8';
//    $dom->loadHTML($html);
//
//    //rewrite links
//    foreach ($dom->getElementsByTagName('a') as $tag) {
//      $href = $tag->getAttribute('href');
//      if (substr($href, 0, strlen($old_files)) === $old_files) {
//        $this->output->writeln("Replacing " . $href . " link");
//        $href = str_replace($old_files, $this->file_links, $href);
//        $tag->setAttribute('href', $href);
//      }
//    }
//    //rewrite images
//    foreach ($dom->getElementsByTagName('img') as $tag) {
//      $src = $tag->getAttribute('src');
//      if (substr($src, 0, strlen($old_files)) === $old_files) {
//        $this->output->writeln("Replacing " . $src . " image");
//        $src = str_replace($old_files, $this->file_links, $src);
//        $tag->setAttribute('src', $src);
//      }
//    }
//    $html = '';
//    foreach ($dom->childNodes as $childNode) {
//      $html .= $dom->ownerDocument->saveHTML($childNode)
//    }
  }


  /**
   * Get the console command arguments.
   * @return array
   */
  protected function getArguments()
  {
    return [
      // input file
      ['input_file', InputArgument::REQUIRED, 'Input CSV file to process'],
      ['output_file', InputArgument::REQUIRED, 'Resulting file name'],
    ];
  }

  /**
   * Get the console command options.
   * @return array
   */
  protected function getOptions()
  {
    return [
      [
        'files',
        NULL,
        InputOption::VALUE_OPTIONAL,
        'Imported files folder path (e.g. /storage/app/old-files, no trailing slash). Set to move file links in content to a new location.',
        NULL,
      ],
    ];
  }


  /**
   * Sets up position of content column
   * Looks through the row of titles to find 'content'
   *
   * @param array $row - row with column titles
   * @param string $column - name of the column to be found
   * @param int $position - value where to save the position
   * @return bool - whether column was found
   */
  protected function findColumn($row, $column, &$position) {
    $pos = 0;
    $found = FALSE;
    foreach ($row as $title) {
      if (strtolower(trim($title)) == $column) {
        $found = TRUE;
        break;
      }
      $pos ++;
    }
    //save what we found
    $position = $pos;
    if ($found) {
      $this->output->writeln("$column was found at column $pos");
    } else {
      $this->error("Can't find column $column");
    }
    return $found;
  }

}
