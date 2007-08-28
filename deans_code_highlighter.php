<?php
/*
Plugin Name: Dean's Code Highlighter
Plugin URI: http://www.deanlee.cn/wordpress/code_highlighter_plugin_for_wordpress/
Description: this plugin using <a href="http://qbnz.com/highlighter/">Geshi</a> to highlight source code in your posts. .
Author: Dean Lee
Version: 1.2
Author URI: http://www.deanlee.cn
*/

/*  Copyright 2006  Dean Lee (email : deanlee2@hotmail.com)

You are free:

    * to copy, distribute, display, and perform the work
    * to make derivative works
    * to make commercial use of the work

Under the following conditions:
By Attribution: 

You must attribute the work by providing a link to 
http://www.deanlee.cn/projects from every domain and subdomain 
where this plugin will be used.  This link can be in the form of a simple link, 
or you can write a short post about your use of the plugin on your site.
Share Alike: 
If you alter, transform, or build upon this work, you may distribute the 
resulting work only under a licence identical to this one.

    * For any reuse or distribution, you must make clear to others the licence 
        terms of this work.
    * Any of these conditions can be waived if you get permission from the 
        copyright holder.
For more details please see: http://www.deanlee.cn/projects

*/
require_once("geshi.php");

$ch_options=array();
$ch_options['ch_b_linenumber']=true;				
$ch_options['ch_b_wrap_text']=true;				
$ch_options['ch_in_tab_width']=8;					
$ch_options['ch_b_strict_mode']=false;

//First init default values, then overwrite it with stored values so we can add default
//values with an update which get stored by the next edit.
$dl_storedoptions=get_option("dean_ch_options");
if($dl_storedoptions) {
	foreach($dl_storedoptions AS $k=>$v) {
		$ch_options[$k]=$v;	
	}
} 
else update_option("dean_ch_options",$ch_options);

function ch_go($key) {
	global $ch_options;
	return $ch_options[$key];	
}
class ch_highlight {
	var $ch_is_excerpt = false;
	function __construct()
	{
		$this->ch_is_excerpt= false;
		add_action('wp_head', array(&$this, 'ch_gencss'));
		add_filter('the_content',array(&$this, 'ch_the_content_filter'),1);
	}
	function ch_gencss()
	{
		$cssurl = trailingslashit(get_option('siteurl')) .'wp-content/plugins/' . basename(dirname(__FILE__)) .'/geshi.css';
		echo '<link rel="stylesheet" href="' . $cssurl .'"  type="text/css" />' ;
	}
	// PHP 4 Constructor
	function ch_highlight ()
	{
		$this->__construct() ;
	}
	
	function entodec($text){
		 $html_entities_match = array( "|\<br \/\>|", "#<#", "#>#", "|&#39;|", '#&quot;#', '#&nbsp;#' );
		$html_entities_replace = array( "\n", '&lt;', '&gt;', "'", '"', ' ' );

		$text = preg_replace( $html_entities_match, $html_entities_replace, $text );

		$text = str_replace('&lt;', '<', $text);
		$text = str_replace('&gt;', '>', $text);

		return $text;
	}

	function ch_highlight_code($matches){
		global $ch_options;
		// undo nl and p formatting
		$plancode = $matches[2];
		$plancode = $this->entodec($plancode);

		$geshi = new GeSHi($plancode, strtolower($matches[1]));
		$geshi->set_encoding('utf-8');
		$geshi->set_header_type(GESHI_HEADER_DIV);
		$geshi->enable_classes(true);
		if (ch_go('ch_b_linenumber'))
		{
			$geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS); 
		}
		$geshi->enable_strict_mode(ch_go('ch_b_strict_mode'));
		$geshi->set_tab_width(ch_go('ch_in_tab_width'));
		$geshi->set_overall_class('dean_ch');
		$overall_style='';
		if (!ch_go("ch_b_wrap_text"))
		{
			$overall_style.='white-space: nowrap;';
		}
		else
		{
			$overall_style.='white-space: wrap;';
		}
		
		if ($overall_style != '')
		{
			$geshi->set_overall_style($overall_style, false);
		}

