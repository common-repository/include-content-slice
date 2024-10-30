<?php
/*
Plugin Name: Include Content Slice
Plugin URI: http://ecs.mit.edu/
Description: Use a shortcode to include content in your Wordpress site from another URL, or from a local file on your server.  This could be posts and pages from your own site, another Wordpress site, other blog sites, or any other page or file for which you have the URL.  Control what part of the source content you would like to include by wrapping it in begin and end tags, possibly containing regular expressions, or include complete files without regard to begin and end tags.
Version: 0.2
Author: Brett Mellor
Author URI: http://ecs.mit.edu/ 
*/

add_shortcode('slice', 'include_content_slice');

// this is what the plugin actually does
function include_content_slice($atts) {

	$default_atts = array ( 'src' => 'http://ecs.mit.edu/',
					'begin_tag' => slice_get_default_tag('begin'),
					'end_tag' => slice_get_default_tag('end'),
					'include_tags' => false,
					'tagless' => false,
					'local' => false,
					'debug' => false,
					'errors' => false);

	extract(shortcode_atts($default_atts, $atts));

	define('SLICE_ERROR_REPORTING',$errors);

	// Make sure PHP Client URL Library is installed on this server.
	if(!function_exists(curl_init))
		return slice_error("The Include Content Slice plugin will not work because the PHP Client URL Library is not installed on this server.  Please see <a href='http://php.net/manual/en/book.curl.php'>http://php.net/manual/en/book.curl.php</a> or contact your system administrator for assistance.");

	// are we sourcing a local file or an internet page?
	// local file
	if($local) {
		if(preg_match('/wp-config.php/',$src) || preg_match('/htpasswd/',$src) || preg_match('/htaccess/',$src))
			return slice_error("protected file, don't be creepy.");  
		if(preg_match('/http:\/\//',$src))
			return slice_error('your file location cannot contain "http://".  If you are trying to include a file from a URL, do not use the "local" shortcode option.');			
		$fh = @fopen($src, "rb");
		if(!$fh)
			return slice_error('failed to open local file');
		$source = fread($fh, filesize($src)); 
		fclose($fh); 	
		}

	// internet page
	else {
		// If the src being passed in by the shortcode is the same url as the current page, we are going to be in an endless loop.  or near endless loop.
		if( isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on" )
			$this_page_url = 'https://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		else
			$this_page_url = 'http://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		if($src == $this_page_url)
			return slice_error('the source url cannot be the same as the current page url');

		$ch = @curl_init();
		curl_setopt($ch, CURLOPT_URL, $src);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		// pass on whatever user agent has already been provided to this script by the user's browser
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		$source = curl_exec($ch);
		$curl_errno = curl_errno($ch);
		$curl_error = curl_error($ch);
		if($curl_errno > 0)
			return slice_error($curl_error);
		curl_close($ch);
		}

	// if we are slurping in a whole file without regard to begin and end tags, return the whole file
	if($tagless)
		return $source;

	$pattern = "/($begin_tag)(.*)($end_tag)/s";
	preg_match($pattern, $source, $matches);
	$content = $matches[2];

	// if the tags are actually little beginning and ending bits of the content we are trying to excerpt, include them with the output
	if($include_tags)
		$content = $matches[1] . $content . $matches[3];

	// if we are in debug mode, show the user the exact begin and end tags
	if($debug)
		$content = "<pre style='border-radius:4px; border:1px solid #E6DB55; padding:4px 8px; background:#FFFFE0; font-size:12px;'>slice debug: begin_tag='$begin_tag'    end_tag='$end_tag'</pre>" . $content;

	return $content;

} // include_content_slice


function slice_error($err_msg) {
	if(SLICE_ERROR_REPORTING) {
		$err_msg = "<div id='slice_error' style='border-radius:4px; border:1px solid #CC0000; background:#FFEBE8; padding:4px 8px; font-size:12px;'>Slice error: ".$err_msg."</div>";
		return $err_msg;
		}
	} // slice_error



add_filter('the_content', 'slice_wrap_content', 20);
function slice_wrap_content($content) {
	$saved_setting = get_option('slice-plugin-settings');
	if($saved_setting['auto_wrap']=='true')
		return $saved_setting['default_begin_tag'] . $content . $saved_setting['default_end_tag'];
	else
		return $content;
	}



/* ---------- All this down here is for setting up the Include Content Slice plugin options/settings page ---------------- */

// add a link to the Include Content Slice plugin settings page in the main menu, under "Settings" menu
add_action('admin_menu', 'slice_admin_menu_link');
function slice_admin_menu_link() {
	add_submenu_page('options-general.php', __('Include Content Slice Settings'), __('Include Content Slice'), 'manage_options', 'include-content-slice', 'slice_settings_page');
	}

// make use of the Wordpress plugin settings API to setup a plugin settings page. see http://ottopress.com/2009/wordpress-settings-api-tutorial/
add_action('admin_init','slice_admin_init');

function slice_admin_init() {
	// The section labels are not being output and the callback functions (echo_*) are not being called because we are using do_settings_fields individually instead of a single call to do_settings_sections,
	// but the labeling and functions are manually called in slice_settings_page().  The WP Plugin Settings API is pretty sweet but has near zero flexibility for display formatting, so we had to break it up
	add_settings_section('default-tags-section', 'Default begin and end tags', 'echo_default_tags', 'include-content-slice' );
	add_settings_field('default-begin-tag', 'Default begin tag:', 'echo_begin_tag_input', 'include-content-slice', 'default-tags-section');
	add_settings_field('default-end-tag', 'Default end tag:', 'echo_end_tag_input', 'include-content-slice', 'default-tags-section');

	add_settings_section('auto-wrap-section', 'Auto wrap', 'echo_auto_wrap', 'include-content-slice');
	add_settings_field('auto-wrap', 'Auto wrap:', 'echo_auto_wrap_cb', 'include-content-slice', 'auto-wrap-section');

	register_setting('include-content-slice-options', 'slice-plugin-settings');
	}

function echo_default_tags() {
	echo "If you do not set your own begin and end tags when using the slice shortcode, it will automatically<br>look for these tags in the source content and extract only the content between these tags.";
	}

function echo_begin_tag_input() {
	$saved_setting = get_option('slice-plugin-settings');
	$default_begin_tag = $saved_setting['default_begin_tag'];
	echo "<input type='text' name='slice-plugin-settings[default_begin_tag]' size='32' value='$default_begin_tag' class='slice-input'>";
	}

function echo_end_tag_input() {
	$saved_setting = get_option('slice-plugin-settings');
	$default_end_tag = $saved_setting['default_end_tag'];
	echo "<input type='text' name='slice-plugin-settings[default_end_tag]' size='32' value='$default_end_tag' class='slice-input'>";
	}

function echo_auto_wrap() {
	echo "Check this box to automatically wrap post content from this site in the default begin and end tags.";
	}

function echo_auto_wrap_cb() {
	$saved_setting = get_option('slice-plugin-settings');
	echo "<input type='checkbox' value='true' name='slice-plugin-settings[auto_wrap]'";
	checked($saved_setting['auto_wrap'], "true");
	echo ">";
	}

// displays the content of the slice settings page
function slice_settings_page() {

	echo "<style type='text/css'>.slice-input {font-family:Courier, Courier New;}</style>
		<div id='icon-options-general' class='icon32'><br></div><h2>Include Content Slice Settings</h2>
		<form action='options.php' method='post' style='margin-top:24px;'>";

	settings_fields('include-content-slice-options');

	echo "<h3>Default begin and end tags</h3>";
	echo_default_tags();
	echo "<table class='form-table'>";

	do_settings_fields('include-content-slice', 'default-tags-section');

	echo "<tr><td colspan='2'>";
	echo "<p>This behavior is overridden by setting your own tags using the begin_tag and end_tag shortcode options like so:<br>
		<pre>&#91;slice src='http://example.com/file.txt' begin_tag='&#60;!--custom begin--&#62;' end_tag='&#60;!--custom end--&#62;'&#93;</pre>
		You can also ignore tags altogether and include the entire source content:
		<pre>&#91;slice src='http://example.com/file.txt' tagless='true'&#93;</pre>
		See the <a href='" . plugins_url('readme.txt', __FILE__) . "'>readme.txt</a> file for other usage examples.
		</p></td></tr></table>";

	echo "<h3>Auto wrap</h3>";
	echo_auto_wrap();
	echo "<table class='form-table'>";
	do_settings_fields('include-content-slice', 'auto-wrap-section');
	echo "</table>";

	echo	"<p><input type='submit' name='submit' class='button-primary' value='";
	esc_attr_e('Save Changes');
	echo "'></p></form>";

	} // slice_settings_page

function slice_get_default_tag($which) {
	$saved_settings = get_option('slice-plugin-settings');
	$default_begin_tag = $saved_settings['default_begin_tag'];
	$default_end_tag = $saved_settings['default_end_tag'];
	if($which=='begin') { return $default_begin_tag; }
	if($which=='end') { return $default_end_tag; }
	}

register_activation_hook( __FILE__, slice_initialize_default_tags );

// this will run once, when the plugin is activated, to set the initial values of the default begin and end tags, cuz leaving them blank would be gauche.
function slice_initialize_default_tags() {
	// If this plugin was previously activated and in use, then it may already have user redefined default tag settings.  We don't want to lose them.
	// The use should be able to toggle activation of the plugin without losing the settings.  Because toggling plugins, for whatever reason, is, you know, not unheard of.  
	if(!get_option('slice-plugin-settings')) {
		$initial_settings['default_begin_tag'] = '<!--slice begin-->';
		$initial_settings['default_end_tag'] = '<!--slice end-->';
		update_option('slice-plugin-settings',$initial_settings);	
		}
	}

?>
