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
