<?php namespace Graker\BlogArchive\Components;

use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use Illuminate\Support\Facades\DB;
use RainLab\Blog\Models\Post as Post;

class RandomPosts extends ComponentBase
{

  public function componentDetails()
  {
    return [
      'name'        => 'Random Posts',
      'description' => 'Output predefined number of random blog posts'
    ];
  }

  public function defineProperties()
  {
    return [
      'postsCount' => [
        'title'             => 'Posts to output',
        'description'       => 'Amount of random posts to output',
        'default'           => 5,
        'type'              => 'string',
        'validationMessage' => 'Posts count must be a number',
        'validationPattern' => '^[0-9]+$',
        'required'          => FALSE,
      ],
      'cacheLifetime' => [
        'title'             => 'Cache Lifetime',
        'description'       => 'Number of minutes selected posts are stored in cache. 0 for no caching.',
        'default'           => 0,
        'type'              => 'string',
        'validationMessage' => 'Cache lifetime must be a number',
        'validationPattern' => '^[0-9]+$',
        'required'          => FALSE,
      ],
      'postPage' => [
        'title'       => 'rainlab.blog::lang.settings.posts_post',
        'description' => 'rainlab.blog::lang.settings.posts_post_description',
        'type'        => 'dropdown',
        'default'     => 'blog/post',
      ],
    ];
  }

  /**
   *
   * Returns pages list for album page select box setting
   *
   * @return mixed
   */
  public function getPostPageOptions() {
    return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
  }
  
  
  /**
   *
   * Returns array of posts_count random posts either from DB or cache
   *
   * @return Post[]
   */
  public function posts() {
    //TODO add caching
    $posts = $this->getPosts();
    return $posts;
  }


  /**
   *
   * Returns array of post_count random posts
   *
   * @return Post[]
   */
  protected function getPosts() {
    $count = $this->property('postsCount');
    $posts = Post::orderBy(DB::raw('RAND()'))
      ->take($count)
      ->get();

    foreach ($posts as $post) {
      $post->url = $post->setUrl($this->property('postPage'), $this->controller);
    }

    return $posts;
  }

}
