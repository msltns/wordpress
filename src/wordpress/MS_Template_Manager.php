<?php

namespace msltns\wordpress;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use msltns\wordpress\MS_Dynamic_Router;

/**
 * Class MS_Template_Manager makes using custom post templates in wordpress easy.
 *
 * @category 	Class
 * @package  	MS_Template_Manager
 * @author 		msltns <info@msltns.com>
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
if ( ! class_exists( '\msltns\wordpress\MS_Template_Manager' ) ) {
    
    class MS_Template_Manager {
        
        private
                    
            /**
        	 * Directory where own templates are located.
        	 */
            $templates_dir = '',
        
            /**
        	 * Array of templates that this class tracks.
        	 */
        	$templates = [],
        
            /**
        	 * Array of post types that this class tracks.
        	 */
            $post_types = [];
        
    	/**
    	 * Main constructor.
    	 */
        public function __construct() {
            
            add_filter( 'page_attributes_dropdown_pages_args',      array( $this, 'register_custom_templates' ) );
            add_filter( 'wp_insert_post_data',                      array( $this, 'register_custom_templates' ) );
            add_filter( 'template_include',                         array( $this, 'get_custom_template' ) );
            
        }
        
        /**
         * Adds custom templates to the pages cache in order to trick WordPress
         * into thinking the template file exists where it doens't really exist.
         *
         * @param  array    $args   The function arguments.
         * @return array
         */
        public function register_custom_templates( array $args ) {

            // Get theme object
            $theme = wp_get_theme();

            // Create the key used for the themes cache
            $cache_key = 'page_templates-' . md5( $theme->get_theme_root() . '/' . $theme->get_stylesheet() );

            // Retrieve existing page templates
            $templates = $theme->get_page_templates();

            // Add our template(s) to the list of existing templates by merging the arrays
            $templates = array_merge( $templates, $this->templates );

            // Replace existing value in cache
            wp_cache_set( $cache_key, $templates, 'themes', 1800 );

            add_filter( 'theme_page_templates', function( $page_templates ) use ( $templates ) {
                return $templates;
            });

            return $args;
        } 
    
        /**
    	 * Returns the related custom template if existing.
         * 
         * @param string    $template   The template path.
         * @return string
    	 */
    	public function get_custom_template( $template ) {
            global $post;
            
            if ( empty( $this->templates ) ) {
                return $template;
            }
            
            $post_type     = '';
            $template_file = false;

    		if ( $post ) {
                
                $post_type = $post->post_type;
                
                /* use as page template */
                if ( $post_type === 'page' ) {
                    
                    $page_template = get_post_meta( $post->ID, '_wp_page_template', true );
                    if ( array_key_exists( $page_template, $this->templates ) ) {
                        $template_file = $this->templates_dir . $page_template;
                        $template_file = str_replace( '.php', '', $template_file ) . '.php';
                    }
                }
                /* e.g. for use with custom post types */
                else if ( in_array( $post_type, $this->post_types ) ) {
                    $template_file = $this->templates_dir . "single-{$post_type}.php";
                }
                
    		}
            /* check if custom route is dynamic */
            else if ( MS_Dynamic_Router::is_dynamic_page() ) {
                
                $post_type     = 'dynamic_page';
                $page_template = apply_filters( 'msltns_dynamic_route_page_template', $template );
                $template_file = $this->templates_dir . $page_template;
            }
            
            if ( file_exists( $template_file ) ) {
    			$template = $template_file;
    		}
            
            return apply_filters( "msltns_single_{$post_type}_template", $template );
    	}
        
        /**
    	 * Gets the templates directory.
         * 
         * @return string
    	 */
        public function get_templates_dir() : string {
            return $this->templates_dir;
        }
        
        /**
    	 * Sets the templates directory.
         * 
         * @param string    $templates_dir   The templates directory.
         * @return void
    	 */
        public function set_templates_dir( string $templates_dir ) {
            $this->templates_dir = $templates_dir;
        }
        
        /**
    	 * Gets the templates.
         * 
         * @return array
    	 */
        public function get_templates() : array {
            return $this->templates;
        }
        
        /**
    	 * Sets the templates.
         * 
         * @param array    $templates   The templates.
         * @return void
    	 */
        public function set_templates( array $templates ) {
            $this->templates = $templates;
        }
        
        /**
    	 * Gets the post types.
         * 
         * @return array
    	 */
        public function get_post_types() : array {
            return $this->post_types;
        }
        
        /**
    	 * Sets the post types.
         * 
         * @param array    $post_types   The post types.
         * @return void
    	 */
        public function set_post_types( array $post_types ) {
            $this->post_types = $post_types;
        }
    }
}
