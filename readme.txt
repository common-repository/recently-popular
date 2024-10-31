=== Recently Popular ===
Contributors: ebiven, caiocrcosta
Tags: recent, popular, views, hits, counter, postviews, time, span, timespan
Requires at least: 2.7.0
Tested up to: 3.2.1
Stable tag: 0.7.2

Displays the most popular posts based on history from now to X amount of time in the past.

== Description ==

= New in 0.7: =

 * Network (MU) compatibility.
 * Fixed bugs related to posts in multiple categories.

This popularity plugin differs from the ones currently in the directory because it returns the number of views from the current time back to a user-requested time and does not require a stats package. For instance they can show the most viewed posts from the past 4 days, or 7 hours, 3 months, etc. Post/page views age and can "expire" instead of being counted into perpetuity.

It includes a widget that supports a user definable number of hours or days or weeks or months as well as a limit on the number of results to show. The plugin URL shows a screenshot of the widget which makes it much clearer.

This widget allows the user full control over what each result looks like and also allows users to select posts only from specific categories.

The widget is capable of being added multiple times to the same sidebar.

== Installation ==

This plugin requires no special installation procedures.

== Frequently Asked Questions ==

= My counts all disappeared! =

If you disable the plugin for a blog it will clean up after itself, meaning it will drop the counts table. Since this plugin is used to count views for the current timeframe it makes no sense to keep counts with gaps in them since the data is no longer valid.

= Do you take feature requests? =

Sometimes.  Post them as a comment on the [Recently Popular WordPress Plugin](http://eric.biven.us/2008/12/03/recently-popular-wordpress-plugin/) page on my blog.

= Are there configuration options for the base plugin? =

Not yet.  It may eventually be built to support ignoring bot views, etc.

= I installed it but I don't see anything listed in the sidebar. =

You probably haven't had any page views that qualify according to your widget settings.

= I asked it to show X posts, but it's showing fewer.  What gives? =

The "limit" setting is just that, a limit.  If you ask it to show only 10, but there are only 5 posts with views in the timeframe you're asking for, it can only show those 5.

== Screenshots ==

1. The widget (simple view)
2. The widget (showing formatting options)
3. The widget (showing category filtering options)

== Changelog ==

= 0.1 =
* First release

= 0.2 =
* WordPress 2.7 testing
* Added support for 2.7 automatic uninstall

= 0.3 =
* Allow multiple instances of the widget

= 0.4 =
* Collect all hits and categorize them as anonymous or by a logged in user
* Add a selection to the widget to limit counting to only posts, only pages, or both
* Add a selection to the widget to limit counting to only anonymous, logged in, or both

= 0.4.5 =
* Support for template tags in widget output (post title, post url, author, hits)

= 0.4.7 =
* Support for selecting only posts from certain categories

= 0.4.8 =
* Added publish date and thumbnail path to the template tags

= 0.5 =
* Clean up bugs from 0.4.8.x branch
* Add "Relative time" option for counting views
* Add truncation for limiting title length
* Depricate get_recently_popular and replace it with get_recently_popular2 which takes a params array instead of listed parameters

= 0.6 =
* Settings page
* Support for purging records from the stats table
* Ensure that removed widgets settings are removed from the database
* Re-check indexes to ensure query is as easy as possible
* Combine both the plugin and the widget to be activated together

= 0.6.3 =
* Bugfix release

= 0.7 =
* 3.2 testing
* Network (MU) compatibility
* Rewrite all code to be fully OOP
* Fixed bugs related to posts in multiple categories.
* Other bugfixes

= 0.7.1 =
* Workaround for Centos 5 / eAccelerator installations throwing errors about class not found. http://eaccelerator.net/ticket/146

= 0.7.2 =
* Added post excerpt support and max length tag for them.

== Roadmap ==

= 0.9 =
* Feature complete, bugfixes only

= 1.0 =
* First production release

