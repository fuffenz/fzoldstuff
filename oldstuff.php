<?php
/*
Plugin Name: OldStuff
Description: Display a warning when a post is older than X days
Version: 0.04
Author: Ulf Hedlund
Author URI: http://ulfhedlund.se/
Text Domain: fz_oldstuff

*/

// activate plugin, create default settings
function fz_oldstuff_activate() {
	delete_option('fz_oldstuff');	// brute force
	$triggers = array(  
		array( "days" => "1800", "htmltext" => "<div class='fzoldstuff fzred'>" . __('This is pre-historic stuff', 'fz_oldstuff') . "</div>"),
		array( "days" => "1095", "htmltext" => "<div class='fzoldstuff fzyellow'>" . __('This is very old stuff', 'fz_oldstuff') . "</div>"),
		array( "days" => "365", "htmltext" => "<div class='fzoldstuff'>" . __('This is old stuff', 'fz_oldstuff') . "</div>")
	);
	$options = array( 'triggers' => $triggers, 'autoinsert' => 1);
	update_option('fz_oldstuff', $options);
}

// uninstall plugin, clean up settings
function fz_oldstuff_uninstall() {
	delete_option('fz_oldstuff');
}

// queue CSS
function fz_oldstuff_css() {
	wp_register_style( 'fz_oldstuff-style', plugins_url('style.css', __FILE__) );
	wp_enqueue_style( 'fz_oldstuff-style' );
}

// init settings api
function fz_oldstuff_init() {
	register_setting( 'fz_oldstuff_options', 'fz_oldstuff', 'fz_oldstuff_validate' );
}

// settings menu setup
function fz_oldstuff_menu() {
	add_options_page('Settings', __('OldStuff settings','fz_oldstuff'), 'manage_options', __FILE__, 'fz_oldstuff_settings');
}

function fz_oldstuff_validate( $str ) {
	// ok, this isn't validation, but sort the triggers in descending order
	$triggers = $str['triggers'];
	arsort($triggers);	
	$newtriggers = array();
	foreach ( $triggers as $key => $val ) {
		$newtriggers[] = $val;
	}
	$str['triggers'] = $newtriggers;

	return $str;	
}

// settings page
function fz_oldstuff_settings() {
?><div class='wrap'><h2><?php echo __('OldStuff settings','fz_oldstuff') ?></h2>

<form method='post' action='options.php'>
<?php 
settings_fields('fz_oldstuff_options'); 
$options = get_option('fz_oldstuff'); 
?>
	<table class="form-table">
<?php for ( $i = 0; $i < 3; $i++ ) { ?>
	<tr>
		<th scope="row"><?php echo __('Minimum # of days', 'fz_oldstuff') ?></th>
		<td>
		<input type="text" size="5" name="fz_oldstuff[triggers][<?php echo $i ?>][days]" value="<?php echo $options['triggers'][$i]['days']; ?>" />
		</td>
	</tr>
	<tr>	
		<th scope="row"><?php echo __('HTML to display', 'fz_oldstuff') ?></th>
		<td>
		<textarea name="fz_oldstuff[triggers][<?php echo $i ?>][htmltext]" rows="5" cols="80" type='textarea'><?php echo $options['triggers'][$i]['htmltext']; ?></textarea>
		</td>
	</tr>
<?php } ?>

	<tr valign="top">
	<th scope="row"><?php __('Auto-insert in posts', 'fz_oldstuff') ?></th>
	<td>
		<label><input name="fz_oldstuff[autoinsert]" type="checkbox" value="1" <?php if (isset($options['autoinsert'])) { checked('1', $options['autoinsert']); } ?> /> <br />
		<?php echo __('If unchecked, you have to add <strong>&lt;?php oldstuff_warning(); ?&gt;</strong> to your theme manually', 'fz_oldstuff') ?></label><br />
	</td>
	</tr>
	</table>
<input type="submit" value="<?php echo __('Save Changes', 'fz_oldstuff') ?>" />
</form>
</div>
<?php
}

// auto-insert at top of post
function fz_oldstuff_filter( $content ) {
	$options = get_option('fz_oldstuff');
	// if no options or autoinsert is false, do nothing
	if( empty($options) || empty($options['autoinsert']) ) {
		return $content;
	}

	$days = floor((time() - strtotime(get_the_date('Y-m-d'))) / 86400);
	// get the triggers 
	$triggers = $options['triggers'];
	foreach ( $triggers as $key => $val ) {
		if( $days > $val['days'] && 0 < $val['days'] ) {
			return $val['htmltext'] . $content;
		}
	}
	// no match, just return 
	return $content;
}

// this function can be used in the theme 
function oldstuff_warning() {
	$options = get_option('fz_oldstuff');
	if( empty($options) ) {
		return;
	}

	$days = floor((time() - strtotime(get_the_date('Y-m-d'))) / 86400);
	// get the triggers 
	$triggers = $options['triggers'];
	foreach ( $triggers as $key => $val) {
		if( $days > $val['days'] && 0 < $val['days'] ) {
			echo $val['htmltext'];
			return;
		}
	}
	// no match, just return 
}

// let WP know that we are here
register_activation_hook(__FILE__, 'fz_oldstuff_activate');
register_uninstall_hook(__FILE__, 'fz_oldstuff_uninstall');
add_action( 'wp_enqueue_scripts', 'fz_oldstuff_css' );
add_action('admin_init', 'fz_oldstuff_init');
add_action('admin_menu', 'fz_oldstuff_menu');
add_filter('the_content', 'fz_oldstuff_filter');
load_plugin_textdomain('fz_oldstuff', false, basename( dirname( __FILE__ ) ) . '/languages'); 
?>
