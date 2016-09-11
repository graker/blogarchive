<?php
/**
 * Class to support archive prev/next page
 * with respect to years, months or dates
 */

namespace Graker\BlogArchive\Classes;

use Carbon\Carbon;
use Cms\Classes\Controller;

class ArchivePager
{

  use ArchiveTrait;

  /**
   * @var Controller to set urls
   */
  public $controller = null;

  public $current_year = 0;
  public $current_month = 0;
  public $current_day = 0;

  public $first_year = 0;

  /*
   * Text and url params to use in pagination
   */
  public $previous_text = '';
  public $previous_url = '';
  public $next_text = '';
  public $next_url = '';

  /**
   * ArchivePager constructor.
   * @param Controller $controller pass controller to use to set pager urls
   * @param int $year
   * @param int $month
   * @param int $day
   */
  public function __construct(Controller $controller, $year, $month = 0, $day = 0)
  {
    $this->controller = $controller;
    $this->current_day = $day;
    $this->current_month = $month;
    $this->current_year = $year;
    $this->first_year = self::getStartYear();
    $this->setupPager();
  }

  /**
   * Sets pager variables by calling different functions for day, month or year pager
   */
  protected function setupPager()
  {
    if ($this->current_day) {
      $this->setupDayPager();
    } else if ($this->current_month) {
      $this->setupMonthPager();
    } else {
      $this->setupYearPager();
    }
  }

  /**
   * Sets up pager to switch prev/next year
   */
  protected function setupYearPager()
  {
    if ($this->first_year <= ((intval($this->current_year) - 1))) {
      $prev_year = intval($this->current_year) - 1;
      $this->previous_text = $prev_year;
      $this->previous_url = $this->makePagerUrl(array('year' => $prev_year));
    } else {
      $this->previous_text = $this->current_year;
      $this->previous_url = '';
    }

    if ($this->current_year < intval(date('Y'))) {
      $next_year = intval($this->current_year) + 1;
      $this->next_text = $next_year;
      $this->next_url = $this->makePagerUrl(array('year' => $next_year));
    } else {
      $this->next_text = $this->current_year;
      $this->next_url = '';
    }
  }

  /**
   * Sets up pager to switch prev/next month
   */
  protected function setupMonthPager()
  {
    $first_date = self::getFirstDate();
    $current_date = new Carbon();
    $current_date->setDate($this->current_year, $this->current_month, 1);
    $current_date->setTime(0, 0);
    // previous
    if ($first_date->getTimestamp() < $current_date->getTimestamp()) {
      $previous_date = $current_date->copy();
      $previous_date->subMonth(1);
      $this->previous_text = $previous_date->formatLocalized('%B') . ', ' . $previous_date->year;
      $this->previous_url = $this->makePagerUrl(array(
        'year' => $previous_date->year,
        'month' => $previous_date->month,
      ));
    } else {
      $this->previous_text = $current_date->formatLocalized('%B') . ', ' . $current_date->year;
      $this->previous_url = '';
    }
    // next
    if (($this->current_year < date('Y')) || ($this->current_month < date('m'))) {
      $next_date = $current_date->copy();
      $next_date->addMonth(1);
      $this->next_text = $next_date->formatLocalized('%B') . ', ' . $next_date->year;
      $this->next_url = $this->makePagerUrl(array(
        'year' => $next_date->year,
        'month' => $next_date->month,
      ));
    } else {
      $this->next_text = $current_date->formatLocalized('%B') . ', ' . $current_date->year;
      $this->next_url = '';
    }
  }

  protected function setupDayPager()
  {
    // TODO implement
  }

  /**
   *
   * Generates url for params given
   *
   * @param array $params
   * @return string
   */
  protected function makePagerUrl($params) {
    $page = $this->controller->getPage();
    return $this->controller->pageUrl($page->getBaseFileName(), $params);
  }
}
