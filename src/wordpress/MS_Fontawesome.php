<?php

namespace msltns\wordpress;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use msltns\wordpress\MS_Element;

/**
 * Class MS_Fontawesome adds some useful shortcodes.
 *
 * @category 	Class
 * @package  	MS_Fontawesome
 * @author 		msltns <info@msltns.com>
 * @version  	0.0.1
 * @since       0.0.1
 * @license 	GPL 3
 *          	This program is free software; you can redistribute it and/or modify
 *          	it under the terms of the GNU General Public License, version 3, as
 *          	published by the Free Software Foundation.
 *          	This program is distributed in the hope that it will be useful,
 *          	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *          	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *          	GNU General Public License for more details.
 *          	You should have received a copy of the GNU General Public License
 *          	along with this program; if not, write to the Free Software
 *          	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
if ( ! class_exists( '\msltns\wordpress\MS_Fontawesome' ) ) {
    
    class MS_Fontawesome extends MS_Element {
        
        /**
    	 * Main constructor.
    	 */
        public function __construct() {
            
            $this->routes = [
                'msltnscss/fontawesome' => [
                    'file'  => MSLTNS_ASSETS_DIR . '/css/fontawesome.min.css',
                    'type'  => 'css',
                    'ctype' => 'text/css',
                ],
                'msltnsfonts/fontawesomeeot' => [
                    'file'  => MSLTNS_ASSETS_DIR . '/fonts/fontawesome-webfont.eot',
                    'type'  => 'font',
                    'ctype' => 'application/vnd.ms-fontobject',
                ],
                'msltnsfonts/fontawesomesvg' => [
                    'file'  => MSLTNS_ASSETS_DIR . '/fonts/fontawesome-webfont.svg',
                    'type'  => 'font',
                    'ctype' => 'image/svg+xml',
                ],
                'msltnsfonts/fontawesomettf' => [
                    'file'  => MSLTNS_ASSETS_DIR . '/fonts/fontawesome-webfont.ttf',
                    'type'  => 'font',
                    'ctype' => 'application/font-ttf',
                ],
                'msltnsfonts/fontawesomewoff' => [
                    'file'  => MSLTNS_ASSETS_DIR . '/fonts/fontawesome-webfont.woff',
                    'type'  => 'font',
                    'ctype' => 'application/font-woff',
                ],
                'msltnsfonts/fontawesomewoff2' => [
                    'file'  => MSLTNS_ASSETS_DIR . '/fonts/fontawesome-webfont.woff2',
                    'type'  => 'font',
                    'ctype' => 'application/font/woff2',
                ],
            ];
            
            $this->scripts = [
                'fontawesomecss' => [
                    'action' => 'register',
                    'type'   => 'style',
                    'handle' => 'fontawesome',
                    'src'    => trailingslashit( network_home_url() ) . 'msltnscss/fontawesome',
                ],
            ];
            
        }
    }
}
