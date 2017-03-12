<?php namespace Graker\BlogArchive\Console;

/**
 * Goes over posts with excerpt set and re-save them
 */

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Rainlab\Blog\Models\Post;

class UpdateExcerpts extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'blogarchive:updateexcerpts';

    /**
     * @var string The console command description.
     */
    protected $description = 'Re-save blog posts with excerpts';

    /**
     * Execute the console command.
     * @return void
     */
    public function fire()
    {
      $this->output->writeln('Hello world!');
      $posts = Post::where('excerpt', '<>', '')->get();
      $this->output->writeln('Posts with excerpts: ' . count($posts));
      if (!$this->confirm('Are you sure to re-save all of these posts?')) {
        $this->output->writeln('Cancelled');
        return ;
      }

      foreach ($posts as $post) {
        // just call save and presave callback will do all the job
        $this->output->writeln('Saving ' . $post->title);
        $post->save();
      }
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }
}
