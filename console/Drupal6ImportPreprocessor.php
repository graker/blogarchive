<?php
/**
 * blogarchive:d6_preprocess_import command
 * Used to preprocess CSV file to import blog posts exported from Drupal 6 content
 */

namespace Graker\BlogArchive\Console;

use Illuminate\Console\Command;
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
   * @var int position of id column
   */
  protected $id_index = 0;
  
  
  /**
   * @var int position of title column
   */
  protected $title_index = 0;
  

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
   * @var array of 'code tag' => 'language-lng' (or '' if no specific language)
   */
  protected $code_tags = [
    'code' => '',
    'javascript' => 'lang-js',
    'cpp' => 'lang-cpp',
    'php' => 'lang-php',
    'drupal6' => 'lang-php',
    'qt' => 'lang-cpp',
    'bash' => 'lang-bsh',
  ];


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
    if (!$this->findColumn($first_row, 'title', $this->title_index)) {
      return;
    }
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
    
    // post-process rows
    $this->info('Starting rows post-process');
    foreach ($rows as &$row) {
      $this->postProcess($row, $rows);
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
    $this->processHTML($row);
  }
  
  
  /**
   *
   * Post-processing for row
   * This happens when all rows are processed and information is gathered
   *
   * @param $row
   * @param $rows array of processed rows to reference
   */
  protected function postProcess(&$row, $rows) {
    // check row slug for uniqueness
    $slug = $original_link = $row[$this->link_index];
    $i = 1;
    while (!$this->isUniqueSlug($slug, $rows, $row[$this->id_index])) {
      $slug = $original_link . "-$i";
      $i ++;
    }
    if ($slug != $original_link) {
      $row[$this->link_index] = $slug;
      $this->output->writeln("Fixed non-unique slug for row " . $row[$this->title_index]);
    }
  }
  
  
  /**
   *
   * Check whether $slug given is unique through all the $rows
   *
   * @param $slug
   * @param $rows
   * @param int $id - id of row being checked (to avoid comparing to itself)
   * @return bool
   */
  protected function isUniqueSlug($slug, $rows, $id) {
    foreach ($rows as $row) {
      if ($row[$this->id_index] == $id) {
        continue;
      }
      if ($row[$this->link_index] == $slug) {
        return false;
      }
    }
    return true;
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
    // October has a restriction: slug must be at least 3 chars and no longer than 64 chars
    $link = $this->checkLinkLength($link, $row);
    $row[$this->link_index] = $link;
  }
  
  
  /**
   *
   * Checks if $link meets October's requirement to be at least 3 chars and no longer than 64 chars
   * If not, tries to propose a new link:
   *  - from a title
   *  - by prepending 'id-' to the link
   *
   * @param string $link
   * @param array $row
   * @return string
   */
  protected function checkLinkLength($link, $row) {
    $length = strlen($link);
    if ((3 <= $length) && ($length <= 64)) {
      return $link;
    }
    $title = $row[$this->title_index];
    $this->output->writeln("Updating link length for title=" . $title);
    
    //try to use transliterated title
    if ($length < 3) {
      // update short link
      $string = transliterator_transliterate("Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; [:Punctuation:] Remove; Lower();", $title);
      $string = preg_replace('/[-\s]+/', '-', $string);
      $string = trim($string, '-');
      if (strlen($string) >= 3) {
        $this->output->writeln("Link for title=$title is replaced with transliterated title");
        return $string;
      } else {
        $this->output->writeln("Link for title=$title is prepended with id-");
        return 'id-' . $link;
      }
    } else {
      // update long link - cut to 61 symbol to have room for unique processing later)
      $string = substr($link, 0, 61);
      return $string;
    }
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
   * Process all HTML changes (teaser and content)
   *
   * @param $row
   */
  protected function processHTML(&$row) {
    if ($row[$this->content_index]) {
      $this->processHTMLString($row[$this->content_index]);
    }
    if ($row[$this->teaser_index]) {
      $this->processHTMLString($row[$this->teaser_index]);
    }
  }


  /**
   *
   * Creates dom object for $html given and launches each enabled process for this dom
   * Then processed dom is dumped back to $html given
   *
   * @param string $html
   */
  protected function processHTMLString(&$html) {
    $dom = $this->createDOM($html);

    //processing DOM
    if ($this->file_links) {
      $this->processFileLinks($dom);
    }
    if ($this->option('lightbox-to-magnific')) {
      $this->lightboxToMagnific($dom);
    }
    if ($this->option('code-to-prettify')) {
      $this->replaceCodeWithPrettify($dom);
    }

    $html = $this->dumpDOM($dom);
  }


  /**
   *
   * Creates DOMDocument for html given and returns it to use in further changes
   *
   * @param $html
   * @return \DOMDocument
   */
  protected function createDOM($html) {
    //wrap html
    $html = '<div id="post-import-wrapper">' . $html . '</div>';
    //disable errors for broken html
    libxml_use_internal_errors(true);
    $dom = new \DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    return $dom;
  }


  /**
   *
   * Dumps processed DOMDocument back to string representation and returns it
   *
   * @param \DOMDocument $dom
   * @return string
   */
  protected function dumpDOM($dom) {
    $post_wrapper = $dom->getElementById('post-import-wrapper');
    $html = $dom->saveHTML($post_wrapper);
    //remove wrapper
    $html = str_replace('<div id="post-import-wrapper">', '', $html);
    $html = mb_substr($html, 0, mb_strlen($html)-6);
    return $html;
  }


  /**
   *
   * Replaces sites/default/files with a path in $this->file_links for each anchor's href and img's src
   *
   * @param \DOMDocument $dom
   */
  protected function processFileLinks($dom) {
    $old_files = '/sites/default/files';

    //rewrite links
    foreach ($dom->getElementsByTagName('a') as $tag) {
      $href = $tag->getAttribute('href');
      if (substr($href, 0, strlen($old_files)) === $old_files) {
        $this->output->writeln("Replacing " . $href . " link");
        $href = str_replace($old_files, $this->file_links, $href);
        $tag->setAttribute('href', $href);
      }
    }

    //rewrite images
    foreach ($dom->getElementsByTagName('img') as $tag) {
      $src = $tag->getAttribute('src');
      if (substr($src, 0, strlen($old_files)) === $old_files) {
        $this->output->writeln("Replacing " . $src . " image");
        $src = str_replace($old_files, $this->file_links, $src);
        $tag->setAttribute('src', $src);
      }
    }
  }


  /**
   *
   * Replaces rel="lightbox" with class="magnific"
   *
   * @param \DOMDocument $dom
   */
  protected function lightboxToMagnific($dom) {
    foreach ($dom->getElementsByTagName('a') as $link) {
      if ($link->getAttribute('rel') == 'lightbox') {
        $link->removeAttribute('rel');
        $link->setAttribute('class', 'magnific');
      }
    }
  }


  /**
   *
   * Replaces code tags with prettify markup,
   * also moves code tags outside of the paragraphs (or prettify won't work)
   *
   * @param \DOMDocument $dom
   */
  protected function replaceCodeWithPrettify($dom) {
    foreach ($this->code_tags as $code_tag => $language_class) {
      $this->processCodeTag($dom, $code_tag, $language_class);
    }
  }


  /**
   *
   * Process one code tag to be prettified
   *
   * @param \DOMDocument $dom - dom being processed
   * @param string $code_tag - code tag name
   * @param string $language_class - class to tip Prettify on the language used
   */
  protected function processCodeTag($dom, $code_tag, $language_class) {
    $code_tags = array();
    //get code tags for post
    foreach ($dom->getElementsByTagName($code_tag) as $tag) {
      $code_tags[] = $tag;
    }

    if (empty($code_tags)) {
      return;
    }
    foreach ($code_tags as $tag) {
      $parent = $tag->parentNode;
      if ($code_tag != 'code') {
        //replace $code_tag with <code class="$language_class">
        $new_tag = $dom->createElement('code');
        foreach ($tag->childNodes as $child) {
          $new_tag->appendChild($child->cloneNode(true));
        }
        $parent->replaceChild($new_tag, $tag);
        $tag = $new_tag;
      }
      if ($parent->nodeName == 'p') {
        $tag = $parent->removeChild($tag);
        $newParent = $parent->parentNode;
        $newParent->insertBefore($tag, $parent->nextSibling);
        $parent = $newParent;
      }
      //wrap code element with <pre></pre>
      $element = $dom->createElement('pre');
      $element->setAttribute('class', 'prettyprint ' . $language_class);
      $parent->replaceChild($element, $tag);
      $element->appendChild($tag);
      //delete brs in code (if any)
      $brs = array();
      foreach ($tag->childNodes as $childNode) {
        if ($childNode->nodeName == 'br') {
          $brs[] = $childNode;
        }
      }
      foreach ($brs as $br) {
        $tag->removeChild($br);
      }
    }
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
      [ 'lightbox-to-magnific', NULL, InputOption::VALUE_NONE, 'If set, rel="lightbox" will be replaced with class="magnific"'],
      [ 'code-to-prettify', NULL, InputOption::VALUE_NONE, 'If set, code tags will be replaced with prettify markup'],
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
