# Blog Archive plugin

This is a plugin extending OctoberCMS [Blog plugin](http://octobercms.com/plugin/rainlab-blog).
It creates a component to add blog archive to any page. Blog archive outputs blog posts for year, month or day given
in a table manner, sorted by published date and grouped by months.

## How to use

* Enable the plugin
* Create a page with year (required), month (optional) and day (optional) parameters, for example, set page path like this: `/news/:year/:month?/:day?`
* Add blogArchive component to this page
* In component settings, set up year, month and day parameters the usual way
* You can also use category parameter to set category slug to limit archive to this category
* Select category and post pages for links to these pages from archive to work
* If your blog comments are implemented with Disqus, check the Disqus Comments box to add comments count column to the archive table
* Output the component on your page as usual

Note that to output translated month names via `Carbon` in the archive table, the component will use `setlocale()` to set the application locale.
You can disable this feature in `config/config.php` file of the plugin (set `setLocaleForCarbon` value to `FALSE` in the overridden config file).

### Blog excerpts and Markdown

By default, only Post text is processed by Blog plugin with Markdown filter. As for Post excerpt, it is saved as is without markdown support. So you have to either type excerpt without markdown or output it using pipe filter: `{{ excerpt | md }}`. To improve this behavior, post.beforeSave listener is added in BlogArchive plugin. In this listener, post excerpt will be processed with Markdown filter whenever post is being saved.

In addition, to process old post excerpts, artisan command `blogarchive:updateexcerpts` had been added. It would find all existing posts with non-empty excerpt fields and re-save them.

### Random posts component

Also there is Random Posts component which you can use to display some random post titles (or more than titles if you override default markup). 
Just add the component to a page or partial, set up number of posts to show, cache lifetime and blog post page name.
Note though, that for big databases selects with random sorting can slow down your site so use with caution and make use of cache lifetime.
Also note that due to the use of RAND() function for sorting, the component would work with MySQL database only. 
To use component with other databases, you'd need to rewrite orderBy() call. 
And apparently there's no general DB-independent method in Laravel to do random sorting.

### Sitemap and Pages integration

Archive pages are integrated into [Sitemap](https://octobercms.com/plugin/rainlab-sitemap). 
You can add "All Archive Years" item at Sitemap settings and have a link to each year archive in the sitemap.xml, starting from the first year you have published posts on. 
And since [Static Pages](http://octobercms.com/plugin/rainlab-pages) plugin shares the integration events with Sitemap, you can also add archive years as static menu items.

### Typofilter integration

To perform some typographical transforms and replacements, [Typofilter.js](https://github.com/graker/typofilter.js) is integrated in this plugin. 
The button (with a magic wand icon) will be added to Markdown editor for blog posts automatically.

You can disable this button in `config/config.php` file of the plugin (set `addTypofilterToMarkdown` value to `FALSE` in the overridden config file).

## Drupal6 export processor

When I was migrating data from Drupal 6 to October, I've created **blogarchive:d6_preprocess_import** artisan command to preprocess CSV with exported nodes. 
While this command not nearly is "the migration out of the box", it might be a starting point for someone, so I share it here with a short manual following.

### Requirements

To migrate from Drupal 6 nodes to Rainlab.Blog posts you'll need to export nodes from D6 to CSV file. 
The best way to do this is to generate CSV automatically with [Views Data Export](http://https://www.drupal.org/project/views_data_export) module. 
You'll need to export columns:

* nid
* title
* content html
* teaser
* link to the node (just use standard views field generating the link)
* created date
* updated date
* taxonomy terms

When the file is ready, you can process it with the command prior to importing data to October. Check out arguments and options with:

php artisan blogarchive:d6_preprocess_import -h

For the command to work properly you CSV file must meet some requirements:

* first row must be of column titles
* first column must be unique id (e.g. node id)
* content column title must be Content
* teaser column title must be Teaser
* link column title must be Link
* taxonomy terms column title must be Categories

Columns order doesn't matter, the script will find columns by titles.

For now, the command can help you with following

### Remove teasers if same as content

By default for nodes not having a teaser Drupal 6 would export a copy of full content field. But we don't want to save full content field to excerpt, we want it blank in this case.
So the command will remove (replace with empty strings) teasers when they are equal to node's full content.
 
### Extract slugs from node links

While Drupal 6 nodes could have complex path aliases, in October's blog posts slugs shouldn't contain variable parts. So the command will pick href attribute from 
the link field and save the part after last slash to be used as a slug.

### Prepare categories

By default, Drupal 6 Views would export tags separated by commas, while blog's import expects them to be separated by pipes. The command would replace commas with pipes.

### Fix paths to uploaded files
 
The command will scan full content and teaser columns for anchor and image tags containing links to /sites/default/files/\*. 
For each such tag, the link will be replaced with a new path you provided in --file option. So you can just copy sites/default/files contents 
from the old site to new path and all links to images and other documents will be available after migration.

### Convert Lightbox links to Magnific

Optional replacing rel="lightbox" with class="magnific" for all lightbox anchor tags. Use --lightbox-to-magnific option to enable.

### Prepare code tags for Prettify

This option will modify code samples in content to use [Prettify](https://github.com/google/code-prettify) script which installs with October. 
Use --code-to-prettify to enable. Then the command would look for &lt;code&gt; tags, remove &lt;br/&gt; from this tag's content. 
If &lt;code&gt; tag is found inside of &lt;p&gt;, it will be moved outside after the parent (otherwise, Prettify won't work).
Then the tag will be wrapped with &lt;pre class="prettyprint"&gt;&lt;/pre&gt; so it can be prettified.
&lt;javascript&gt;, &lt;cpp&gt;, &lt;php&gt;, &lt;drupal6&gt;, &lt;qt&gt; and &lt;bash&gt; tags will be transformed to &lt;code&gt; tag with corresponding language class and then prettified too. 

That's it. After the command is finished working try to import the resulting file in October.


