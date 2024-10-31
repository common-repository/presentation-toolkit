<?php
/*
Plugin Name: Presentation Toolkit
Plugin URI: http://windyroad.org/software/wordpress/presentation-toolkit-plugin
Description: Helps theme and skin authors set up an admin menu, which theme and skin users can then use to customise the theme or skin.
Version: 0.0.9
Author: Windy Road
Author URI: http://windyroad.org

Copyright (C)2007 Windy Road
This work is based on the  Wordpress Theme Toolkit by Ozh (http://planetozh.com)

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.This work is licensed under a Creative Commons Attribution 2.5 Australia License http://creativecommons.org/licenses/by/2.5/au/

*/ 


$_BENICE[]='presentationtoolkit;6770968883708243;0901874953';

function presentationtoolkit($array='',$file='') {
    if ( $array == '' or $file == '') {
        die ('No theme options or file defined in Presentaton Toolkit');
    }
    $kit = new PresentationToolkit($array,$file);
	$theme = $kit->infos[ 'theme_name' ];
	if( $kit->infos[ 'skin' ] ){
		$theme = get_stylesheet() . '-' . $kit->infos[ 'theme_shortname' ];
	}
	global $ptk;
	$ptk[ $theme ] = $kit;
}

class PresentationToolkit{

    var $option, $infos, $msg;

    function PresentationToolkit($array,$file){
        global $wp_version;
		
		$theme_root = str_replace( '\\', '/', get_theme_root() );
		$file = str_replace( '\\', '/', $file );
		$this->file = str_replace($theme_root . '/', '', $file);
		$this->infos['path'] = dirname( $this->file );
        /* Create some vars needed if an admin menu is to be printed */
        if ($array['debug']) {
            if ($this->file == $_GET['page']) $this->infos['debug'] = 1;
            unset($array['debug']);
        }
        if ($this->file == $_GET['page']){
            $this->infos['menu_options'] = $array;
        }
        $this->option=array();

        /* Check this file is registered as a plugin, do it if needed */

        /* Get infos about the theme and particularly its 'shortname'
         * which is used to name the entry in wp_options where data are stored */
        $this->do_init();

        /* Read data from options table */
        $this->read_options();

        /* Are we in the admin area ? Add a menu then ! */
        add_action('admin_menu', array(&$this, 'add_menu'));        
        add_action('init', array(&$this, 'process_admin_settings'));
    }


    /* Add an entry to the admin menu area */
    function add_menu() {
        global $wp_version;
        if ( $wp_version >= 2 ) {
            $level = 'edit_themes';
        } else {
            $level = 9;
        }
        //add_submenu_page('themes.php', 'Configure ' . $this->infos[theme_name], $this->infos[theme_name], 9, $this->infos['path'] . '/functions.php', array(&$this,'admin_menu'));
		if( $this->infos['skin'] ) {
			add_theme_page('Configure ' . $this->infos['theme_name'] . ' Skin', $this->infos['theme_name'] . ' Skin',
							'edit_themes', $this->file, array(&$this,'admin_menu'));
		}
		else {
			add_theme_page('Configure ' . $this->infos['theme_name'], $this->infos['theme_name'], 'edit_themes', $this->file, array(&$this,'admin_menu'));
			/* Thank you MCincubus for opening my eyes on the last parameter :) */
		}
    }

