<?php

namespace msltns\wordpress;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use msltns\wordpress\MS_Dynamic_Router;
use msltns\wordpress\MS_Utils;

/**
 * Class MS_Element adds some useful shortcodes.
 *
 * @category 	Class
 * @package  	MS_Element
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
if ( ! class_exists( '\msltns\wordpress\MS_Element' ) ) {
    
    if ( ! defined( 'MSLTNS_ASSETS_VERSION' ) ) {
        define( 'MSLTNS_ASSETS_VERSION', '0.0.1' );
    }
    
    if ( ! defined( 'MSLTNS_ASSETS_DIR' ) ) {
        define( 'MSLTNS_ASSETS_DIR', __DIR__ . '/../assets' );
    }
    
    abstract class MS_Element {
        
        protected $routes;
        
        protected $scripts;
        
        protected $utils;
        
        /**
    	 * Main constructor.
    	 */
        public function __construct() {
            $this->utils = MS_Utils::instance();
            
            add_action( 'init', array( $this, 'register_dynamic_route' ), 5 );
            add_action( 'msltns_dynamic_route_file', array( $this, 'handle_dynamic_route_file' ), 10, 2 );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        }
        
    	/**
    	 * Registers necessary dynamic route.
    	 * 
         * @return void
    	 */
        public function register_dynamic_route() {
        
            // make sure our DynamicRouter class exists
            if ( ! class_exists( '\msltns\wordpress\MS_Dynamic_Router' ) ) {
                return false;
            }

            // register asset routes
            foreach ( $this->routes as $route => $data ) {
                MS_Dynamic_Router::create( $route, $data['type'] );
            }
            
            // handle our page routes
            MS_Dynamic_Router::handle();
        }
        
    	/**
    	 * Handles dynamic route files.
    	 * 
         * @return void
    	 */
        public function handle_dynamic_route_file( $type, $route ) {
            $dest_file = $content_type = '';
            if ( in_array( $type, [ 'css', 'js', 'img', 'font' ] ) && ! empty( $this->routes[ $route ] ) ) {
                $dest_file    = $this->routes[ $route ]['file'];
                $content_type = $this->routes[ $route ]['ctype'];
            }
            
            if ( ! empty( $dest_file ) && ! empty( $content_type ) && file_exists( $dest_file ) ) {
                header( 'Content-type: ' . $content_type );
                header( 'Content-Length: ' . filesize( $dest_file ) );
                readfile( $dest_file );
                exit;
            }
        }
        
    	/**
    	 * Registers and enqueues scripts and styles.
    	 * 
    	 * @return void
    	 */
    	public function enqueue_scripts() {
            if ( ! empty( $this->scripts ) ) {
                foreach ( $this->scripts as $key => $data ) {
                    $action  = ! empty( $data['action'] ) ? $data['action'] : 'enqueue';
                    $type    = ! empty( $data['type'] ) ? $data['type'] : 'style';
                    $handle  = ! empty( $data['handle'] ) ? $data['handle'] : "msltns-{$type}";
                    $source  = ! empty( $data['src'] ) ? $data['src'] : '';
                    $deps    = ! empty( $data['deps'] ) ? $data['deps'] : [];
                    $version = ! empty( $data['vers'] ) ? $data['vers'] : MSLTNS_ASSETS_VERSION;
                    $footer  = isset( $data['footer'] ) ? boolval( $data['footer'] ) : true;
                    if ( ! empty( $src ) ) {
                        $args = [ $handle, $source, $deps, $version ];
                        if ( 'script' === $type ) {
                            $args[] = $footer;
                        }
                        call_user_func_array( "wp_{$action}_{$type}", $args );
                    }
                }
            }
        }
    }
}
