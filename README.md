# Blog Archive plugin

This is a plugin extending OctoberCMS [Blog plugin](http://octobercms.com/plugin/rainlab-blog).
It creates a component to add blog archive to any page. Blog archive outputs blog posts for year, month or day given
in a table manner, sorted by published date and grouped by months.

## How to use

* Enable the plugin
* Create a page with year (required), month (optional) and day (optional) parameters
* Add blogArchive component to this page
* In component settings, set up names of year, month and day parameters
* Select category and post pages for links to these pages from archive to work
* In the Links group
* If your blog comments are implemented with Disqus, check the Disqus Comments box to add comments count column to the archive table
* Output the component on your page as usual

## Drupal6 export processor

The plugin also adds a command for artisan: blogarchive:d6_preprocess_import, it is supposed to process nodes export from Drupal's views_data_export to be imported in the Rainlab.Blog model. Check -h to find out command options and arguments.
This command can be used to preprocess CSV with blog posts import. I created it for my own migration from Drupal 6 to OctoberCMS but I think maybe this command could become a starting point for someone else's migration.

Main features:

* remove teaser text if it is equal to content text (to avoid saving excerpts in this case) as D6 would save teaser equal to node's content in this case
* replace /sites/default/files/* paths with path to a new directory containing legacy files. Replacements are implemented for anchor hrefs and image srcs
* replace anchor tag with node's link to the node with the last part of node's path (to be used as slug)
* replace (optionally) lightbox links with magnific
* replace (optionally) code tags (and some other) to support Prettify script for code decoration

