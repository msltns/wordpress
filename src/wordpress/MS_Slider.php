<?php

namespace msltns\wordpress;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use msltns\wordpress\MS_Element;

/**
 * Class MS_Slider adds some useful shortcodes.
 *
 * @category 	Class
 * @package  	MS_Slider
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
if ( ! class_exists( '\msltns\wordpress\MS_Slider' ) ) {
    
    class MS_Slider extends MS_Element {
        
        /**
    	 * Main constructor.
    	 */
        public function __construct() {
            parent::__construct();
            
            $this->routes = [
                'msltnscss/slick' => [
                    'file'  => MSLTNS_ASSETS_DIR . '/css/slick.min.css',
                    'type'  => 'css',
                    'ctype' => 'text/css',
                ],
                'msltnscss/slicktheme' => [
                    'file'  => MSLTNS_ASSETS_DIR . '/css/slick-theme.min.css',
                    'type'  => 'css',
                    'ctype' => 'text/css',
                ],
                'msltnsjs/slick' => [
                    'file'  => MSLTNS_ASSETS_DIR . '/js/slick.min.js',
                    'type'  => 'js',
                    'ctype' => 'application/javascript',
                ],
                'msltnsimg/ajaxloader' => [
                    'file'  => MSLTNS_ASSETS_DIR . '/images/ajax-loader.gif',
                    'type'  => 'img',
                    'ctype' => 'image/gif',
                ],
                'msltnsfonts/slickeot' => [
                    'file'  => MSLTNS_ASSETS_DIR . '/fonts/slick.eot',
                    'type'  => 'font',
                    'ctype' => 'application/vnd.ms-fontobject',
                ],
                'msltnsfonts/slicksvg' => [
                    'file'  => MSLTNS_ASSETS_DIR . '/fonts/slick.svg',
                    'type'  => 'font',
                    'ctype' => 'image/svg+xml',
                ],
                'msltnsfonts/slickttf' => [
                    'file'  => MSLTNS_ASSETS_DIR . '/fonts/slick.ttf',
                    'type'  => 'font',
                    'ctype' => 'application/font-ttf',
                ],
                'msltnsfonts/slickwoff' => [
                    'file'  => MSLTNS_ASSETS_DIR . '/fonts/slick.woff',
                    'type'  => 'font',
                    'ctype' => 'application/font-woff',
                ],
            ];
            
            $this->scripts = [
                'slickcss' => [
                    'action' => 'register',
                    'type'   => 'style',
                    'handle' => 'slick',
                    'src'    => trailingslashit( home_url() ) . 'msltnscss/slick',
                ],
                'slickthemecss' => [
                    'action' => 'register',
                    'type'   => 'style',
                    'handle' => 'slick-theme',
                    'src'    => trailingslashit( home_url() ) . 'msltnscss/slicktheme',
                ],
                'slickjs' => [
                    'action' => 'register',
                    'type'   => 'script',
                    'handle' => 'slick',
                    'src'    => trailingslashit( home_url() ) . 'msltnsjs/slick',
                    'deps'   => [ 'jquery' ],
                    'footer' => true,
                ],
            ];
            
            add_shortcode( 'ms_post_slider', array( $this, 'generate_post_slider' ) );
            
            add_shortcode( 'msltns_post_slider', array( $this, 'generate_post_slider' ) );
        }
        
        /**
         * Generates a slider out of posts.
         *
         * @param array  $atts      The shortcode attributes.
         * @param string $content   The shortcode content.
         * @return string The resolved html code.
         */
        public function generate_post_slider( $atts, $content ) {
            
            $defaults = array(
        		'limit'      => -1,
        		'post_type'  => 'post',
        		'category'   => 0,
                'orderby'    => 'title',
                'order'      => 'ASC',
        		'class'      => '',
        		'slides'     => 3,
                'image_size' => 'thumbnail',
                'autoplay'   => false,
        	);
        	extract( shortcode_atts( $defaults, $atts ) );
            
            $args = array(
                'post_type'        => $post_type,
                'post_status'      => 'publish',
                'numberposts'      => $limit,
        		'category'         => $category,
                'orderby'          => $orderby,
                'order'            => $order,
        		'include'          => array(),
        		'exclude'          => array(),
        		'meta_key'         => '',
        		'meta_value'       => '',
                // 'suppress_filters' => true,
    		);
    		$posts = get_posts( $args );
            
            return $this->create_post_slider( $posts, $atts );
        }
        
    	/**
         * Creates a post slider based on slick.
         *
         * @param array $posts  The posts.
         * @param array $args   The args.
         * @return string The html output.
         */
		private function create_post_slider( $posts = array(), $args = array() ) {
            
            if ( empty( $posts ) ) {
                return false;
            }
            
            wp_enqueue_style( 'slick' );
            wp_enqueue_style( 'slick-theme' );
            wp_enqueue_script( 'slick' );

			$class_name  = ! empty( $args['class'] ) ? $args['class'] : 'msltns-slider';
			$slide_count = ! empty( $args['slides'] ) ? absint( $args['slides'] ) : 3;
			$image_size  = ! empty( $args['image_size'] ) ? trim( $args['image_size'] ) : 'thumbnail';
			$autoplay    = ! empty( $args['autoplay'] ) ? trim( $args['autoplay'] ) : false;

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

			$autoplay = $autoplay ? 'true' : 'false';

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
