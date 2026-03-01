<?php
/*
Plugin Name:	Link Hopper
Plugin URI:		http://www.fergusweb.net/software/linkhopper/
Description:	Provides an easy interface to mask outgoing links using <code>site.com/hop/XXXXXX</code> syntax.  Configure hops via wp-admin.
Version:		1.0
Author:			Anthony Ferguson
Author URI:		http://www.fergusweb.net
*/


// delete_option('optLinkHopper');
// Load default options
$opts = array(
	'baseURL'	=> '/hop/',
	'hops'		=> array(
						'google' => 'http://www.google.com'
					)
);
add_option('optLinkHopper', $opts);


add_action('init', 'doLinkHopper');
function doLinkHopper() {
	$reqURL = $_SERVER['REQUEST_URI'];
	$fullURL = 'http://'.$_SERVER['HTTP_HOST'].$reqURL;
	$opts = get_option('optLinkHopper');
	$hopURL = $opts['baseURL'];
	if (stristr($fullURL, $hopURL) !== false) {
		$reqArr = explode('/', $reqURL);
		foreach ($reqArr as $key=>$token) {
			if ($token=='') { unset($reqArr[$key]); }
		}
		$tag = array_pop($reqArr);
		if (array_key_exists($tag, $opts['hops'])) {
			header('Location: '.$opts['hops'][$tag]);
		} else {
			header('Location: '.get_bloginfo('home'));
		}
		die;
	}
}


