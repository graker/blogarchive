<?php

namespace Graker\BlogArchive\Tests;

use PluginTestCase;
use Graker\BlogArchive\Components\RandomPosts;
use Rainlab\Blog\Models\Post;
use Carbon\Carbon;
use Cms\Classes\ComponentManager;
use Cms\Classes\Page;
use Cms\Classes\Layout;
use Cms\Classes\Controller;
use Cms\Classes\Theme;
use Cms\Classes\CodeParser;

class RandomPostsTest extends PluginTestCase {

  /**
   * Tests that random posts are generated
   */
  public function testRandomPosts() {
    // remove all existing posts
    $all = Post::all();
    foreach ($all as $post) {
      $post->delete();
    }

    // create some Posts
    $posts = [];
    $posts[0] = $this->createPost(0, TRUE);
    $posts[1] = $this->createPost(1, TRUE);
    $posts[2] = $this->createPost(2, TRUE);
    $posts[3] = $this->createPost(3, TRUE);
    $posts[4] = $this->createPost(4, TRUE);
    $posts[5] = $this->createPost(5, TRUE);
    $posts[6] = $this->createPost(6, TRUE);

    $random_posts = $this->createRandomPostsComponent();
    $generated_posts = $random_posts->posts();

    // amount of posts should be equal to default value
    self::assertEquals(5, count($generated_posts));

    // ensure all Posts are from posts created
    $found_all = TRUE;
    foreach ($generated_posts as $generated_post) {
      $found = FALSE;
      foreach ($posts as $post) {
        if ($post->id == $generated_post->id) {
          $found = TRUE;
          break;
        }
      }
      if (!$found) {
        $found_all = FALSE;
        break;
      }
    }
    self::assertTrue($found_all, 'All posts exist in original array');

    // check for non-repeating
    $ids = [];
    foreach ($generated_posts as $post) {
      self::assertArrayNotHasKey($post->id, $ids, 'Post is not already present in the generated array');
      $ids[$post->id] = $post->id;
    }
  }


  /**
   * Tests that random posts won't contain unpublished posts
   */
  public function testRandomPostsUnpublished() {
    // create posts, some of them unpublished (note that there is seeded post with id=1)
    $posts = [];
    $posts[0] = $this->createPost(0, TRUE);
    $posts[1] = $this->createPost(1, FALSE);
    $posts[2] = $this->createPost(2, TRUE);
    $posts[3] = $this->createPost(3, FALSE);


    // get random posts of same quantity
    $random_posts = $this->createRandomPostsComponent();
    $generated_posts = $random_posts->posts();

    // ensure there are no unpublished posts in the array
    $ids = $generated_posts->pluck('id');
    self::assertTrue(in_array($posts[0]->id, $ids->all()), 'Published post is in generated array');
    self::assertFalse(in_array($posts[1]->id, $ids->all()), 'Unpublished post is not in generated array');
    self::assertFalse(in_array($posts[2]->id, $ids->all()), 'Unpublished post is not in generated array');
  }


  /**
   *
   * Creates RandomPosts component to test
   *
   * @return \Graker\BlogArchive\Components\RandomPosts
   */
  protected function createRandomPostsComponent() {
    // Spoof all the objects we need to make a page object
    $theme = Theme::load('test');
    $page = Page::load($theme, 'index.htm');
    $layout = Layout::load($theme, 'content.htm');
    $controller = new Controller($theme);
    $parser = new CodeParser($page);
    $pageObj = $parser->source($page, $layout, $controller);
    $manager = ComponentManager::instance();
    $object = $manager->makeComponent('randomPosts', $pageObj);
    return $object;
  }


  /**
   *
   * Creates post and makes it published if needed
   *
   * @param int $index - post number
   * @param bool $published
   * @return mixed
   */
  protected function createPost($index, $published = TRUE) {
    $post = new Post();
    $post->title = 'Some title ' . $index;
    $post->slug = 'some_slug_' . $index;
    $post->content = 'Post content ' . $index;
    if ($published) {
      $post->published = TRUE;
      // make it 10 seconds ago because isPublished() scope use '<' for comparison
      $post->published_at = Carbon::createFromTimestamp(time() - 10);
    }
    $post->save();
    return $post;
  }

}
