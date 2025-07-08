<?php

namespace msltns\wordpress;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use msltns\wordpress\WP_Dynamic_Router;

/**
 * Class WP_Slider adds some useful shortcodes.
 *
 * @category 	Class
 * @package  	WP_Slider
 * @author 		Daniel Muenter <info@msltns.com>
 * @version  	0.0.1
 * @since 0.0.1
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
if ( ! class_exists( '\msltns\wordpress\WP_Slider' ) ) {
    
    if ( ! defined( 'WPWOODO_ASSETS_DIR' ) ) {
        define( 'WPWOODO_ASSETS_DIR', __DIR__ . '/../assets' );
    }
    
    class WP_Slider {
        
        private $routes;
        
        /**
    	 * Main constructor.
    	 */
        public function __construct() {
            
            $this->routes = [
                'msltnscss/slick' => [
                    'file'  => WPWOODO_ASSETS_DIR . '/css/slick.min.css',
                    'type'  => 'css',
                    'ctype' => 'text/css',
                ],
                'msltnscss/slicktheme' => [
                    'file'  => WPWOODO_ASSETS_DIR . '/css/slick-theme.min.css',
                    'type'  => 'css',
                    'ctype' => 'text/css',
                ],
                'msltnsjs/slick' => [
                    'file'  => WPWOODO_ASSETS_DIR . '/js/slick.min.js',
                    'type'  => 'js',
                    'ctype' => 'application/javascript',
                ],
                'msltnsimg/ajaxloader' => [
                    'file'  => WPWOODO_ASSETS_DIR . '/images/ajax-loader.gif',
                    'type'  => 'img',
                    'ctype' => 'image/gif',
                ],
                'msltnsfonts/slickeot' => [
                    'file'  => WPWOODO_ASSETS_DIR . '/fonts/slick.eot',
                    'type'  => 'font',
                    'ctype' => 'application/vnd.ms-fontobject',
                ],
                'msltnsfonts/slicksvg' => [
                    'file'  => WPWOODO_ASSETS_DIR . '/fonts/slick.svg',
                    'type'  => 'font',
                    'ctype' => 'image/svg+xml',
                ],
                'msltnsfonts/slickttf' => [
                    'file'  => WPWOODO_ASSETS_DIR . '/fonts/slick.ttf',
                    'type'  => 'font',
                    'ctype' => 'application/font-ttf',
                ],
                'msltnsfonts/slickwoff' => [
                    'file'  => WPWOODO_ASSETS_DIR . '/fonts/slick.woff',
                    'type'  => 'font',
                    'ctype' => 'application/font-woff',
                ],
            ];
            
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
            if ( ! class_exists( '\msltns\wordpress\WP_Dynamic_Router' ) ) {
                return false;
            }

            // register asset routes
            foreach( $this->routes as $route => $data ) {
                WP_Dynamic_Router::create( $route, $data['type'] );
            }
            
            // handle our page routes
            WP_Dynamic_Router::handle();
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
            wp_register_style( 'slick', trailingslashit( network_home_url() ) . 'msltnscss/slick' );
            wp_register_style( 'slick-theme', trailingslashit( network_home_url() ) . 'msltnscss/slicktheme' );
            wp_register_script( 'slick', trailingslashit( network_home_url() ) . 'msltnsjs/slick', array( 'jquery' ), false, true );
        }
        
        /**
         * Creates a post slider based on slick.
         *
         * @param array $posts  The posts.
         * @param array $args   The args.
         * @return string The html output.
         */
		public function create_post_slider( $posts = array(), $args = array() ) {
            
            if ( empty( $posts ) ) {
                return false;
            }
            
            wp_enqueue_style( 'slick' );
            wp_enqueue_style( 'slick-theme' );
            wp_enqueue_script( 'slick' );

			$class_name  = isset( $args['class'] ) ? $args['class'] : 'msltns-slider';
			$slide_count = isset( $args['slides'] ) ? absint( $args['slides'] ) : 3;
			$image_size  = isset( $args['image_size'] ) ? absint( $args['image_size'] ) : 'thumbnail';

			$output = '<div class="' . esc_attr( $class_name ) . '">';

		    $count = 0;
		    foreach ( $posts as $post ) {
		        $image_id  = get_post_thumbnail_id( $post->ID  );
		        if ( $image_id ) {
		            $image = wp_get_attachment_image_src( $image_id, $image_size );
                    list( $src, $width, $height ) = $image;
		            $output .= '    <div><a href="' . get_permalink( $post->ID ) . '" alt="' . $post->post_title . '"><img data-lazy="' . $src . '" width="'.$width.'" height="'.$height.'"></a></div>';
		            $count++;
		        }
		    }
			$output .= '</div><br/>';

			$autoplay = ( $count > ( $slide_count + 1 ) ) ? 'true' : 'false';

		    $output .= "<script>
		                jQuery(document).ready(function() {
							jQuery('.{$class_name}').slick({
								lazyLoad: 'ondemand',
								slidesToShow: {$slide_count},
								slidesToScroll: 1,
								autoplay: {$autoplay},
								autoplaySpeed: 3000,
							});
		                });
		             </script>"; 

		    ob_start();
		    echo $output;
		    return ob_get_clean(); 
		}

    }
    
}
