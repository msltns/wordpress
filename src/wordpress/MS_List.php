<?php

namespace msltns\wordpress;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use msltns\wordpress\MS_Element;

/**
 * Class MS_List adds some useful shortcodes.
 *
 * @category 	Class
 * @package  	MS_List
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
if ( ! class_exists( '\msltns\wordpress\MS_List' ) ) {
    
    class MS_List extends MS_Element {
        
        /**
    	 * Main constructor.
    	 */
        public function __construct() {
            parent::__construct();
            
            $this->routes = [
                'msltnsjs/columnizer' => [
                    'file'  => MSLTNS_ASSETS_DIR . '/js/columnizer.min.js',
                    'type'  => 'js',
                    'ctype' => 'application/javascript',
                ],
            ];
            
            $this->scripts = [
                'columnizerjs' => [
                    'action' => 'register',
                    'type'   => 'script',
                    'handle' => 'columnizer',
                    'src'    => trailingslashit( home_url() ) . 'msltnsjs/columnizer',
                    'deps'   => [ 'jquery' ],
                    'footer' => true,
                ],
            ];
            
            add_shortcode( 'msltns_post_list', array( $this, 'generate_post_list' ) );
            add_shortcode( 'ms_post_list', array( $this, 'generate_post_list' ) );
            
            add_shortcode( 'msltns_term_list', array( $this, 'generate_term_list' ) );
            add_shortcode( 'ms_term_list', array( $this, 'generate_term_list' ) );
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
        		'cols'      => 3,
                'class'     => '',
        	);
        	extract( shortcode_atts( $defaults, $atts ) );
            
            wp_enqueue_script( 'columnizer' );
    
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
            
            if ( empty( $posts ) ) {
                return '';
            }
            
            $class_name = ! empty( $class ) ? $class : 'ms-post-list';
            
            $list = '<div class="' . esc_attr( $class_name ) . ' columnized">';
	        
    		// alphabetical presentation
    		$sorted_posts = $this->utils->sort_posts_alphabetically( $posts );
    		foreach ( $sorted_posts as $letter => $post_list ) {
    			if ( ! empty( $post_list ) ) {
    				$list .= "<h4>{$letter}</h4>";
    				foreach ( $post_list as $name => $post ) {
    					$list .= '<div class="post"><span class="post-link"><a href="' . get_permalink( $post->ID ) . '">' . esc_html( $name ) . '</a></span></div>';
    				}
    			}
    		}
	
        	$list .= '</div>';
        	$list .= '<script>jQuery(function(){const n="' . $cols . '";jQuery(window).width()<992&&(n=2),jQuery(".columnized").columnize({columns:n,doneFunc:function(){$(".columnized > .column").css({width:100/n+"%"}),$(window).trigger("' . $post_type . 'ListColumnized"),$(window).trigger("columnizerFinished")}})});</script>';
            
        	return $list;
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
        		'cols'			=> 3,
                'class'         => '',
        	);
        	extract( shortcode_atts( $defaults, $atts ) );
	
            wp_enqueue_script( 'columnizer' );
    
        	$hierarchical = ( $hierarchical === 'true' ) ? true : false;
        	$hide_empty   = ( $hide_empty === 'true' ) ? true : false;
	
        	$terms = $this->utils->get_term_list( $post_type, $taxonomy, $limit, $hierarchical, $orderby, $order, $hide_empty );
            
            if ( empty( $terms ) ) {
                return '';
            }
            
            $class_name = ! empty( $class ) ? $class : 'ms-term-list';
            
            $list = '<div class="' . esc_attr( $class_name ) . ' columnized">';
	
        	if ( $hierarchical ) {
		
        		// hierarchical presentation
        		$sorted_terms = $this->utils->sort_terms_hierarchically( $terms );
        		foreach ( $sorted_terms as $id => $term ) {
        			$list .= '<h4><a href="' . $basic_url . $term->slug . '">' . esc_html( $term->name ) . '</a></h4>';
        			if ( ! empty( $term->children ) ) {
        				foreach ( $term->children as $i => $te ) {
        					$list .= '<div class="sub-term"><span class="sub-term-link"><a href="' . $basic_url . $te->slug . '">' . esc_html( $te->name ) . '</a></span></div>';
        					if ( ! empty( $te->children ) ) {
        						foreach ( $te->children as $j => $t ) {
        							$list .= '<div class="sub-sub-term"><span class="sub-sub-term-link"><a href="' . $basic_url . $t->slug . '">' . esc_html( $t->name ) . '</a></span></div>';
        						}
        					}
        				}
        			}
        		}
        	} else {
		
        		// alphabetical presentation
        		$sorted_terms = $this->utils->sort_terms_alphabetically( $terms );
        		foreach ( $sorted_terms as $letter => $term_list ) {
        			if ( ! empty( $term_list ) ) {
        				$list .= "<h4>{$letter}</h4>";
        				foreach ( $term_list as $name => $term ) {
        					$list .= '<div class="sub-term"><span class="sub-term-link"><a href="' . $basic_url . $term->slug . '">' . esc_html( $name ) . '</a></span></div>';
        				}
        			}
        		}
        	}

        	$list .= '</div>';
        	$list .= '<script>jQuery(function(){const n="' . $cols . '";jQuery(window).width()<992&&(n=2),jQuery(".columnized").columnize({columns:n,doneFunc:function(){$(".columnized > .column").css({width:100/n+"%"}),$(window).trigger("termListColumnized"),$(window).trigger("columnizerFinished")}})});</script>';
            
        	return $list;
        }
    }
}
