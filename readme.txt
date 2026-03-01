=== Link Hopper ===
Contributors:		Anthony Ferguson
Tags:				links, redirect, affiliate, masking
Tested up to:		6.9
Requires at least:	6.0
Stable tag:			3.0
Requires PHP:       8.3
License:            GPLv3 or later
License URI:        https://www.gnu.org/licenses/gpl-3.0.html

Link Hopper lets you set up tidy link redirection to other websites.

== Description ==

Easily set up links like /hop/google/ to redirect users to www.google.com.  This can be useful in masking your affiliate links.

If your affiliate link needs to change, you can just change the HOP values in a single place, without having to search your site for outdated links.

== Installation ==

1. Upload plugin to your wordpress installation plugin directory
1. Activate plugin through the `Plugins` menu in Wordpress
1. Look at the configuration screen (found under `Tools` in the Wordpress menu)
1. Supply a Name and URL for each redirect you want to use.
1. You're done!

== Frequently Asked Questions ==

= What's the Base URL? =
If you use `hop`, then your redirection links will look like this:
http://www.site.com/hop/name

= What's a Hop Name? =
The Hop Name appears after the Base URL in the redirection link.  This tells the Link Hopper which destination URL to redirect to.

= Does this include statistics and click-tracking? =
No, not at this time. It's a simple link-redirect script.


== Changelog ==

= 3.0 = 
* Rewrite the plugin code for modern best practices
* Better dev environment
* Github to SVN deployment

= 1.4 =
* Completely re-written code base
* Better compatibility with current versions of WordPress
* Still requires a permalink setting - anything but default, really.

= 1.3 =
* Older release.
