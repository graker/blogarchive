<?php
/**
 * blogarchive:d6_preprocess_import command
 * Used to preprocess CSV file to import blog posts exported from Drupal 6 content
 */

namespace Graker\BlogArchive\Console;

use Illuminate\Console\Command;
use League\Csv\Reader;
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

    $rows = $csv->fetchAll();
    array_shift($rows); // remove header
    $count = count($rows);
    $this->info("CSV file is parsed. It has $count rows. Processing them now...");
    foreach ($rows as &$row) {
      $this->processRow($row);
    }
    $this->info('Processing finished. Saving...');
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
    $http_pos = strpos($row[$this->link_index], 'http://');
    if (!$http_pos) {
      $this->error("Error parsing link for node $row[0]");
      return ;
    }
    $str = substr($row[$this->link_index], $http_pos);
    $close_pos = strpos($str, '"');
    $str = substr($str, $close_pos - 1);
    $parts = explode('/', $str);
    $link = array_pop($parts);
    $row[$this->link_index] = $link;
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
    return [];
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