if (!class_exists('LinkHopper_Admin')) {
class LinkHopper_Admin {
	var $optKey = 'optLinkHopper';
	
	function add_config_page() {
		if (function_exists('add_management_page')) {
			add_management_page('LinkHopper Config', 'Link Hopper', 10, basename(__FILE__), array('LinkHopper_Admin','adminConfigPage'));
			add_filter( 'plugin_action_links', array( 'LinkHopper_Admin', 'addPluginConfigLink'), 10, 2 );
			add_action('wp_print_scripts', array('LinkHopper_Admin', 'adminLoadJS'));
		}
	}
	
	function addPluginConfigLink($links, $file) {
		static $this_plugin;
		if (!$this_plugin)
			$this_plugin = plugin_basename(__FILE__);
		if ($file == $this_plugin) {
			$settings_link = '<a href="tools.php?page=linkhopper.php">' . __('Configure') . '</a>';
			array_unshift( $links, $settings_link ); // before other links
		}
		return $links;
	}
	
	function adminLoadJS() {
		if (is_admin()) {
			$formcheckSrc = path_join(WP_PLUGIN_URL, basename( dirname( __FILE__ ) ) . '/formcheck.js' );
			$formcheckCSS = path_join(WP_PLUGIN_URL, basename( dirname( __FILE__ ) ) . '/formcheck.css' );
			wp_register_script('mootools', 'http://ajax.googleapis.com/ajax/libs/mootools/1.2.1/mootools-yui-compressed.js', array(), '1.2.1');
			wp_register_script('formcheck', $formcheckSrc, array('mootools'), '1.4.2');
			wp_enqueue_script('mootools');
			wp_enqueue_script('formcheck');
			echo '<link rel="stylesheet" href="'.$formcheckCSS.'" />'."\n";
		}
	}
	
	function adminConfigPage() {
		// Load Current Options
		$opts = get_option('optLinkHopper');
		// Save Updated Options
		if (isset($_POST['SaveLinkhopperOptions'])) {
			if (!wp_verify_nonce($_POST['_wpnonce'], 'doLinkHopper')) {
				echo '<p class="alert">Invalid Security</p>'."\n";
				return;
			}
			$hops = array();
			foreach ($_POST['hopName'] as $key=>$hopName) {
				$hopName = trim(stripslashes($hopName));
				$hopURL = trim(stripslashes($_POST['hopURL'][$key]));
				if ($hopName != '' && $hopURL != '') {
					$hops[$hopName] = $hopURL;
				}
			}
			if (is_array($_POST['hopDel']))
			foreach ($_POST['hopDel'] as $key=>$hopName) {
				unset($hops[$hopName]);
			}
			$opts['hops'] = $hops;
			update_option('optLinkHopper', $opts);
			echo '<div id="message" class="updated fade"><p><strong>'.__('Options saved.').'</strong></p></div>'."\n";
		}
		// Display Option Form
		?>
<div class="wrap">
<div class="inner">
  <h2>LinkHopper Configuration</h2>
<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" id="linkhopCFG">
<?php wp_nonce_field('doLinkHopper') ?>
<table id="baseHopCfg" class="widefat">
  <tr>
	<th><label for="hopBaseURL"><?php _e('Base URL'); ?></label></th>
  </tr>
  <tr>
	<td><input class="widefat validate['required']" name="baseURL" id="hopBaseURL" value="<?php echo $opts['baseURL']; ?>" /></td>
  </tr>
</table>

<table id="linkHopCfg" class="widefat">
  <tr>
	<th><?php _e('Hop Name'); ?></th>
    <th><?php _e('Destination URL'); ?></th>
    <th><?php _e('Delete?'); ?></th>
  </tr>
<?php
foreach ($opts['hops'] as $hopName=>$hopURL) {
?>
  <tr>
	<td class="name"><input class="name widefat validate['required','alphanum']" type="text" name="hopName[]" value="<?php echo $hopName; ?>" /></td>
    <td class="url"><input class="url widefat validate['required','url']" type="text" name="hopURL[]" value="<?php echo $hopURL; ?>" /></td>
    <td class="delete"><label><input class="delete" type="checkbox" name="hopDel[]" value="<?php echo $hopName; ?>" /></label></td>
  </tr>
<?php
} // foreach
for ($i=0; $i<2; $i++) {
?>
  <tr>
	<td class="name"><input class="name widefat validate['alphanum']" type="text" name="hopName[]" value="" /></td>
    <td class="url"><input class="url widefat validate['url']" type="text" name="hopURL[]" value="" /></td>
    <td class="delete">&nbsp;</td>
  </tr>
<?php
} // for
?>
  
  <tr><td colspan="3" class="bttn">
  <input type="submit" name="SaveLinkhopperOptions" value="<?php _e('Save Changes'); ?>" id="Save" class="button-primary" />
  </td></tr>
</table>
</form>
</div><!-- inner -->
<div class="donate">
<table class="widefat">
<tr><th>Buy me a beer</th></tr>
<tr><td>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="4295059">
<input type="image" src="https://www.paypal.com/en_AU/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online.">
<img alt="" border="0" src="https://www.paypal.com/en_AU/i/scr/pixel.gif" width="1" height="1">
</form>
<p>If you find the LinkHopper useful, and you're feeling generous, buy the poor author a beer.</p>
<p>He's thirsty!<br />(This is not required)</p>
</td></tr></table>
</div>
</div><!-- wrap -->
<style><!--
.wrap .inner { float:left; }
#linkhopCFG table.widefat { width:63em; margin:0.6em 0; }
table.widefat th { background:#DDD; }
#linkhopCFG input.name	{ width:15em; }
#linkhopCFG input.url	{ width:40em; }
#linkhopCFG td.bttn		{ text-align:right; padding-right:3em; }
#linkhopCFG input.widefat{border-color:#21759B; }
#linkhopCFG td.delete label {	display:block; text-align:center; }
#baseHopCfg input.widefat { width:40em;  }

div.donate { width:17em; float:left; margin:4em 0 0 3em; }
.donate table th, .donate td { text-align:center; }
--></style>
<script><!--
new FormCheck('linkhopCFG');
--></script>
		<?php
    }
}
}

add_action('admin_menu', array('LinkHopper_Admin','add_config_page'));

?>