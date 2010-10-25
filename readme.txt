=== A9 SiteInfo Generator ===
Contributors: arnee
Donate link: http://www.arnebrachhold.de/redir/paypal
Tags: a9, siteinfo, xml
Requires at least: 2.0.3
Stable tag: 1.2

This plugin will create a SiteInfo menu for your blog.

== Description ==

SiteInfo is an open standard which allows you to specify information that will be displayed in a toolbar menu. This allows the A9 toolbar (or other toolbars that want to use SiteInfo) to display a menu about your site, when a user is on your site.

**THE SITEINFO FORMAT HAS BEEN CANCELED BY A9. THIS PLUGIN IS NOT SUPPORTED ANYMORE!**

== Installation ==

Installation is easy if you follow this simple steps:

If your Blog is in the root directory of your domain (like www.myblog.com/) and your server supports mod_rewrite:

1. Upload the plugin into your wp-content/plugins directory
2. Activate the plugin
3. Rebuild your Permalink structure in "Options" -> "Permalinks"
4. Customize the settings of this plugin in "Options" -> "SiteInfo"

If your Blog is NOT located in the root directory (like www.mydomain.com/blog/) or your server doesn't support mod_rewrite:

1. Upload the plugin into your wp-content/plugins directory
2. Create a new file named "siteinfo.xml" in your domain root directory (like www.mydomain.com/siteinfo.xml).
3. Make it writable using FTP or SSH and the CHMOD 777 command. The WordPress Codex has additional information about that.
4. Activate the plugin
5. Go into "Options" -> "SiteInfo" and verify if the file system path to the siteinfo.xml is correct.

== Frequently Asked Questions == 

= I have to access to my domain root, can I use SiteInfo? =
Since there is only one SiteInfo per domain, you can't use SiteInfo without access to your domain root.

= I get the error that my siteinfo.xml is not writable. =
Please make it writable using FTP or SSH and the CHMOD 777 command. The WordPress Codex has additional information about that.

= When i click on "Verify Path" or "Autodetect Path" I get the error "This feature is not available for this WordPress version". =
You are using an older WordPress version which doesn't support all AJAX security features. Please upgrade to 2.0.3

= What is mod_rewrite? =
Mod_rewrite is a technology which allows you to map a nice looking path (like yourblog.com/2005/05/01/your-post) to yourblog.com/index.php?id=40. =

= How does this plugin uses mod_rewrite? =
This plugin uses mod_rewrite to map yourdomain.com/siteinfo.xml to yourdomain.com/index.php?request_siteinfo=true. This allows you to generate the SiteInfo on the fly without having to create a static file.

= My server doesn't support mod_rewrite or my blog is not located at the domain root, what should I do? =
This plugin can also generate a static siteinfo.xml. Please verify your path to the domain root at the administration page.

= How often is the static file rebuilt? =
The static file is rebuild of you publish a post.

= I enabled mod_rewrite, but I alway get an old SiteInfo =
Please make sure that there is no siteinfo.xml in your domain root.

= I changed the content of my SiteInfo, but I can't see it in my browser! =
The SiteInfo is retrieved only once per session. Please close your browser and retry.