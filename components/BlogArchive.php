<?php

namespace Graker\BlogArchive\Components;

use Carbon\Carbon;
use Rainlab\Blog\Models\Post;
use Cms\Classes\Page;

class BlogArchive extends \Cms\Classes\ComponentBase {

  /**
   * @var string year to display archive for
   */
  protected $year = '';

  /**
   * @var string month to display archive for
   */
  protected $month = '';

  /**
   * @var string day to display archive for
   */
  protected $day = '';

  /**
   * Returns information about this component, including name and description.
   */
  public function componentDetails() {
    return [
      'name' => 'Blog Archive',
      'description' => 'Displays an archive of blog posts by year and (optionally) month given',
    ];
  }

  //TODO add partial for month/day to output posts as table lines
  //TODO add partial for year to output 12 tables of posts
  //TODO need to validate year/month/day parts prior to use them (got exception when tampering with urls)

  /**
   *
   * Returns array of archive posts
   * Uses properties: year, month, day to figure out archive page settings
   *
   * @return array
   */
  public function archivePosts() {
    list($start, $end) = $this->getCurrentRange();
    $posts = Post::where('published_at', '>=', $start)->where('published_at', '<', $end)->with('categories')->get();

    return $this->preparePosts($posts);
  }


  /**
   * Component initialization
   * Figure out archive parameters and save them to properties
   */
  public function init() {
    //TODO validate params (or add validation to route)
    $this->year = $this->param($this->property('yearParam'));
    $this->month = $this->param($this->property('monthParam'));
    $this->day = $this->param($this->property('dayParam'));
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
   * @return array of data prepared to create archive
   */
  protected function preparePosts($posts) {
    $prepared = [];

    foreach ($posts as $post) {
      $post->setUrl($this->property('postPage'), $this->controller);
      $category = $post->categories->first();
      if ($category) {
        $category->setUrl($this->property('categoryPage'), $this->controller);
      }
      $prepared[] = [
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
