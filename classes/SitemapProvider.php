<?php

namespace Graker\BlogArchive\Classes;

use Cms\Classes\Page as CmsPage;
use Cms\Classes\Theme;
use RainLab\Pages\Classes\MenuItem;

/**
 * Class SitemapProvider
 * XML sitemap integration
 */
class SitemapProvider {

    use ArchiveTrait;

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

        // get year url param and strip it of {{ :<name> }} to get pure name
        $paramName = str_replace(array('{', '}', ' ', ':'), '', $properties['yearParam']);
        $url = CmsPage::url($page->getBaseFileName(), [$paramName => $year]);

        return $url;
    }
}
