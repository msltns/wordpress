<?php

namespace msltns\wordpress;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use msltns\wordpress\WP_Dynamic_Router;
use msltns\wordpress\WP_Slider;
use msltns\wordpress\WP_Utils;

/**
 * Class WP_Shortcode_Manager adds some useful shortcodes.
 *
 * @category 	Class
 * @package  	WP_Shortcode_Manager
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
if ( ! class_exists( '\msltns\wordpress\WP_Shortcode_Manager' ) ) {
    
    if ( ! defined( 'WPWOODO_ASSETS_DIR' ) ) {
        define( 'WPWOODO_ASSETS_DIR', __DIR__ . '/../assets' );
    }
    
    class WP_Shortcode_Manager {
        
        private $utils;
        
        private $routes;
        
        private $slider;
        
        private $tabs_data;
        
        /**
    	 * Main constructor.
    	 */
        public function __construct() {
            
            $this->utils  = WP_Utils::instance();
            $this->slider = new WP_Slider();
            $this->routes = [
                'msltnscss/frontend' => [
                    'file'  => WPWOODO_ASSETS_DIR . '/css/msltns.min.css',
                    'type'  => 'css',
                    'ctype' => 'text/css',
                ],
                'msltnsjs/autocolumn' => [
                    'file'  => WPWOODO_ASSETS_DIR . '/js/autocolumn.min.js',
                    'type'  => 'js',
                    'ctype' => 'application/javascript',
                ],
                'msltnscss/bootstrap' => [
                    'file'  => WPWOODO_ASSETS_DIR . '/css/bootstrap.min.css',
                    'type'  => 'css',
                    'ctype' => 'text/css',
                ],
                'msltnsjs/bootstrap' => [
                    'file'  => WPWOODO_ASSETS_DIR . '/js/bootstrap.bundle.min.js',
                    'type'  => 'js',
                    'ctype' => 'application/javascript',
                ],
            ];
            
            add_action( 'init', array( $this, 'register_dynamic_route' ), 5 );
            add_action( 'msltns_dynamic_route_file', array( $this, 'handle_dynamic_route_file' ), 10, 2 );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
            
            add_shortcode( 'msltns_term_list', array( $this, 'generate_term_list' ) );
            add_shortcode( 'msltns_post_list', array( $this, 'generate_post_list' ) );
            
            add_shortcode( 'msltns_post_slider', array( $this, 'generate_post_slider' ) );
            
			add_shortcode( 'msltns_tabs', array( $this, 'generate_tab_pane' ) );
			add_shortcode( 'msltns_tab', array( $this, 'generate_single_tab' ) );
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
            wp_register_style( 'msltns-shortcodes', trailingslashit( network_home_url() ) . 'msltnscss/frontend' );
            
            wp_register_script( 'autocolumn', trailingslashit( network_home_url() ) . 'msltnsjs/autocolumn', array( 'jquery' ), false, true );
            
            wp_register_style( 'bootstrap', trailingslashit( network_home_url() ) . 'msltnscss/bootstrap' );
            wp_register_script( 'bootstrap', trailingslashit( network_home_url() ) . 'msltnsjs/bootstrap', array( 'jquery' ), false, true );
        }
        
        /**
         * Generates a list out of terms.
         *
         * @param array  $atts      The shortcode attributes.
         * @param string $content   The shortcode content.
         * @return string The resolved html code.
         */
        public function generate_term_list( $atts, $content ) {
            
        	$defaults = array(
        		'limit' 		=> -1,
        		'post_type'		=> 'post',
        		'taxonomy'		=> '',
        		'basic_url'		=> network_home_url(),
        		'hierarchical' 	=> 'true',
        		'orderby' 		=> 'name',
        		'order' 		=> 'ASC',
        		'hide_empty' 	=> 'false',
        		'cols'			=> '3'
        	);
        	extract( shortcode_atts( $defaults, $atts ) );
	
            wp_enqueue_script( 'autocolumn' );
    
        	$hierarchical = ( $hierarchical === 'true' ) ? true : false;
        	$hide_empty   = ( $hide_empty === 'true' ) ? true : false;
	
        	$terms = $this->utils->get_term_list( $post_type, $taxonomy, $limit, $hierarchical, $orderby, $order, $hide_empty );
            
            $list = '<div class="columnized">';
	
        	if ( $hierarchical ) {
		
        		// hierarchical presentation
        		$sorted_terms = $this->utils->sort_terms_hierarchically( $terms );
        		foreach( $sorted_terms as $id => $term ) {
        			$list .= '<h4><a href="' . $basic_url . $term->slug . '">' . esc_html( $term->name ) . '</a></h4>';
        			if ( ! empty( $term->children ) ) {
        				foreach( $term->children as $i => $te ) {
        					$list .= '<div class="sub-term"><span class="sub-term-link"><a href="' . $basic_url . $te->slug . '">' . esc_html( $te->name ) . '</a></span></div>';
        					if ( ! empty( $te->children ) ) {
        						foreach( $te->children as $j => $t ) {
        							$list .= '<div class="sub-sub-term"><span class="sub-sub-term-link"><a href="' . $basic_url . $t->slug . '">' . esc_html( $t->name ) . '</a></span></div>';
        						}
        					}
        				}
        			}
        		}
		
        	} else {
		
        		// alphabetical presentation
        		$sorted_terms = $this->utils->sort_terms_alphabetically( $terms );
        		foreach( $sorted_terms as $letter => $term_list ) {
        			if ( ! empty( $term_list ) ) {
        				$list .= "<h4>{$letter}</h4>";
        				foreach( $term_list as $name => $term ) {
        					$list .= '<div class="sub-term"><span class="sub-term-link"><a href="' . $basic_url . $term->slug . '">' . esc_html( $name ) . '</a></span></div>';
        				}
        			}
        		}
        	}
	
        	$list .= '</div>';
        	$list .= '<script>jQuery(function(){var u="' . $cols . '";jQuery(window).width()<992&&(u=2),jQuery(".columnized").columnize({columns:u})});</script>';
            
        	return $list;
        }
        
        /**
         * Generates a list out of posts.
         *
         * @param array  $atts      The shortcode attributes.
         * @param string $content   The shortcode content.
         * @return string The resolved html code.
         */
        public function generate_post_list( $atts, $content ) {
            
        	$defaults = array(
        		'limit'     => -1,
        		'post_type' => 'post',
        		'category'  => 0,
                // 'orderby'   => 'title'
                // 'order'     => 'ASC',
        		'cols'      => '3'
        	);
        	extract( shortcode_atts( $defaults, $atts ) );
            
            wp_enqueue_script( 'autocolumn' );
    
    		$args = array(
                'post_type'        => $post_type,
                'post_status'      => 'publish',
                'numberposts'      => $limit,
        		'category'         => $category,
                // 'orderby'          => $orderby,
                // 'order'            => $order,
        		'include'          => array(),
        		'exclude'          => array(),
        		'meta_key'         => '',
        		'meta_value'       => '',
                // 'suppress_filters' => true,
    		);
    		$posts = get_posts( $args );
            
            $list = '<div class="columnized">';
	
    		// alphabetical presentation
    		$sorted_posts = $this->utils->sort_posts_alphabetically( $posts );
    		foreach( $sorted_posts as $letter => $post_list ) {
    			if ( ! empty( $post_list ) ) {
    				$list .= "<h4>{$letter}</h4>";
    				foreach( $post_list as $name => $post ) {
    					$list .= '<div class="post"><span class="post-link"><a href="' . get_permalink( $post->ID ) . '">' . esc_html( $name ) . '</a></span></div>';
    				}
    			}
    		}
	
        	$list .= '</div>';
        	$list .= '<script>jQuery(function(){var u="' . $cols . '";jQuery(window).width()<992&&(u=2),jQuery(".columnized").columnize({columns:u})});</script>';
            
        	return $list;
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
            
            return $this->slider->create_post_slider( $posts, $atts );
        }
        
		/**
		 * Renders a bootstrap tab pane.
		 * 
		 * @param $attr
		 * @param $content
		 * @return string
		 */
		public function generate_tab_pane( $atts, $content = '' ) {
            
            wp_enqueue_style( 'msltns-shortcodes' );
            wp_enqueue_style( 'bootstrap' );
            wp_enqueue_script( 'bootstrap' );
		
			$defaults = [
				'id' => '',
            ];
			extract( shortcode_atts( $defaults, $atts ) );
			
			$this->tabs_data = [];
			
	        $replace      = [ ']<br />' => ']' ];
	        $content      = strtr( $content, $replace );
			$tabs_content = do_shortcode( $content );
			$class        = rawurldecode( sanitize_title( $id ) );
            
            $ul_class     = apply_filters( 'msltns_tab_pane_nav_class', 'text-center' );
            $li_class     = apply_filters( 'msltns_tab_pane_nav_link_class', '' );
            
            if ( $this->utils->starts_with( $tabs_content, '<br />' ) ) {
                $tabs_content = substr( $tabs_content, 6 );
            }
            
            ob_start();
			?>
			<div class="<?php echo esc_attr( $class ) ?>">
                <div class="msltns-tabs-container">
                    <ul class="nav nav-tabs <?php echo esc_attr( $ul_class ) ?>">
            
                        <?php foreach( $this->tabs_data as $index => $tab ) : ?>
                            <?php $active = ( $index === 0 ? true : false ); ?>
                            <li data-tab-id="<?php echo esc_attr( $tab['id'] ) ?>" class="nav-item <?php echo esc_attr( $li_class ) ?><?php if ( $active ) { echo ' active'; } ?>">
                                <a href="#<?php echo esc_attr( $tab['id'] ) ?>" class="nav-link" data-toggle="tab"><span class="tab-title"><?php echo esc_html( $tab['title'] ) ?></span></a>
    					    </li>
    					<?php endforeach; ?>
            
                    </ul>
                </div>
                <div class="tab-content"><?php echo $tabs_content ?></div>
            </div>
            <script>
                jQuery(function($) {
                    $(document).ready(function(){
                        $('.nav-tabs > .active > a').trigger('click');
                    });
                });
            </script>
			<?php
			
			$output = ob_get_contents();
			ob_end_clean();
			return $output;
		}
		
		/**
		 * Renders single tab content.
		 * 
		 * @param $attr
		 * @param $content
		 * @return string
		 */
		public function generate_single_tab( $atts, $content = '' ) {
		
			$defaults = [
                'title' => '',
            ];
			extract( shortcode_atts( $defaults, $atts ) );
			
			$id    = rawurldecode( sanitize_title( $title ) );
            $class = apply_filters( 'msltns_tab_pane_content_class', 'tab-pane fade half-internal-gutter single-block-padding in' );
			
			$this->tabs_data[] = [ 'id' => $id, 'title' => $title ];
			
			$active = count( $this->tabs_data ) == 1 ? ' active' : '';
            
			ob_start();
			?>
            <div id="<?php echo esc_attr( $id ) ?>" class="<?php echo esc_attr( $class ) ?><?php echo esc_attr( $active ) ?>">
                <?php echo do_shortcode( $content ) ?>
            </div>
            <?php
            
			$output = ob_get_contents();
			ob_end_clean();
			return $output;
		}
        
    }
    
}