    /* Get infos about this theme */
    function do_init() {
        $shouldbe= $this->infos['path'];
		if( dirname($shouldbe) == '.' ) {
			$themes = get_themes();
            foreach ($themes as $theme) {
                $current= basename($theme['Template Dir']);
                if ($current == $shouldbe) {
                	if (get_template() == $current) {
                        $this->infos['active'] = TRUE;
                    } else {
                        $this->infos['active'] = FALSE;
                    }
					$this->infos['skin'] = FALSE;
					$this->infos['type'] = 'theme';
					$this->infos['Type'] = 'Theme';
	                $this->infos['theme_name'] = $theme['Name'];
	                $this->infos['theme_shortname'] = $current;
	                $this->infos['theme_site'] = $theme['Title'];
	                $this->infos['theme_version'] = $theme['Version'];
	                $this->infos['theme_author'] = preg_replace("#>\s*([^<]*)</a>#", ">\\1</a>", $theme['Author']);

					$this->infos['option'] = 'theme-'.$this->infos['theme_shortname'].'-options';
					$this->infos['option_desc'] = 'Options for theme '.$this->infos['theme_name'];
                }
            }
		}
		else if ( basename(dirname($shouldbe)) == "skins" ) {
			$cs = null;
			$skin_loc = null;
			if( function_exists( 'current_skin_info' ) ) {
				$cs = current_skin_info();				
				$skin_loc = str_replace(get_theme_root() . '/', '', get_skin_directory());
				$this->infos['active'] = ( $skin_loc == $shouldbe );
				$this->infos['skin'] = TRUE;
				$this->infos['type'] = 'skin';
				$this->infos['Type'] = 'Skin';
				$this->infos['theme_name'] = $cs->name;
				$this->infos['theme_shortname'] = $cs->stylesheet;
				$this->infos['theme_site'] = $cs->title;
				$this->infos['theme_version'] = $cs->version;
				$this->infos['theme_author'] = preg_replace("#>\s*([^<]*)</a>#", ">\\1</a>", $cs->author);
				$themes = get_themes();
				$ct = null;
				foreach( $themes as $name => $data ) {
					if( $data[ 'Stylesheet'] == get_stylesheet() ) {
						$ct = $data[ 'Name'];
						break;
					}
				}
				if( $ct == null ) {
					$ct = get_current_theme();
				}
				
				$this->infos['option'] = 'theme-'.$ct . '-' . $this->infos['theme_shortname'].'-options';
				$this->infos['option_desc'] = 'Options for ' . $ct . ' skin '.$this->infos['theme_name'];
			}
			else {
				$skin_loc = str_replace(get_theme_root() . '/', '', get_stylesheet_directory()) . '/skins/default';
				$this->infos['active'] = ( $skin_loc == $shouldbe );
				$this->infos['skin'] = TRUE;
				$this->infos['type'] = 'skin';
				$this->infos['Type'] = 'Skin';
				$this->infos['theme_name'] = 'Default';
				$this->infos['theme_shortname'] = 'default';
				$this->infos['theme_site'] = 'Default';
				$this->infos['theme_version'] = null;
				$themes = get_themes();
				$ct = null;
				foreach( $themes as $name => $data ) {
					if( $data[ 'Stylesheet'] == get_stylesheet() ) {
						$ct = $data[ 'Name' ];
						break;
					}
				}
				if( $ct == null ) {
					$ct = get_current_theme();
				}
				global $wp_themes;
				$this->infos['theme_author'] = preg_replace("#>\s*([^<]*)</a>#", ">\\1</a>", $wp_themes[ $ct ][ 'Author' ] );
				$this->infos['option'] = 'theme-'.$ct . '-' . $this->infos['theme_shortname'].'-options';
				$this->infos['option_desc'] = 'Options for ' . $ct . ' skin '.$this->infos['theme_name'];
			}
		}
    }

    /* Read theme options as defined by user and populate the array $this->option */
    function read_options() {
        $options = get_option($this->infos['option']);
        if( !empty( $options ) ) {
	        foreach ($options as $key=>$val) {
	            $this->option["$key"] = stripslashes($val);
	        }
        }
        return $this->option;
    }

    /* Write theme options as defined by user in database */
    function store_options($array) {
        update_option($this->infos['option'],'');
        return update_option($this->infos['option'],$array);
    }

    /* Delete options from database */
      function delete_options() {
        /* Remove entry from database */
        delete_option($this->infos['option']);
        /* Revert theme back to Kubrick if this theme was activated */
        if ($this->infos['active']) {
			if( $this->infos['skin'] ) {
                update_option('skin', 'default');
                do_action('switch_skin', 'Default');
			}
			else {
                update_option('template', 'default');
                update_option('stylesheet', 'default');
                do_action('switch_theme', 'Default');
			}
        }
    }

