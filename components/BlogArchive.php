<?php

namespace Graker\BlogArchive\Components;

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
  //TODO add function to prepare post limits (from - to)

  /**
   *
   * Returns array of archive posts
   * Uses properties: year, month, day to figure out archive page settings
   *
   * @return array
   */
  public function archivePosts() {
    return ['Here be dragons', $this->year, $this->month, $this->day, 'shiii'];
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
    ];
  }

};
