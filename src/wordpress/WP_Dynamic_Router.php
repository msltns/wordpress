<?php

namespace msltns\wordpress;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use msltns\wordpress\WP_Utils;

/**
 * Class WP_Dynamic_Router makes creating dynamic page or file urls in wordpress easy.
 *
 * @category 	Class
 * @package  	WP_Dynamic_Router
 * @author 		Daniel Muenter <info@msltns.com>
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
if ( ! class_exists( '\msltns\wordpress\WP_Dynamic_Router' ) ) {
    
    class WP_Dynamic_Router {
                
        protected static

            /**
             * Flag that determines if we are on a virtual page or not
             * @var bool
             */
            $is_virtual_page = false,

            /**
             * Array of custom routes
             * @var array
             */
            $custom_routes = [],

            /**
             * Array of custom created query vars
             * @var array
             */
            $custom_query_vars = [],

            /**
             * Stores the currently used route
             * @var array|false
             */
            $current_route = false;

        /**
         * Create new route to process our pages
         *
         * @param string $route Regular expression for the routing ^route$
         * for example will create /route/ page
         * @param string|null $type The route type
         * @param string|null $template The template name to use, null to automate
         * @param string $title The page title appearing in wp_title()
         * @param array $arguments Custom arguments to be parsed
         * @throws Exception Throws exception when given invalid data
         * @return void
         */
        public static function create( $route, $type = '', $template = '', $path = '', $title = '', $args = [] ) {
            
            // validate our route string
            if ( !is_string( $route ) ) {
                throw new \Exception( 'Argument #1 of WP_Dynamic_Router::create() must be a valid not empty string' );
            }
            
            if ( empty( $route ) ) {
                throw new \Exception( 'Argument #1 of WP_Dynamic_Router::create() must be a valid not empty string' );
            }
            
            // validate our template string
            if ( !is_string( $template ) ) {
                throw new \Exception( 'Argument #2 of WP_Dynamic_Router::create() must be a string referencing a WordPress template file' );
            }
            
            // validate our template string
            if ( !is_string( $path ) ) {
                throw new \Exception( 'Argument #3 of WP_Dynamic_Router::create() must be a string referencing a WordPress template file' );
            }

            // validate our route arguments
            if ( !is_array( $args ) ) {
                throw new \Exception( 'Argument #4 of WP_Dynamic_Router::create() must be an empty array or array of possible regex matches' );
            }
            
            // insure regex pattern is available
            $route = str_replace( [ '^', '$' ], '', $route );
            $route = "^{$route}$";
            
            if ( !is_string( $type ) || empty( $type ) ) {
                $type = 'page';
            }

            // add to our array of routes
            self::$custom_routes[$route] = array(
                'route'     => $route,
                'type'      => $type,
                'template'  => $template,
                'path'      => $path,
                'title'     => $title,
                'args'      => $args,
            );
        }


        /**
         * Static method to execute our rewrites
         * @return void
         */
        public static function handle() {

            // add rewrite action
            add_action( 'init', array( __CLASS__, 'action_rewrite' ) );

            // register our custom filters
            add_action( 'query_vars', array( __CLASS__, 'action_query_vars' ) );

            // determine how to handle our templates
            add_action( 'wp', array( __CLASS__, 'action_template_include' ) );
            
            // get the current WP_Dynamic_Router route being accessed.
            add_filter( 'msltns_current_route',        array( __CLASS__, 'get_current_route' ) );
            
            // obtain all arguments of the current route if existing
            add_filter( 'msltns_current_route_args',   array( __CLASS__, 'get_current_route_args' ) );
            
            // obtain a certain route argument if existing
            add_filter( 'msltns_current_route_arg',    array( __CLASS__, 'get_current_route_arg' ) );
            
        }
        
        
        /**
         * Method to obtain all custom routes.
         * 
         * @since 0.0.1
         * 
         * @return array
         */
        public static function get_custom_routes() {
            return self::$custom_routes;
        }
        
        
        /**
         * Alias for get_custom_routes().
         * 
         * @since 0.0.1
         * 
         * @return array
         */
        public static function get_registered_routes() {
            return self::get_custom_routes();
        }
        
        
        /**
         * Method to obtain all custom route uris.
         * 
         * @since 0.0.1
         * 
         * @return array
         */
        public static function get_registered_uris() {
            $uris = [];
            foreach( self::$custom_routes as $key => $route ) {
                if ( $route['type'] === 'page' ) {
                    $uris[] = '/' . str_replace( [ '^', '$' ], '', $route['route'] );
                }
            }
            return $uris;
        }

        
        /**
         * Check to see if we are on a virtual page or not.
         *
         * @param null|string $slug The string to compare against. Null
         * to simply check if it is a page or not
         * @return bool
         */
        public static function is_page( $slug = null ) {

            if ( is_null( $slug ) ) {
                return (bool) self::$is_virtual_page;
            } else {
                return (bool) ( self::$is_virtual_page === $slug );
            }

        }
        
        /**
         * Alias for is_page.
         * 
         * @since 0.0.1
         * 
         * @param null|string $slug The string to compare against. Null
         * to simply check if it is a page or not
         * @return bool
         */
        public static function is_dynamic_page( $slug = null ) {
            return self::is_page( $slug );
        }

        
        /**
         * Check to see if we are on the given route or not.
         * 
         * @since 0.0.1
         * 
         * @param string $route The string to compare against.
         * @return bool
         */
        public static function is_current_route( string $route ) {
            $aRoute = self::get_current_route();
            if ( !empty( $aRoute['route'] ) && !empty( $aRoute['type'] ) && $aRoute['type'] === 'page' ) {
                $uri = str_replace( [ '^', '$' ], '', $aRoute['route'] );
                return $uri === $route;
            }
            return false;
        }
        

        /**
         * Retrieves query variable from URL
         * (extension of WordPress get_query_var() method).
         *
         * @param string $field Query var to obtain
         * @param mixed $default The default value to return
         * @throws Exception Exception when invalid data is passed
         * @return mixed
         */
        public static function get_query_var( $field, $default = false ) {

            if ( is_string( $field ) ) {

                // return our query var
                if ( $return = get_query_var(sprintf( 'DR%s', $field ) ) ) {
                    return $return;
                }

                // see if we have a default argument
                if ( $default === FALSE && array_key_exists( $field, self::$current_route['args'] ) ) {
                    // set our new default
                    $default = self::$current_route['args'][$field];
                }

                // return our default
                return $default;


            } else {
                // throw exception when we haven't got a string
                throw new \Exception( 'WP_Dynamic_Router::get_query_var() requires the first argument to be a string' );
            }
        }


        /**
         * Gets the current WP_Dynamic_Router route being accessed.
         *
         * @param string|null $matched_rule rule that was matched (regex)
         * @return bool|string Gets the current route or false if route is invalid
         */
        public static function get_current_route( &$matched_rule = null ) {

            // get our wp global
            global $wp;

            // check if we are running a router rule
            if ( array_key_exists( $wp->matched_rule, self::$custom_routes ) ) {

                $matched_rule = $wp->matched_rule;

                // set our current route
                self::$current_route = self::$custom_routes[$wp->matched_rule];
            }

            // return our current route
            return self::$current_route;
        }

        
        /**
         * Obtains all arguments of the current route if existing.
         * 
         * @since 0.0.1
         * 
         * @param array     $args   The arguments array to filter.
         * @return array
         */
        public static function get_current_route_args( $args = [] ) {
            $route = self::get_current_route();
            if ( !empty( $route['args'] ) && is_array( $route['args'] ) ) {
                return $route['args'];
            }
            
            return [];
        }
        
        
        /**
         * Obtains a certain route argument if existing.
         * 
         * @since 0.0.1
         * 
         * @param string  $key     The argument key.
         * @return string
         */
        public static function get_current_route_arg( $key = '' ) {
            
            if ( empty( $key ) ) {
                return '';
            }
            
            $args = self::get_current_route_args();
            if ( is_array( $args ) && array_key_exists( $key, $args ) ) {
                return $args[$key];
            }
            
            return '';
        }
        
        
        /**
         * WP Action to create our rewrite rules.
         * 
         * @uses init action
         * @return void
         */
        public static function action_rewrite() {

            // create a rewrite endpoint
            add_rewrite_endpoint( 'router', EP_PERMALINK );

            // add rewrite tag
            add_rewrite_tag( '%WP_Dynamic_Router%', '([^&]+)' );

            // loop through our rewrites
            foreach( self::$custom_routes as $sRegex => $aRoute ) {

                $sRouteKey = md5( serialize( self::$custom_routes[$sRegex] ) );
                self::$custom_routes['key'] = $sRouteKey;

                // set our $sArgs to be null
                $sArgs = '?WP_Dynamic_Router=' . $sRegex;

                // check if we have any arguments
                if ( is_array( $aRoute['args'] ) && count( $aRoute['args'] ) ) {

                    // set our match number
                    $i = 0;

                    // loop through our arguments
                    foreach( $aRoute['args'] as $sArgument ) {
                        // add argument to our rewrite string
                        self::$custom_query_vars[] = sprintf( 'DR%s', esc_attr( $sArgument ) );
                        $sArgs.= sprintf( '&DR%s=$matches[%d]', esc_attr( $sArgument ), ++$i );
                    }
                }

                // create our rewrite rule for this route
                add_rewrite_rule( $sRegex, 'index.php' . $sArgs, 'top' );
            }
        }


        /**
         * WP Action to register our custom WordPress query vars.
         *
         * @uses query_vars action
         * @param array $vars Array of existing query vars
         * @return array Array of updated query vars
         */
        public static function action_query_vars( $vars ) {
            
            // get our route
            $aRoute = self::get_current_route();
            if ( is_array( $aRoute ) ) {
                
                $route  = str_replace( [ '^', '$' ], '', $aRoute['route'] );
            
                // handle certain route types directly
                if ( !empty( $aRoute['type'] ) ) {
                    if ( in_array( $aRoute['type'], [ 'js', 'script', 'javascript' ] ) ) {
                        do_action( 'msltns_dynamic_route_file', 'js', $route );
                        header( 'HTTP/1.1 404 Not found' );
                        exit;
                    }
                    else if ( in_array( $aRoute['type'], [ 'css', 'style', 'stylesheet' ] ) ) {
                        do_action( 'msltns_dynamic_route_file', 'css', $route );
                        header( 'HTTP/1.1 404 Not found' );
                        exit;
                    }
                    else if ( in_array( $aRoute['type'], [ 'img', 'image', 'picture' ] ) ) {
                        do_action( 'msltns_dynamic_route_file', 'img', $route );
                        header( 'HTTP/1.1 404 Not found' );
                        exit;
                    }
                    else if ( in_array( $aRoute['type'], [ 'font', 'fonts' ] ) ) {
                        do_action( 'msltns_dynamic_route_file', 'font', $route );
                        header( 'HTTP/1.1 404 Not found' );
                        exit;
                    }
                }

                // add our WP_Dynamic_Router variable
                $vars[] = 'WP_Dynamic_Router';

                // check we have a valid route with args
                if ( ! empty( $aRoute['args'] ) ) {
                    // loop through our arguments
                    foreach( $aRoute['args'] as $sVar ) {
                        $vars[] = 'DR' . $sVar;
                    }
                }
            }
            
            // return our updated vars
            return $vars;
        }


        /**
         * WP Action to determine the template to render.
         *
         * @uses template_include action
         * @param string $template The default template to render
         * @return string Returns template to render
         */
        public static function action_template_include( $wp ) {
            
            global $wp_query;

            // get our current route
            $aRoute = self::get_current_route();

            // check if we are running a router rule
            if ( $aRoute ) {

                // modify $wp_query to set page
                $wp_query->is_home = false;
                $wp_query->is_page = false;
                
                // set dynamic page title
                add_filter( 'wp_title', function( $title, $sep, $seplocation ) use ( $aRoute ) {
                    
                    // return our title if we have one
                    if ( !empty( $aRoute['title'] ) ) {
                        $title = $aRoute['title'];
                    }
                    
                    return $title;
                }, 10000, 3 );
                
                // set dynamic page title
                add_filter( 'document_title', function( $title ) use ( $aRoute ) {
                    
                    // return our title if we have one
                    if ( !empty( $aRoute['title'] ) ) {
                        $title = $aRoute['title'];
                    }
                    
                    return $title;
                }, 10000 );
                
                // add our body class filters
                add_filter( 'body_class', function( $classes ) use ( $wp, $aRoute ) {
                    
                    // set our title if we can
                    if ( !is_null( $aRoute['title'] ) ) {
                        $classes[] = 'router-' . sanitize_title( $aRoute['title'] );
                    }

                    // set our matched rule class
                    $classes[] = 'router-' . sanitize_title( $wp->matched_rule );

                    // set our generic router page class
                    $classes[] = 'router-page';

                    // return our classes
                    return $classes;
                } );

                // set virtual page flag
                self::$is_virtual_page = sanitize_title( $wp->matched_rule );
                
                if ( !is_404() ) {
                    
                    add_filter( 'the_content', function() use( $aRoute ) {
                        
                        $file_name = $aRoute['template'];
                        if ( locate_template( $file_name ) ) {
                            $template = locate_template( $file_name );
                        } else {
                            // Template not found in theme's folder, use plugin's template as a fallback
                            $template = trailingslashit( $aRoute['path'] ) . $file_name;
                        }
                        
                        ob_start();
            			include( $template );
            			$content = ob_get_contents();
            			ob_end_clean();
		
            			return $content;
                    }, 10000 );

                    add_filter( 'template_include', function( $template ) use ( $aRoute ) {
                        
                        $route = $aRoute['route'];
                        $route = str_replace( [ '^', '$' ], '', $route );
                        
                        /* @since 0.0.1 */
                        $custom_template = apply_filters( 'msltns_dynamic_route_page_template', basename( get_page_template() ), $route );
                        
                        if ( !empty( $custom_template ) ) {
                            
                            if ( strpos( $custom_template, '.php' ) === false ) {
                                $custom_template .= '.php';
                            }
                            
                            if ( locate_template( $custom_template ) ) {
                                $template = locate_template( $custom_template );
                            }
                        }
                        
                        if ( empty( $template ) ) {
                            $template = get_page_template();
                        }
                        
                        return $template;
                        
                    }, 10000 );
                    
                }
                
            }
            
        }
        
    }

}
