<?php

namespace Graker\BlogArchive\Tests;

use PluginTestCase;
use RainLab\Blog\Models\Category;
use Rainlab\Blog\Models\Post;
use Graker\BlogArchive\Components\BlogArchive;
use Carbon\Carbon;
use Cms\Classes\ComponentManager;
use Cms\Classes\Page;
use Cms\Classes\Layout;
use Cms\Classes\Controller;
use Cms\Classes\Theme;
use Cms\Classes\CodeParser;
use Faker;

class BlogArchiveTest extends PluginTestCase {


  /**
   * Test blog archive component
   * General functionality test
   */
  public function testBlogArchive() {
    // remove all existing posts
    $all = Post::all();
    foreach ($all as $post) {
      $post->delete();
    }

    // create categories
    $categories = [];
    $categories[0] = $this->createCategory();

    // create some posts
    $posts = [];
    $posts[0] = $this->createPost($categories[0], Carbon::createFromDate(2017, 1, 1));
    $posts[1] = $this->createPost($categories[0], Carbon::createFromDate(2017, 2, 20));
    $posts[2] = $this->createPost($categories[0], Carbon::createFromDate(2017, 3, 3));
    $posts[3] = $this->createPost($categories[0], Carbon::createFromDate(2017, 4, 1));
    $posts[4] = $this->createPost($categories[0], Carbon::createFromDate(2017, 4, 15));
    $posts[5] = $this->createPost($categories[0], Carbon::createFromDate(2016, 12, 31));
    $posts[6] = $this->createPost($categories[0], Carbon::createFromDate(2016, 11, 3));
    $posts[7] = $this->createPost($categories[0], Carbon::createFromDate(2015, 5, 14));
    $posts[8] = $this->createPost($categories[0], Carbon::createFromDate(2015, 5, 14));

    // check that component returns pots 0-4 for year 2017
    $component = $this->createBlogArchiveComponent(['yearParam' => '2017']);
    $component->init();
    $archive_posts = $component->archivePosts();
    self::assertEquals(4, count($archive_posts), 'There are 4 months shown for 2017');
    self::assertEquals($posts[0]->title, $archive_posts['January'][0]['title'], 'January post is ser correctly');
    self::assertEquals($posts[1]->title, $archive_posts['February'][0]['title'], 'February post is ser correctly');
    self::assertEquals($posts[2]->title, $archive_posts['March'][0]['title'], 'March post is ser correctly');
    self::assertEquals($posts[4]->title, $archive_posts['April'][0]['title'], 'April first post is set correctly');
    self::assertEquals($posts[3]->title, $archive_posts['April'][1]['title'], 'April second post is set correctly');

    // check month archive (2 posts in april 2017)
    $component = $this->createBlogArchiveComponent(['yearParam' => '2017', 'monthParam' => '4']);
    $component->init();
    $archive_posts = $component->archivePosts();
    self::assertEquals(1, count($archive_posts), 'There is 1 month in archive');
    self::assertEquals(2, count($archive_posts['April']), 'There are 2 posts in archive');

    // check empty archive for 2014
    $component = $this->createBlogArchiveComponent(['yearParam' => '2014']);
    $component->init();
    $archive_posts = $component->archivePosts();
    self::assertEmpty($archive_posts, 'No posts for 2014');

    // check 2 months returned for 2016
    $component = $this->createBlogArchiveComponent(['yearParam' => '2016']);
    $component->init();
    $archive_posts = $component->archivePosts();
    self::assertEquals(2, count($archive_posts), 'There are 2 months for 2016');

    // check day archive for 2015
    $component = $this->createBlogArchiveComponent(['yearParam' => '2015', 'monthParam' => '5', 'dayParam' => '14']);
    $component->init();
    $archive_posts = $component->archivePosts();
    self::assertEquals(1, count($archive_posts), 'There is 1 month for 2015');
    self::assertEquals(2, count($archive_posts['May']), 'There are 2 posts in May 2015');
  }