		return $geshi->parse_code();
	}
	
	function ch_the_content_filter($content) {
		if ($this->ch_is_excerpt)
		{
			$this->ch_is_excerpt = false;
			return $content;
		}
		else 
			return preg_replace_callback("/<pre\s+.*lang\s*=\"(.*)\">(.*)<\/pre>/siU",
									 array(&$this, "ch_highlight_code"), 
									 $content);
	}
}

function dl_reg_admin() {
	if (function_exists('add_options_page')) {
		add_options_page('Code Highlighter', 'Code Highlighter', 8, basename(__FILE__), 'dean_ch_options_page');	
	}
}

function dean_ch_options_page()
{
	global $ch_options;
		//All output should go in this var which get printed at the end
	$message="";
	if (!empty($_POST['info_update'])) 
	{
		foreach($ch_options as $k=>$v) {
			if(!isset($_POST[$k])) $_POST[$k]=""; 
			if(substr($k,0,5)=="ch_b_") {					
				$ch_options[$k]=(bool) $_POST[$k];	
			} else if(substr($k,0,6)=="ch_in_") {
				$ch_options[$k]=(int)$_POST[$k];		
			} else if(substr($k,0,7)=="ch_str_") {
				$ch_options[$k]=(string) $_POST[$k];		
			} 
		}
		
		if(update_option("dean_ch_options",$ch_options)) $message.=__('Configuration updated', 'code_highlighter');
		else $message.=__('Error', 'code_highlighter');

		//Print out the message to the user, if any
		if($message!="") {
			?>
			<div class="updated"><strong><p><?php
			echo $message;
			?></p></strong></div><?php
		}
	}
	?>
		<div class=wrap>
			<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
				<h2><?php _e('Dean\'s Code Highlighter', 'code_highlighter') ?> 1.0</h2>
				<fieldset name="sm_basic_options"  class="options">
					<legend><?php _e('Basic Options', 'code_highlighter') ?></legend>
					<ul>
						<li>
							<label for="ch_b_linenumber">
								<input type="checkbox" id="ch_b_linenumber" name="ch_b_linenumber" <?php echo (ch_go("ch_b_linenumber")==true?"checked=\"checked\"":"") ?> />
								<?php _e('Show line numbers beside your code', 'code_highlighter') ?>
							</label>
						</li>
						<li>
							<label for="ch_b_wrap_text">
								<input type="checkbox" id="ch_b_wrap_text" name="ch_b_wrap_text" <?php echo (ch_go("ch_b_wrap_text")==true?"checked=\"checked\"":"") ?> />
								<?php _e('Wrap overflowing text(If you turn this option on, lines will be wrapped when they reach the width of the screen.)', 'code_highlighter') ?>
							</label>
						</li>
						<li>
							<label for="ch_b_strict_mode">
								<input type="checkbox" id="ch_b_strict_mode" name="ch_b_strict_mode" <?php echo (ch_go("ch_b_strict_mode")==true?"checked=\"checked\"":"") ?> />
								<?php _e('Strict Mode means that if your language is a scripting language (such as PHP), then highlighting will only be done on the code between appropriate code delimiters (eg &lt;?php, ?&gt;).)', 'code_highlighter') ?>
							</label>
						</li>
						<li><strong>Tab width (in spaces):</strong><input type="text" name="ch_in_tab_width" value="<?php echo ch_go('ch_in_tab_width');?>"/>Tabs in your source code will be turned into this many spaces (maximum of 20) </li>
											
						</ul>
					</fieldset>
					<div class="submit"><input type="submit" name="info_update" value="<?php _e('Update options', 'code_highlighter') ?>" /></div>
					<fieldset class="options">
					<legend><?php _e('Informations and support', 'code_highlighter') ?></legend>
					<p><?php echo str_replace("%s","<a href=\"http://www.deanlee.cn/wordpress/code_highlighter_plugin_for_wordpress/\">http://www.deanlee.cn/wordpress/code_highlighter_plugin_for_wordpress/</a>",__("Check %s for updates and comment there if you have any problems / questions / suggestions.",'code_highlighter')); ?></p>
				</fieldset>
				</form></div>
				<?php

}
//Register to wordpress...
add_action('admin_menu', 'dl_reg_admin');

if (!function_exists('ch_highlight'))
	$ch_highlight = new ch_highlight();

?>