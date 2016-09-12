<?php

namespace Graker\BlogArchive\Components;

use Carbon\Carbon;
use Graker\BlogArchive\Classes\ArchivePager;
use Rainlab\Blog\Models\Post;
use Cms\Classes\Page;
use App;
use Redirect;

class BlogArchive extends \Cms\Classes\ComponentBase {

  /**
   * @var string year to display archive for
   */
  public $year = '';

  /**
   * @var string month to display archive for
   */
  public $month = '';

  /**
   * @var string day to display archive for
   */
  public $day = '';

  /*
   * Vars for mini-pager
   */
  public $previous_text = '';
  public $previous_url = '';
  public $next_text = '';
  public $next_url = '';

  /**
   * Returns information about this component, including name and description.
   */
  public function componentDetails() {
    return [
      'name' => 'Blog Archive',
      'description' => 'Displays an archive of blog posts by year and (optionally) month given',
    ];
  }


  /**
   *
   * Returns array of archive posts
   * Uses properties: year, month, day to figure out archive page settings
   *
   * @return array
   */
  public function archivePosts() {
    list($start, $end) = $this->getCurrentRange();
    $posts = Post::where('published_at', '>=', $start)
      ->where('published_at', '<', $end)
      ->with('categories')
      ->orderBy('published_at', 'desc')
      ->get();
    return $this->preparePosts($posts);
  }


  /**
   * Component initialization
   * Figure out archive parameters and save them to properties
   */
  public function init() {
    $this->year = $this->param($this->property('yearParam'));
    $this->month = $this->param($this->property('monthParam'));
    $this->day = $this->param($this->property('dayParam'));
  }


  /**
   * onRun() event implementation
   *  - Validate year, month and day values, if not valid, go 404
   */
  public function onRun() {
    $year = $this->year;
    $month = (!$this->month) ? '1' : $this->month;
    $day = (!$this->day) ? '1' : $this->day;

    if (!ctype_digit($year) || !ctype_digit($month) || !ctype_digit($day)) {
      return Redirect::to('404');
    }

    if (!checkdate($month, $day, $year)) {
      return Redirect::to('404');
    }

    $this->setupPager();
  }


  /**
   *
   * Returns array of component's properties descriptions
   *
   * @return array
   */
  public function defineProperties() {
    return [
      'yearParam' => [
        'title'             => 'Year param',
        'description'       => 'URL parameter to get year from',
        'default'           => 'year',
        'type'              => 'string',
      ],
      'monthParam' => [
        'title'             => 'Month param',
        'description'       => 'URL parameter to get month from',
        'default'           => 'month',
        'type'              => 'string',
      ],
      'dayParam' => [
        'title'             => 'Day param',
        'description'       => 'URL parameter to get day from',
        'default'           => 'day',
        'type'              => 'string',
      ],
      'disqusComments' => [
        'title'             => 'Disqus Comments',
        'description'       => 'If checked, archive will have a column for comments count loaded asynchronously from Disqus',
        'type'              => 'checkbox',
        'default'           => FALSE,
      ],
      'categoryPage' => [
        'title'       => 'rainlab.blog::lang.settings.posts_category',
        'description' => 'rainlab.blog::lang.settings.posts_category_description',
        'type'        => 'dropdown',
        'default'     => 'blog/category',
        'group'       => 'Links',
      ],
      'postPage' => [
        'title'       => 'rainlab.blog::lang.settings.posts_post',
        'description' => 'rainlab.blog::lang.settings.posts_post_description',
        'type'        => 'dropdown',
        'default'     => 'blog/post',
        'group'       => 'Links',
      ],
    ];
  }


  /**
   *
   * Returns pages list for category page selection (copied from blog plugin)
   *
   * @return mixed
   */
  public function getCategoryPageOptions()
  {
    return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
  }

  /**
   *
   * Returns pages list for blog page selection (copied from blog plugin)
   *
   * @return mixed
   */
  public function getPostPageOptions()
  {
    return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
  }


  /**
   *
   * Prepares data to be output to archive. Each row is array with keys:
   *  - published_at
   *  - title (linked to original post)
   *  - post_url - url to post
   *  - category (first category or empty string)
   *  - category_url - url to category
   *
   * @param Post[] $posts posts to output
   * @return array of data prepared to create archive, keyed by months
   */
  protected function preparePosts($posts) {
    $prepared = [];

    foreach ($posts as $post) {
      $month = $this->getMonthName($post->published_at);
      $post->setUrl($this->property('postPage'), $this->controller);
      $category = $post->categories->first();
      if ($category) {
        $category->setUrl($this->property('categoryPage'), $this->controller);
      }
      //arrange posts by month to display separate tables for each month in year archives
      $prepared[$month][] = [
        'published_at' => $post->published_at,
        'title' => $post->title,
        'post_url' => $post->url,
        'category' => ($category) ? $category->name : '',
        'category_url' => ($category) ? $category->url : '',
      ];
    }

    return $prepared;
  }


  /**
   * Sets previous and next years for pager
   */
  protected function setupPager() {
    $pager = new ArchivePager($this->controller, $this->year, $this->month, $this->day);
    $this->previous_text = $pager->previous_text;
    $this->previous_url = $pager->previous_url;
    $this->next_text = $pager->next_text;
    $this->next_url = $pager->next_url;
  }
  

  /**
   *
   * Returns localized month name
   *
   * @param string $date
   * @return string
   */
  protected function getMonthName($date = '') {
    $d = new Carbon($date);
    return $d->formatLocalized('%B');
  }


  /**
   *
   * Returns with start and end dates limiting current archive output
   *
   * @return Carbon[] - [start, end]
   */
  protected function getCurrentRange() {
    if (!$this->year) {
      return [];
    }

    $start = new Carbon();
    $end = new Carbon();

    if (!$this->month) {
      //year archive
      $start->setDate($this->year, 1, 1);
      $end->setDate(intval($this->year) + 1, 1, 1);
    } else if (!$this->day) {
      //month archive
      $start->setDate($this->year, $this->month, 1);
      $end->setDate($this->year, intval($this->month) + 1, 1);
    } else {
      //day archive
      $start->setDate($this->year, $this->month, $this->day);
      $end->setDate($this->year, $this->month, intval($this->day) + 1);
    }

    $start->setTime(0,0,0);
    $end->setTime(0,0,0);

    return [$start, $end];
  }

};