  /**
   * Tests for blog archive limited by category
   */
  public function testCategoryBlogArchive() {
    // remove all existing posts
    $all = Post::all();
    foreach ($all as $post) {
      $post->delete();
    }

    // create categories
    $categories = [];
    $categories[0] = $this->createCategory();
    $categories[1] = $this->createCategory();
    $categories[2] = $this->createCategory();

    // create some posts (put them in one month to check easier)
    $posts = [];
    $posts[0] = $this->createPost($categories[0], Carbon::createFromDate(2017, 1, 1));
    $posts[1] = $this->createPost($categories[0], Carbon::createFromDate(2017, 1, 2));
    $posts[2] = $this->createPost($categories[0], Carbon::createFromDate(2017, 1, 3));
    $posts[3] = $this->createPost($categories[1], Carbon::createFromDate(2017, 1, 4));
    $posts[4] = $this->createPost($categories[2], Carbon::createFromDate(2017, 1, 5));
    $posts[5] = $this->createPost($categories[2], Carbon::createFromDate(2017, 1, 6));
    $posts[6] = $this->createPost($categories[2], Carbon::createFromDate(2017, 1, 7));
    $posts[7] = $this->createPost($categories[2], Carbon::createFromDate(2017, 1, 8));

    // check category 0
    $component = $this->createBlogArchiveComponent(['yearParam' => '2017', 'categoryParam' => $categories[0]->slug]);
    $component->init();
    $archive_posts = $component->archivePosts();
    self::assertEquals(3, count($archive_posts['January']), '3 posts for category 0');

    // check category 1
    $component = $this->createBlogArchiveComponent(['yearParam' => '2017', 'categoryParam' => $categories[1]->slug]);
    $component->init();
    $archive_posts = $component->archivePosts();
    self::assertEquals(1, count($archive_posts['January']), '1 posts for category 1');

    // check category 2
    $component = $this->createBlogArchiveComponent(['yearParam' => '2017', 'categoryParam' => $categories[2]->slug]);
    $component->init();
    $archive_posts = $component->archivePosts();
    self::assertEquals(4, count($archive_posts['January']), '4 posts for category 2');
  }

  /**
   *
   * Creates post with category given and makes it published on provided date and time
   *
   * @param \RainLab\Blog\Models\Category $category
   * @param \Carbon\Carbon $published_at
   * @return mixed
   */
  protected function createPost(Category $category = NULL, Carbon $published_at = NULL) {
    $faker = Faker\Factory::create();
    $post = new Post();
    $post->title = $faker->sentence(3);
    $post->slug = str_slug($post->title);
    $post->content = $faker->text();
    if ($published_at) {
      $post->published = TRUE;
      $post->published_at = $published_at;
    }
    if ($category) {
      $post->categories = [$category];
    }

    $post->save();
    return $post;
  }


  /**
   *
   * Creates test category and returns it
   *
   * @return \RainLab\Blog\Models\Category
   */
  protected function createCategory() {
    $faker = Faker\Factory::create();
    $category = new Category();
    $category->name = $faker->sentence(2);
    $category->slug = str_slug($category->name);
    $category->save();
    return $category;
  }


  /**
   *
   * Creates BlogArchive component to test
   *
   * @param array $options array of component options to override default ones
   * @return \Graker\BlogArchive\Components\BlogArchive
   */
  protected function createBlogArchiveComponent($options = array()) {
    // Spoof all the objects we need to make a page object
    $theme = Theme::load('test');
    $page = Page::load($theme, 'index.htm');
    $layout = Layout::load($theme, 'content.htm');
    $controller = new Controller($theme);
    $parser = new CodeParser($page);
    $pageObj = $parser->source($page, $layout, $controller);
    $manager = ComponentManager::instance();
    $object = $manager->makeComponent('blogArchive', $pageObj, $options);
    return $object;
  }

}
