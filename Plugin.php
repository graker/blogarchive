<?php namespace Graker\BlogArchive;

use Backend;
use System\Classes\PluginBase;
use Carbon\Carbon;
use Lang;
use App;

/**
 * BlogArchive Plugin Information File
 */
class Plugin extends PluginBase
{

  /**
   * @var array plugin dependencies
   */
  public $require = ['RainLab.Blog'];

  /**
   * Returns information about this plugin.
   *
   * @return array
   */
  public function pluginDetails()
  {
    return [
      'name'        => 'BlogArchive',
      'description' => 'Provides archive pages to display posts per month or per year in a table manner',
      'author'      => 'Graker',
      'icon'        => 'icon-leaf'
    ];
  }

  /**
   * Registers any front-end components implemented in this plugin.
   *
   * @return array
   */
  public function registerComponents()
  {
    return [
      'Graker\BlogArchive\Components\BlogArchive' => 'blogArchive',
    ];
  }


  /**
   * Setup locale to use for month names
   */
  public function boot() {
    $localeCode = App::getLocale();
    Carbon::setLocale($localeCode);
    setlocale(LC_TIME, $localeCode . '_' . strtoupper($localeCode) . '.UTF-8');
  }


  /**
   * register() method implementation
   *  - register console commands here
   */
  public function register() {
    $this->registerConsoleCommand('blogarchive.d6_preprocess_import', 'Graker\BlogArchive\Console\Drupal6ImportPreprocessor');
  }


  /**
   * Registers any back-end permissions used by this plugin.
   *
   * @return array
   */
  public function registerPermissions()
  {
    return [];
  }

  /**
   * Registers back-end navigation items for this plugin.
   *
   * @return array
   */
  public function registerNavigation()
  {
    return [];
  }

}
