<?php
/**
 * Class to support archive prev/next page
 * with respect to years, months or dates
 */

namespace Graker\BlogArchive\Classes;

use Carbon\Carbon;
use Cms\Classes\Controller;
use Illuminate\Support\Facades\App;

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

  /**
   * @var Carbon object set to date of the first post
   */
  public $first_date = null;

  /**
   * @var Carbon object set to current date
   */
  public $current_date = null;

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
    $this->setCurrentDate();
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
    if ($this->first_date->year <= ((intval($this->current_year) - 1))) {
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
    $first_date = $this->first_date;
    $current_date = $this->current_date;
    // previous
    if ($first_date->getTimestamp() < $current_date->getTimestamp()) {
      $previous_date = $current_date->copy();
      $previous_date->subMonth(1);
      $this->previous_text = $previous_date->locale(App::getLocale())->translatedFormat('F') . ', ' . $previous_date->year;
      $this->previous_url = $this->makePagerUrl(array(
        'year' => $previous_date->year,
        'month' => $previous_date->month,
      ));
    } else {
      $this->previous_text = $current_date->locale(App::getLocale())->translatedFormat('F') . ', ' . $current_date->year;
      $this->previous_url = '';
    }
    // next
    if (($this->current_year < date('Y')) || ($this->current_month < date('m'))) {
      $next_date = $current_date->copy();
      $next_date->addMonth(1);
      $this->next_text = $next_date->locale(App::getLocale())->translatedFormat('F') . ', ' . $next_date->year;
      $this->next_url = $this->makePagerUrl(array(
        'year' => $next_date->year,
        'month' => $next_date->month,
      ));
    } else {
      $this->next_text = $current_date->locale(App::getLocale())->translatedFormat('F') . ', ' . $current_date->year;
      $this->next_url = '';
    }
  }

  /**
   * Sets up pager to switch prev/next day
   */
  protected function setupDayPager()
  {
    $first_date = $this->first_date;
    $current_date = $this->current_date;
    // previous
    if ($first_date->getTimestamp() < $current_date->getTimestamp()) {
      $previous_date = $current_date->copy();
      $previous_date->subDay(1);
      $this->previous_text = $previous_date->formatLocalized('%d') . ' ' . $previous_date->locale(App::getLocale())->translatedFormat('F') . ', ' . $previous_date->year;
      $this->previous_url = $this->makePagerUrl(array(
        'year' => $previous_date->year,
        'month' => $previous_date->month,
        'day' => $previous_date->day,
      ));
    } else {
      $this->previous_text = $current_date->formatLocalized('%d') . ' ' . $current_date->locale(App::getLocale())->translatedFormat('F') . ', ' . $current_date->year;
      $this->previous_url = '';
    }
    // next
    $today = new Carbon();
    $today->setTime(0, 0);
    if ($this->current_date->getTimestamp() < $today->getTimestamp()) {
      $next_date = $current_date->copy();
      $next_date->addDay(1);
      $this->next_text = $next_date->formatLocalized('%d') . ' ' . $next_date->locale(App::getLocale())->translatedFormat('F') . ', ' . $next_date->year;
      $this->next_url = $this->makePagerUrl(array(
        'year' => $next_date->year,
        'month' => $next_date->month,
        'day' => $next_date->day,
      ));
    } else {
      $this->next_text = $current_date->formatLocalized('%d') . ' ' . $current_date->locale(App::getLocale())->translatedFormat('F') . ', ' . $current_date->year;
      $this->next_url = '';
    }
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

  /**
   *
   * Sets first date and current date obects
   *
   */
  protected function setCurrentDate()
  {
    $this->first_date = self::getFirstDate();
    $this->current_date = new Carbon();
    // set current day to 1 if unset (so it won't be 0 because 0 is the last day of previous month)
    $this->current_date->setDate($this->current_year, $this->current_month, ($this->current_day) ? $this->current_day : 1);
    $this->current_date->setTime(0, 0);
  }
}
