<?php namespace Graker\BlogArchive;

use Backend;
use System\Classes\PluginBase;

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
   * Registers any back-end permissions used by this plugin.
   *
   * @return array
   */
  public function registerPermissions()
  {
    return []; // Remove this line to activate

    return [
      'graker.blogarchive.some_permission' => [
        'tab' => 'BlogArchive',
        'label' => 'Some permission'
      ],
    ];
  }

  /**
   * Registers back-end navigation items for this plugin.
   *
   * @return array
   */
  public function registerNavigation()
  {
    return []; // Remove this line to activate

    return [
      'blogarchive' => [
        'label'       => 'BlogArchive',
        'url'         => Backend::url('graker/blogarchive/mycontroller'),
        'icon'        => 'icon-leaf',
        'permissions' => ['graker.blogarchive.*'],
        'order'       => 500,
      ],
    ];
  }

}
