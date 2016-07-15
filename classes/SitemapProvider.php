<?php

namespace Graker\BlogArchive\Classes;

use Carbon\Carbon;
use Cms\Classes\Page as CmsPage;
use Cms\Classes\Theme;
use RainLab\Pages\Classes\MenuItem;
use RainLab\Blog\Models\Post as BlogPost;

/**
 * Class SitemapProvider
 * XML sitemap integration
 */
class SitemapProvider {
    
  /**
   *
   * Returns array of items info for sitemap
   *
   * @return array
   */
  public static function listTypes() {
    return [
      'all-archive-years' => 'All Archive Years',
    ];
  }
  
  
  /**
   *
   * Returns an array of info about menu item type
   *
   * @param string $type item name
   * @return array
   */
  public static function getMenuTypeInfo($type) {
    $result = [];
    
    if ($type != 'all-archive-years') {
      return $result;
    }
    
    $result['dynamicItems'] = true;
    
    $theme = Theme::getActiveTheme();
    $result['cmsPages'] = CmsPage::listInTheme($theme, true);
    
    return $result;
  }
  
  
  /**
   *
   * Generates sitemap elements for this menu item
   *
   * @param MenuItem $item
   * @param $url
   * @param $theme
   * @return array
   */
  public static function resolveMenuItem($item, $url, $theme) {
    $result = [];
    
    if (!$item->cmsPage) {
      return $result;
    }
    
    $result['items'] = [];
    
    $first_year = self::getStartYear();
    $last_year = date('Y');
    
    for ($i=$first_year; $i<= $last_year; $i++) {
      $result['items'][] = [
        'title' => "Archive for year $i",
        'url' => self::getUrl($i, $item->cmsPage, $theme),
        'mtime' => self::getMtime($i),
      ];
    }
    
    return $result;
  }
  
  
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
   * Returns year of the very first published post
   *
   * @return string
   */
  protected static function getStartYear() {
    $post = BlogPost::orderBy('published_at', 'asc')->isPublished()->first();
    
    if (!$post) {
      // there are no posts
      return date('Y');
    }
    
    // return published_at year
    $published = new Carbon($post->published_at);
    return $published->year;
  }
  
  
  /**
   *
   * Generates url for the item to be resolved
   *
   * @param int $year - year number
   * @param string $pageCode - page code to be used
   * @param $theme
   * @return string
   */
  protected static function getUrl($year, $pageCode, $theme) {
    $page = CmsPage::loadCached($theme, $pageCode);
    if (!$page) return '';
  
    $properties = $page->getComponentProperties('blogArchive');
    if (!isset($properties['yearParam'])) {
      return '';
    }
  
    $paramName = $properties['yearParam'];
    $url = CmsPage::url($page->getBaseFileName(), [$paramName => $year]);
  
    return $url;
  }
}
