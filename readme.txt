=== Include Content Slice ===
Contributors: bmellor
Author: Brett Mellor
Author URI: http://ecs.mit.edu/
Tags: slice, URL, href, include, cross-post, mirror, excerpt, duplicate, reuse, repeat, synchronize, embed, import, share, content, html, regular expression, regex
Requires at least: 3.3.1
Tested up to: 3.3.1
Stable tag: 0.2

Use a shortcode to include a file (or just a part of a file by using text delimiters) into the current post from any URL, or from the local server.

== Description ==

The Include Content Slice plugin provides a shortcode you can use to include content in your Wordpress site from another URL, or from a local file on your server.  This could be posts and pages from your own site, another Wordpress site, other blog sites, or any other page or file for which you have the URL.  You can control what part (a "slice") of the source content you would like to include by wrapping it in begin and end tags, possibly containing regular expressions, or you can just include complete files.

Begin and end tags are used in the source content to define what portion of the content you would like to include.  (In this context, the word 'tag' refers to something like an html tag or comment, or some other reasonably unique delimiter that marks the beginning and ending of some portion of text.  It does not mean tag in the sense of post taxonomy.)

Typically, the source page is some HTML page and the delimiting string or tag will be in the form of an HTML comment.  The plugin will look for and use the default delimiter tags &lt;!--slice begin--&gt; and &lt;!--slice end--&gt; in the source content.  You can also specify your own begin and end tags as shortcode options.  The begin and end tags do not need to be in the form of an HTML comment.  They can be almost any text string found in the source content.  The tags are case sensitive.  Your tags will be interpreted as regular expressions, therefore you should not use characters like ^ $ . * + ? | () [] {} unless you are intending to use regular expressions.  See "Other Notes". 

The most common application for this plugin is for reposting all or part of a post from one Wordpress site to another Wordpress site.  The plugin was originally developed so that content could be edited in a single location and then included on other sites, in whole or in part, without having to be retyped.  In this way, any changes made to the source page automatically appear on the other sites on which the source content is included.  The source page does not need to be a Wordpress post.  It can be any web page or text file, from a remote server or from a local disk.

Please see "Other Notes" tab for additional usage instructions and examples.  

The Include Content by URL plugin makes use of the PHP Client URL Library (CURL).  CURL is built-in to PHP since PHP version 4.0.2 which has been around for over a decade so you are unlikely to run into problems.  This plugin does NOT make use of the file_get_contents() function. 

== Installation ==

1. Upload the 'include-content-by-url' folder to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. If desired, adjust the default begin and end tags on the plugin settings page

== Frequently Asked Questions ==

== Changelog ==

= 0.1 =

* Initial public release.

== Usage ==

Example #1: Include content from a URL using the default begin and end tags.

The most basic shortcode looks like this:

`[slice src='http://example.com/wordpress/source-page']`

The plugin will fetch the source page and it will look for the default begin and end tags in that page.  The default begin and end tags are &lt;!--slice begin--&gt; and &lt;!--slice end--&gt;.  You can set them differently on the plugin settings page.  The plugin will return all of the content between those two tags, but not the tags themselves.  

Also on the plugin settings page, you can set the "auto wrap" feature to always wrap the post content on your own Wordpress site in the default begin and end tags, making the content easier to extract.  You could also add the tags to your theme templates, or just add them to the beginning and end of each post when you create the post.    

Example #2: Include content from another URL using custom begin and end tags

`[slice src='http://example.com/wordpress/source-page' begin_tag='<!--begin excerpt1-->' end_tag='<!--end excerpt1-->']`

Same as Example #1, but you get to define your own tags.  Of course, these tags need to exist in the source page, or you need to be able to edit the source page in order to insert them.  

Example #3: Include content from another URL, and also include the tags used to delimit the content.

`[slice src='http://example.com/star-spangled-banner.html' begin_tag='Oh say can you see' end_tag='home of the brave.' include_tags='true']`

This would return the complete first stanza of the Star Spangled Banner including the beginning and ending bits that you used to find it.  The option include_tags='true' will retain the begin and end tags as part of the included content, instead of returning only what is between the begin and end tags. 

This is the way to go if you don't have the ability to edit the source page in order to insert your own begin and end tags.  You can just use portions of the text that already appear in the source page.  

Example #4: Include an entire file from another URL.

`[slice src='http://example.com/file.txt' tagless='true']`

This will include an entire source file without attempting to search for any begin or end tags.  

You should not try to include another complete stand alone web page, which would presumably have its own doctype, html, head and body tags.  These tags should not be repeated within the page you are including content into.  

Example #5: Include a local file

`[slice src='/home/sda1/user/johndoe/file.txt' local='true']`

Use the complete file path starting from your server root directory, not your web root directory.  

Example #6: Error reporting

`[slice src='http://example.com/file.html' errors='true']`

If you are not getting the included content you expect, setting errors='true' may reveal the problem.  See also option debug='true'.

Example #6: Using regular expressions

The Include Content Slice plugin uses the preg_match() function to locate your begin and end tags in the source content.  Theoretically, there is no reason you can't use regular expressions as begin and end tags.  You just have to get them to survive the Wordpress shortcode option parser intact.

As a simple example, the plugin will correctly interpret these tags as the beginning and ending of the source content file (note: the source content file is slurped in as one continuous string):

`[slice src='http://example.com/file.txt' begin_tag='^' end_tag='$']`

I cannot promise you that the Wordpress shortcode option parser will not mangle your regular expression.  In fact, it most definitely will in some cases.  The slice shortcode debug option may help you resolve these problems.  If you want to see the difference between what you set your tags to be in the shortcode options, and what actually survived the Wordpress shortcode option parser, then set debug='true'.  Example:

`[slice src='http://example.com/file.txt begin_tag='\x66\x6f\x6f' end_tag='\x62\x61\x72' debug='true']`

Setting debug='true' will display your tags at the top of the post that you're using the shortcode in, as the tags were actually seen and used by the preg_match() function, so you can see what actually made it through the shortcode parser.  If your tags are in the form of HTML comments, you obviously will not be able to see them on the page.  You will have to view the source.  

One hint: when in doubt, add extra backslashes.  For instance if you want to match a digit character using \d, then you would use \\d in your tag and one of the backslashes will survive the shortcode parser.  Brackets are guaranteed to cause trouble.  You just have to play around with it to see what you can get past the shortcode option parser.  Parentheses may or may not cause problems, depending upon whether you are using them as groupings or as literal characters.  You definitely cannot use groupings in your begin tag at this time.  You might be able to use them in your end tag. 

The following article may be useful in deciphering the inner workings of the Wordpress shortcode option parser: [http://stackoverflow.com/questions/2564177/wordpress-problem-with-the-shortcode-regex](http://stackoverflow.com/questions/2564177/wordpress-problem-with-the-shortcode-regex)

