<?php namespace Graker\BlogArchive;

use Backend;
use Backend\Widgets\Form;
use System\Classes\PluginBase;
use Carbon\Carbon;
use Lang;
use App;
use Event;
use Log;

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
      'Graker\BlogArchive\Components\RandomPosts' => 'randomPosts',
    ];
  }


  /**
   * Setup locale to use for month names
   */
  public function boot() {
    $this->setLocaleForDates();
    //TODO introduce settings for both this matters
    $this->extendBlogPostForm();
  }


  /**
   * Set locale to have months translated in archive view
   */
  protected function setLocaleForDates() {
    $localeCode = App::getLocale();
    Carbon::setLocale($localeCode);
    setlocale(LC_TIME, $localeCode . '_' . strtoupper($localeCode) . '.UTF-8');
  }


  /**
   * Extends form to edit Blog Post
   *  - add button for Typographus.Lite.UTF8
   */
  protected function extendBlogPostForm() {
    Event::listen('backend.form.extendFields', function (Form $widget) {
      // attach to post forms only
      if (!($widget->getController() instanceof \RainLab\Blog\Controllers\Posts)) {
        return ;
      }
      if (!($widget->model instanceof \RainLab\Blog\Models\Post)) {
        return ;
      }

      //add javascript extending Markdown editor
      $widget->addJs('/plugins/graker/blogarchive/assets/js/typofilter.js');
      $widget->addJs('/plugins/graker/blogarchive/assets/js/typofilter-markdown-extend.js');
    });
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
