<?php
/*
Plugin Name: Orbisius Just Write
Plugin URI: http://club.orbisius.com/products/wordpress-plugins/orbisius-just-write/
Description: Show last viewed post(s) - 3 max, and recommend X number of related posts.
Version: 1.0.2
Author: Slavi Marinov | Orbisius
Author URI: http://orbisius.com
*/

define( 'ORBISIUS_JUST_WRITE_URL', plugin_dir_url( __FILE__) );
define( 'ORBISIUS_JUST_WRITE_DIR', dirname( __FILE__) );
define( 'ORBISIUS_JUST_WRITE_BASE_PLUGIN_FILE',  __FILE__ );
define( 'ORBISIUS_JUST_WRITE_PLUGIN_NAME', "Orbisius Just Write" );

require_once(ORBISIUS_JUST_WRITE_DIR . '/share/php-ixr-1.7.4/IXR_Library.php');
require_once(ORBISIUS_JUST_WRITE_DIR . '/lib/Orbisius_Just_Write.php');
require_once(ORBISIUS_JUST_WRITE_DIR . '/lib/Orbisius_Just_Write_Result.php');
require_once(ORBISIUS_JUST_WRITE_DIR . '/lib/Orbisius_Just_Write_HTML_Util.php');

require_once(ORBISIUS_JUST_WRITE_DIR . '/modules/widgets.php');
require_once(ORBISIUS_JUST_WRITE_DIR . '/modules/front.php');
require_once(ORBISIUS_JUST_WRITE_DIR . '/modules/admin.php');
require_once(ORBISIUS_JUST_WRITE_DIR . '/modules/cpt.php');

