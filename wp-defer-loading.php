<?php
/*
Plugin Name: WP Defer Loading
Plugin URI: https://github.com/bassjobsen/wp-defer-loading
Description: Defer loading javascript for WordPress. Without any additional library. Just the way Google ask you to do it.
Version: 1.0.2
Author: Bass Jobsen
Author URI: http://bassjobsen.weblogs.fm/
License: GPLv2
*/

/*  Copyright 2013 Bass Jobsen (email : bass@w3masters.nl)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class WP_Scripts2 extends WP_Scripts
{
	
		function do_item( $handle, $group = false ) {
		if ( !WP_Dependencies::do_item($handle) )
			return false;
   
   
      
		/*if ( 0 === $group && $this->groups[$handle] > 0 ) {
			$this->in_footer[] = $handle;
			return false;
		}*/

		if ( false === $group && in_array($handle, $this->in_footer, true) )
			$this->in_footer = array_diff( $this->in_footer, (array) $handle );

		if ( null === $this->registered[$handle]->ver )
			$ver = '';
		else
			$ver = $this->registered[$handle]->ver ? $this->registered[$handle]->ver : $this->default_version;

		if ( isset($this->args[$handle]) )
			$ver = $ver ? $ver . '&amp;' . $this->args[$handle] : $this->args[$handle];



		$src = $this->registered[$handle]->src;

		if ( $this->do_concat ) {
			$srce = apply_filters( 'script_loader_src', $src, $handle );
			if ( $this->in_default_dir($srce) ) {
				$this->print_code .= $this->print_extra_script( $handle, false );
				$this->concat .= "$handle,";
				$this->concat_version .= "$handle$ver";
				return true;
			} else {
				$this->ext_handles .= "$handle,";
				$this->ext_version .= "$handle$ver";
			}
		}

		$this->print_extra_script( $handle );
		if ( !preg_match('|^(https?:)?//|', $src) && ! ( $this->content_url && 0 === strpos($src, $this->content_url) ) ) {
			$src = $this->base_url . $src;
		}

		if ( !empty($ver) )
			$src = add_query_arg('ver', $ver, $src);

		$src = esc_url( apply_filters( 'script_loader_src', $src, $handle ) );
		global $thescripts, $thescriptsnd;
		
		if ( $this->do_concat )
			$this->print_html .= "<script type='text/javascript' src='$src'></script>\n";
		else
		{
						
			if($handle=="jquery-migrate") $this->registered[$handle]->deps[] = "jquery";
			if($handle=="bp-legacy-js") $this->registered[$handle]->src = plugins_url( 'buddypress/buddypress.js' , __FILE__ );
			
			if(count($this->registered[$handle]->deps)===0)$thescriptsnd[($handle=="jquery-core")?'jquery':str_replace('-','_',$handle)]=$src;
			else $thescripts[$this->registered[$handle]->deps[count($this->registered[$handle]->deps)-1]][str_replace('-','_',$handle)]=$src;
		
        } 
		return true;
	}
	
	function print_extra_script( $handle, $echo = true ) {
		if ( !$output = $this->get_data( $handle, 'data' ) )
			return;

		if ( !$echo )
			return $output;


		echo "var element = document.createElement(\"script\");\n";
        echo "element.appendChild( document.createTextNode( \"". addslashes($output) ."\" ) );";
		echo "document.body.appendChild(element);\n";

		return true;
	}
	
}	

$page = !is_admin() && !in_array($GLOBALS['pagenow'], array('wp-login.php'));
if($page)
{

global $wp_scripts;
$wp_scripts = new WP_Scripts2();

$thescripts = array();
$thescriptsnd = array();


if(!class_exists('WP_Defer_Loading')) 
{ 
	
	class WP_Defer_Loading 
	{ 
	/*
	* Construct the plugin object 
	*/ 
	public function __construct() 
	{ 
		add_filter( 'init', array( $this, 'init' ) );
	} 
	// END public 

	/** 
	 * Activate the plugin 
	**/ 
	public static function activate() 
	{ 
		// Do nothing 
	} 
	// END public static function activate 

	/** 
	 * Deactivate the plugin 
	 * 
	**/ 
	public static function deactivate() 

	{ // Do nothing 
	} 
	// END public static function deactivate 

	/** 
	 * hook into WP's admin_init action hook 
	 * */ 
	 

	function init()
	{

		remove_action( 'wp_head','wp_print_head_scripts',9);
		remove_action( 'wp_footer','wp_print_footer_scripts',20);
		add_action('wp_head', array( $this, 'defer_loading_code'),99);
		add_action( 'defer_loading_scripts','wp_print_head_scripts',1);

		if ( in_array( 'buddypress/bp-loader.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) 
		{
			remove_action( 'wp_head',    'bp_core_confirmation_js', 100 );
		}
	}
		
		function adddom ($handle,$src)
		{
			global $thescripts;
			
			echo "var element".$handle." = document.createElement(\"script\");\n";
			
			if(!empty($thescripts[$handle]))
			{
				echo "\n\nfunction helper".$handle."(){\n";
				
				foreach($thescripts[$handle] as $handle2=>$src2)
				{
				$this->adddom($handle2,$src2);
				}
				echo "}\n";
				
				echo "element".$handle.".onreadystatechange = function () {\n";
				echo "if (this.readyState == 'complete') helper".$handle."();\n";
				echo "}\n";
				echo "element".$handle.".onload = helper".$handle.";\n\n\n";
				
			}	

			echo "element".$handle.".src = \"".$src."\";\n";
			echo "document.body.appendChild(element".$handle.");\n";
		
		}	
		
		function defer_loading_code()
		{
			//
			//https://github.com/requirejs/example-jquery-cdn
			?>
				 <script type="text/javascript">

				 // Add a script element as a child of the body
				 function downloadJSAtOnload() {
				 <?php 
				 do_action('defer_loading_scripts'); 

				 global $thescriptsnd,$thescripts;
				 
				 foreach ($thescriptsnd as $handle=>$src)
				 { 
					  $this->adddom($handle,$src);
				 }	 
				
				 
				 ?>
				 }

				 // Check for browser support of event handling capability
				 if (window.addEventListener)
				 window.addEventListener("load", downloadJSAtOnload, false);
				 else if (window.attachEvent)
				 window.attachEvent("onload", downloadJSAtOnload);
				 else window.onload = downloadJSAtOnload;

				</script>	
				<?php
		}	
		
	}
	}	
}

if(class_exists('WP_Defer_Loading')) 
{ // Installation and uninstallation hooks 
	register_activation_hook(__FILE__, array('WP_Defer_Loading', 'activate')); 
	register_deactivation_hook(__FILE__, array('WP_Defer_Loading', 'deactivate')); 
	
	$wpdeferloading = new WP_Defer_Loading();
}
