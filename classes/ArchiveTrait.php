<?php
/**
 * Trait with helper functions for archive
 */

namespace Graker\BlogArchive\Classes;

use RainLab\Blog\Models\Post as BlogPost;
use Carbon\Carbon;

trait ArchiveTrait
{
  /**
   *
   * Returns last post's published_at timestamp for current year
   * or $year/12/31 00:00:00 timestamp for other years
   *
   * @param $year
   * @return int
   */
  protected static function getMtime($year) {
    if ($year == date('Y')) {
      $post = BlogPost::orderBy('published_at', 'desc')->isPublished()->first();
      if ($post) {
        $date = new Carbon($post->published_at);
      } else {
        // if no posts, set date to the first of january
        $date = new Carbon();
        $date->setDateTime(date('Y'), 1, 1, 0, 0, 0);
      }
    } else {
      // previous year
      $date = new Carbon();
      $date->setDateTime($year, 12, 31, 0, 0, 0);
    }

    return $date->getTimestamp();
  }


  /**
   *
   * Returns date (Carbon object) set to the date of the very first post
   * or to current date if there are no posts
   *
   * @return Carbon
   */
  protected static function getFirstDate() {
    $post = BlogPost::orderBy('published_at', 'asc')->isPublished()->first();
    if (!$post) {
      $date = new Carbon();
    } else {
      $date = new Carbon($post->published_at);
    }
    $date->setTime(0, 0);
    return $date;
  }


  /**
   *
   * Returns year of the very first published post
   *
   * @return string
   */
  protected static function getStartYear() {
    $date = self::getFirstDate();
    return $date->year;
  }

}