    /* Check if the theme has been loaded at least once (so that this file has been registered as a plugin) */
    function is_installed() {
        global $wpdb;
        $where = 'theme-'.$this->infos['theme_shortname'].'-options';
        $check = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->options WHERE option_name = '$where'");
        if ($check == 0) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /* Theme used for the first time (create blank entry in database) */
    function do_firstinit() {
        global $wpdb;
        $options = array();
        foreach(array_keys($this->option) as $key) {
            $options["$key"]='';
        }
		if( $this->infos['skin'] ) {
			$ct = current_theme_info();
			add_option('theme-'.$ct->stylesheet . '-' . $this->infos['theme_shortname'].'-options',$options, 'Options for ' . $ct->stylesheet . ' skin '.$this->infos['theme_name']);			
		}
		else {
			add_option('theme-'.$this->infos['theme_shortname'].'-options',$options, 'Options for theme '.$this->infos['theme_name']);
        }
		return $this->infos['Type'] . " options added in database (1 entry in table '". $wpdb->options ."')";
    }

	function process_admin_settings()
	{
		if(@$_POST[$this->infos[ 'type' ] .'_admin_action'])
		{
			if (@$_POST[$this->infos[ 'type' ] .'_admin_action'] == 'store_option') {
                unset($_POST[$this->infos[ 'type' ] .'_admin_action']);
                $result = $this->store_options($_POST);

                $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
                $protocol = strtolower($_SERVER["SERVER_PROTOCOL"]);
                $protocol = substr($protocol, 0, strpos($protocol, "/")).$s;
                $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
                $url = $protocol."://".$_SERVER['HTTP_HOST'].$port.$_SERVER['REQUEST_URI'];
                
				if( $result )
					$url = add_query_arg( $this->infos[ 'type' ] . '_saved', "true", $url );
				else
					$url = add_query_arg( $this->infos[ 'type' ] . '_saved', "false", $url );				                
				wp_redirect($url);
				exit;
			} elseif (@$_POST[$this->infos[ 'type' ] .'_admin_action'] == 'delete_options') {
                $this->delete_options();
				if( $this->infos[ 'skin' ] ) {
					wp_redirect('themes.php?page=skinner.php&activated=true');
				}
				else {
			 		wp_redirect('themes.php?activated=true');
				}
				exit;
            }
		}
		else
		{
			$this->msg = null;
			if( isset( $_GET[ $this->infos[ 'type' ] .'_saved' ] ) ) {
				if( $_GET[ $this->infos[ 'type' ] .'_saved' ] ) {
					$this->msg = 'Options saved. <a href="'.get_bloginfo('home') .'">View site &raquo;</a>';
				}
				else {
					$this->msg = 'Option where not saved!';
				}
			}
		}
	}
	
    /* The mother of them all : the Admin Menu printing func */
    function admin_menu () {
        global $cache_settings, $wpdb;
					
		if (!$this->is_installed()) {
            $this->msg = $this->do_firstinit();
        }

        if($this->msg) {
			print '<div id="message1" class="updated fade"><p>' . $this->msg . '</p></div>';
		}
        echo '<div class="wrap"><h2>Thank you !</h2>';
        echo '<p>Thank you for installing ' . $this->infos['theme_site'] . ', ';
		if( $this->infos[ 'skin' ] ) {
			$ct = current_theme_info();
			echo 'a skin for ' . $ct->title . ' ' . $ct->version . '. This skin';
		}
		else {
			echo 'a theme for Wordpress. This theme';
		}
		echo ' was made by '.$this->infos['theme_author'].'. </p>';
        if (!$this->infos['active']) { /* theme is not active */
            echo '<p>(Please note that this ';
			echo 'theme is currently <strong>not activated</strong> on your site as the default theme.)</p>';
        }

        $cache_settings = '';
        $check = $this->read_options();
        
        echo "<h2>Configure " . $this->infos['theme_site'] . "</h2>";
        echo '<p>This theme allows you to configure some variables to suit your blog, which are :</p>
        <form action="" method="post">
        <input type="hidden" name="'.$this->infos[ 'type' ] .'_admin_action" value="store_option" />
        <table cellspacing="2" cellpadding="5" border="0" width=100% class="editform">';

        /* Print form, here comes the fun part :) */
        foreach ($this->infos['menu_options'] as $key=>$val) {
            $items='';
            preg_match('/\s*([^{#]*)\s*({([^}]*)})*\s*([#]*\s*(.*))/', $val, $matches);
            if ($matches[3]) {
                $items = split("\|", $matches[3]);
            }

            print "<tr valign='top'><th scope='row' width='33%'>\n";
            if (@$items) {
                $type = array_shift($items);
                switch ($type) {
                case 'separator':
                    print '<h3>'.$matches[1]."</h3></th>\n<td>&nbsp;</td>";
                    break;
                case 'radio':
                    print $matches[1]."</th>\n<td>";
                    while ($items) {
                        $v=array_shift($items);
                        $t=array_shift($items);
                        $checked='';
                        if ($v == $this->option[$key]) $checked='checked';
                        print "<label for='${key}${v}'><input type='radio' id='${key}${v}' name='$key' value='$v' $checked /> $t</label>";
                        if (@$items) print "<br />\n";
                    }
                    break;
                case 'textarea':
                    $rows=array_shift($items);
                    $cols=array_shift($items);
                print "<label for='$key'>".$matches[1]."</label></th>\n<td>";
                    print "<textarea name='$key' id='$key' rows='$rows' cols='$cols'>" . $this->option[$key] . "</textarea>";
                    break;
                case 'checkbox':
                    print $matches[1]."</th>\n<td>";
                    while ($items) {
                        $k=array_shift($items);
                        $v=array_shift($items);
                        $t=array_shift($items);
                        $checked='';
                        if ($v == $this->option[$k]) $checked='checked';
                        print "<label for='${k}${v}'><input type='checkbox' id='${k}${v}' name='$k' value='$v' $checked /> $t</label>";
                        if (@$items) print "<br />\n";
                    }
                    break;
                }
            } else {
                print "<label for='$key'>".$matches[1]."</label></th>\n<td>";
                print "<input type='text' name='$key' id='$key' value='" . $this->option[$key] . "' />";
            }

            if ($matches[5]) print '<br/>'. $matches[5];
            print "</td></tr>\n";
        }
        echo '</table>
        <p class="submit"><input type="submit" value="Store Options" /></p>
        </form>';

        if ($this->infos['debug'] and $this->option) {
            $g = '<span style="color:#006600">';
            $b = '<span style="color:#0000CC">';
            $o = '<span style="color:#FF9900">';
            $r = '<span style="color:#CC0000">';
            echo '<h2>Programmer\'s corner</h2>';
            echo '<p>The array <em>$'. $this->infos['classname'] . '->option</em> is actually populated with the following keys and values :</p>
            <p><pre class="updated">';
            $count = 0;
            foreach ($this->option as $key=>$val) {
                $val=str_replace('<','&lt;',$val);
                if ($val) {
                    print '<span class="ttkline">'.$g.'$'.$this->infos['classname'].'</span>'.$b.'-></span>'.$g.'option</span>'.$b.'[</span>'.$g.'\'</span>'.$r.$key.'</span>'.$g.'\'</span>'.$b.']</span>'.$g.' = "</span>'. $o.$val.'</span>'.$g."\"</span></span>\n";
                    $count++;
                }
            }
            if (!$count) print "\n\n";
            echo '</pre><p>To disable this report (for example before packaging your theme and making it available for download), remove the line "&nbsp;<em>\'debug\' => \'debug\'</em>&nbsp;" in the array you edited at the beginning of this file.</p>';
        }

        ?>
		<h2>Delete <?php _e($this->infos[ 'Type' ]); ?> options</h2>
        <p>To completely remove these <?php _e($this->infos[ 'type' ]); ?> options from your database (reminder: they are all stored in a single entry, in Wordpress options table <em><?php echo $wpdb->options; ?></em>), click on
        the following button. You will be then redirected to the <a href="themes.php<?php if( $this->infos[ 'skin' ] ) { _e('?page=skinner.php'); } ?>"><?php _e($this->infos[ 'Type' ]); ?> admin interface</a>
        <?php 
		if ($this->infos['active']) {
            echo ' and the Default ' . $this->infos[ 'type' ] . ' will have been activated';
        }
        ?>.</p>
        <p><strong>Special notice for people allowing their readers to change <?php _e($this->infos[ 'type' ]); ?> </strong> (i.e. using a <?php _e($this->infos[ 'Type' ]); ?>  Switcher on their blog)<br/>
        Unless you really remove the <?php _e($this->infos[ 'type' ]); ?>  files from your server, this <?php _e($this->infos[ 'type' ]); ?>  will still be available to users, and therefore will self-install again as soon as someone selects it. Also, all custom variables as defined in the above menu will be blank, this could lead to unexpected behaviour.
        Press "Delete" only if you intend to remove the <?php _e($this->infos[ 'type' ]); ?>  files right after this.</p>
        <form action="" method="post">
        <input type="hidden" name="<?php echo $this->infos[ 'type' ]; ?>_admin_action" value="delete_options" />
        <p class="submit"><input type="submit" value="Delete Options" onclick="return confirm(\'Are you really sure you want to delete ?\');"/></p>
        </form>
<h2>Credits</h2>
<p><?php echo $this->infos['theme_site']; ?> has been created by <?php echo $this->infos['theme_author']; ?>.
This administration menu uses the
<a href="http://windyroad.org/software/wordpress/presentation-toolkit-plugin"
title="Presentation Toolkit Plugin : create a admin menu for your own theme or skin in as little as 3 lines">Presentation Toolkit Plugin</a>
by <a href="http://windyroad.org" title="Windy Road">Windy Road</a>, which is based on the
<a href="http://frenchfragfactory.net/ozh/my-projects/wordpress-theme-toolkit-admin-menu/"
title="Wordpress Theme Toolkit : create a admin menu for your own theme as easily as editing 3 lines">Wordpress Theme Toolkit</a> 
by <a href="http://frenchfragfactory.net/ozh/" title="planetOzh">Ozh</a>.
This was all made possible thanks to <a href="http://wordpress.org/" title="Best Blogware Ever.">Wordpress</a>.</p>
</div>

<p style="text-align: center;">
<a href="http://windyroad.org/software/wordpress/presentation-toolkit-plugin">Presentation Toolkit Plugin</a><br />
by<br />
<a href="http://windyroad.org"><img src="http://windyroad.org/static/logos/windyroad-105x15.png" style="border: none;" alt="Windy Road" /></a><br />
with help from<br />
<a href="http://planetOzh.com/"><img src="http://frenchfragfactory.net/ozh/wp-images/btn_planetozh.png" border="0" alt="planetOzh.com" /></a>
</p>

<?php
		}		
    }
    
    
function add_presentationtoolkit_query( $key, $url ) {
	global $ptk;
	if( $ptk[ $key ] ) {
		return htmlspecialchars( add_query_arg( $ptk[ $key ]->option, $url ) );
	}
	return $url;
}

function add_presentationtoolkit_skin_query( $key, $url ) {
	return add_presentationtoolkit_query( get_stylesheet() . '-' . $key, $url );
}

function add_presentationtoolkit_skin_options( $urls, $skin ) {
	global $ptk;
	if( !isset( $ptk[ get_stylesheet() . '-' . $skin ]) )
		return $urls;
	$options = $ptk[ get_stylesheet() . '-' . $skin ];
	if( $options ) {
		foreach( $urls as $key => $url ) {
			if ( preg_match('|\.php|', $url) ) {
				$urls[ $key ] = add_query_arg( $options->option, $url );
			}
		}
		return $urls;
	}
	return $urls;
}

add_filter('skinstyles', 'add_presentationtoolkit_skin_options', 10, 2 );

function get_theme_options()
{
	global $ptk;
	return $ptk[ get_current_theme() ];
}

function get_theme_option( $option )
{
	$options = get_theme_options();
	if( isset( $options->option[ $option ] ) ) {
		return $options->option[ $option ];
	}

	return null;
}


function get_skin_options()
{
	global $ptk;
	$skin = function_exists( 'get_skin' ) ? get_skin() : 'default';
	return $ptk[ get_stylesheet() . '-' . $skin ];
}

function get_skin_option( $option )
{
	$options = get_skin_options();
	if( isset( $options->option[ $option ] ) ) {
		return $options->option[ $option ];
	}
	return null;
}

?>