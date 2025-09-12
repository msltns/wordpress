<?php

namespace msltns\wordpress;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use msltns\utilities\Utils;
use msltns\wordpress\MS_Logstream;

/**
 * Class MS_Utils provides some useful WordPress functions.
 *
 * @category 	Class
 * @package  	MS_Utils
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
if ( ! class_exists( '\msltns\wordpress\MS_Utils' ) ) {
	
    if ( !defined( 'UTILS_VERSION' ) ) {
        define( 'UTILS_VERSION', '0.0.1' );
    }
    
	class MS_Utils extends Utils {
		
		private $parent;
		
		/**
		 * @var \MS_Utils
		 */
		private static $instance;
		
		/**
		 * Main constructor.
		 *
		 * @return void
		 */
		private function __construct() {
			$this->parent = parent::getInstance();
		}
		
		/**
		 * Singleton instance.
		 * 
         * @return \MS_Utils
		 */
		public static function instance() {
            return self::getInstance();
		}
        
		/**
		 * Singleton instance.
		 * 
		 * @return \MS_Utils
		 */
		public static function get_instance() {
            return self::getInstance();
		}
		
		/**
		 * Singleton instance.
		 * 
		 * @return \MS_Utils
		 */
		public static function getInstance() {
            if ( !isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
        
		/**
		 * Initialization method triggered by WordPress' init hook.
		 * 
         * @return void
		 */
    	public function init() {
            // Only need to do this for versions less than current version
    		$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
    		if ( ! $doing_ajax && ! defined( 'IFRAME_REQUEST' ) 
    			&& version_compare( get_option( 'msltns_utils_version', 0 ), UTILS_VERSION, '<' ) 
    		) {
                $this->setup_db_for_msltns_transients();
                update_option( 'msltns_utils_version', UTILS_VERSION );
    		}
            
        	add_action( 'msltns_cleanup_transients', function() {
                MS_Utils::instance()->cleanup_msltns_transients();
        	} );
            
        	if ( ! wp_next_scheduled( 'msltns_cleanup_transients' ) ) {
        	    wp_schedule_event( time(), 'hourly', 'msltns_cleanup_transients' );
        	}
    	}
		
		/**
		 * Obtains the country iso code for the current user.
		 * 
		 * @return string
		 */
    	public function get_country_iso_code() {
            $isoCode = '';
            
            $user_id = get_current_user_id();
            if ( intval( $user_id ) > 0 ) {
                $isoCode = get_user_meta( $user_id, 'country', true );
            }
            
    		if ( empty( $isoCode ) ) {
                if ( function_exists( 'geoip_detect2_get_info_from_current_ip' ) ) {
                    $geoinfo = geoip_detect2_get_info_from_current_ip( NULL );
                    if ( isset( $geoinfo->country->isoCode ) ) {
                        $isoCode = $geoinfo->country->isoCode;
                    }
                } else {
                    $user_ip = $this->get_user_ip();
                    if ( !empty( $user_ip ) ) {
            			$isoCode = $this->get_country_code_by_ip( $user_ip );
                    }
                }                
    		}
            
            if ( !empty( $isoCode ) && intval( $user_id ) > 0 ) {
                update_user_meta( $user_id, 'country', $isoCode );
            }
            
            return $isoCode;
        }
        
		/**
		 * Obtains the country iso code for the current user.
		 * 
		 * @return string
		 */
    	public function get_users_country_iso_code() {
            return $this->get_country_iso_code();
        }
        
		/**
		 * Obtains the timezone for the current user.
		 * 
         * @return string
		 */
    	public function get_timezone() {
            $timezone = '';
            
            $user_id = get_current_user_id();
            if ( intval( $user_id ) > 0 ) {
                $timezone = get_user_meta( $user_id, 'timezone', true );
            }
            
    		if ( empty( $timezone ) ) {
                if ( function_exists( 'geoip_detect2_get_info_from_current_ip' ) ) {
                    $geoinfo = geoip_detect2_get_info_from_current_ip( NULL );
                    if ( isset( $geoinfo->country->timezone ) ) {
                        $timezone = $geoinfo->country->timezone;
                    }
                } else {
                    $user_ip = $this->get_user_ip();
                    if ( !empty( $user_ip ) ) {
            			$timezone = $this->get_timezone_by_ip( $user_ip );
                    }
                }                
    		}
            
            if ( !empty( $timezone ) && intval( $user_id ) > 0 ) {
                update_user_meta( $user_id, 'timezone', $timezone );
            }
            
            return $timezone;
        }
        
		/**
		 * Obtains the timezone for the current user.
		 * 
         * @return string
		 */
    	public function get_users_timezone() {
            return $this->get_timezone();
        }
        
        /**
		 * Get a list of all countries.
		 *
		 * @return array
		 */
        public function get_country_list() {
        	$data = [];
        	// $countries is defined in include file
            $countries = include( __DIR__ . '/../data/countries.php' );
        	foreach ( $countries as $key => $value ) {
        		$data[] = [
        			'name' => $value,
        			'code' => $key
        		];
        	}
	
        	return $data;
        }
        
		/**
		 * Get current blog ID.
		 * 
		 * @return  int
		 */
        public function get_current_blog_id() {
            return get_current_blog_id();
        }

		/**
		 * Get current blog.
		 * 
		 * @return  string
		 */
        public function get_current_blog() {
            $blog = '';
            
            if ( is_multisite() ) {
                
                $subdinst = get_site_meta( 1, 'subdomain_install', true );
                if ( absint( $subdinst ) === 1 ) {
                    // subdomain installation
                    $parts = $this->parse_url( home_url() );
                    $blog  = $parts['subdomain'];
                }
                else {
                    // path installation
                    $db      = $this->get_db();
                    $blog_id = $this->get_current_blog_id();
                    $sql     = "SELECT `path` FROM `{$db->base_prefix}blogs` WHERE `blog_id` = %d;";
                    $path    = $db->get_var( $db->prepare( $sql, $blog_id ) );
                    $blog    = str_replace( '/', '', $path );
                }
            }
            
            return $blog;
        }

		/**
		 * Get requested URI.
		 * 
		 * @return  string
		 */
        public function get_current_uri() {
            return isset( $_SERVER['REQUEST_URI'] ) ? untrailingslashit( sanitize_text_field( $_SERVER['REQUEST_URI'] ) ) : '';
        }
        
		/**
		 * Get WordPress plugin directory.
		 *
		 * @param   string 	$subdir
		 * @return  string|bool
		 */
		public function get_wp_plugin_upload_dir( $subdir = '' ) {
			if ( $this->is_wp_env() ) {
				$wp_upload_dir = wp_upload_dir();
				$directory = $wp_upload_dir['basedir'] . $subdir;
				
				return $directory;
			}
			
			return false;
		}
		
		/**
		 * Check if a plugin is activated.
		 *
		 * @param   string 	$plugin
		 * @return  bool
		 */
		public function plugin_is_active( $plugin = '' ) {
			if ( empty( $plugin ) ) {
				return false;
			}
		    if ( !function_exists( 'is_plugin_active' ) ) {
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );	
			}
		    return ( is_plugin_active( $plugin ) );
		}
		
		/**
		 * Removes a class filter hook in WordPress environments.
		 *
		 * @param   string	$filter_name
		 * @param 	string 	$class_name
		 * @param 	string	$method_name
		 * @param 	int		$priority
		 * @return  bool
		 * @see 	https://developer.wordpress.org/plugins/hooks/
		 */
		public function remove_wp_class_filter( $filter_name, $class_name = '', $method_name = '', $priority = 10 ) {
			
			if ( ! $this->is_wp_env() ) {
				return false;
			}
			
	        global $wp_filter;
	        // Check that filter actually exists first
	        if ( ! isset( $wp_filter[ $filter_name ] ) ) {
	            return false;
	        }
	        /**
	         * If filter config is an object, means we're using WordPress 4.7+ and the config is no longer
	         * a simple array, rather it is an object that implements the ArrayAccess interface.
	         *
	         * To be backwards compatible, we set $callbacks equal to the correct array as a reference (so $wp_filter is updated)
	         *
	         * @see https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/
	         */
	        if ( is_object( $wp_filter[ $filter_name ] ) && isset( $wp_filter[ $filter_name ]->callbacks ) ) {
	            // Create $fob object from filter tag, to use below
	            $fob       = $wp_filter[ $filter_name ];
	            $callbacks = &$wp_filter[ $filter_name ]->callbacks;
	        } else {
	            $callbacks = &$wp_filter[ $filter_name ];
	        }
	        // Exit if there aren't any callbacks for specified priority
	        if ( ! isset( $callbacks[ $priority ] ) || empty( $callbacks[ $priority ] ) ) {
	            return false;
	        }
	        // Loop through each filter for the specified priority, looking for our class & method
	        foreach ( (array) $callbacks[ $priority ] as $filter_id => $filter ) {
	            // Filter should always be an array - array( $this, 'method' ), if not goto next
	            if ( ! isset( $filter['function'] ) || ! is_array( $filter['function'] ) ) {
	                continue;
	            }
	            // If first value in array is not an object, it can't be a class
	            if ( ! is_object( $filter['function'][0] ) ) {
	                continue;
	            }
	            // Method doesn't match the one we're looking for, goto next
	            if ( $filter['function'][1] !== $method_name ) {
	                continue;
	            }
	            // Method matched, now let's check the Class
	            if ( get_class( $filter['function'][0] ) === $class_name ) {
	                // WordPress 4.7+ use core remove_filter() since we found the class object
	                if ( isset( $fob ) ) {
	                    // Handles removing filter, reseting callback priority keys mid-iteration, etc.
	                    $fob->remove_filter( $filter_name, $filter['function'], $priority );
	                } else {
	                    // Use legacy removal process (pre 4.7)
	                    unset( $callbacks[ $priority ][ $filter_id ] );
	                    // and if it was the only filter in that priority, unset that priority
	                    if ( empty( $callbacks[ $priority ] ) ) {
	                        unset( $callbacks[ $priority ] );
	                    }
	                    // and if the only filter for that tag, set the tag to an empty array
	                    if ( empty( $callbacks ) ) {
	                        $callbacks = array();
	                    }
	                    // Remove this filter from merged_filters, which specifies if filters have been sorted
	                    unset( $GLOBALS['merged_filters'][ $filter_name ] );
	                }
	                return true;
	            }
	        }
			
	        return false;
	    }
		
		/**
		 * Removes a class action hook in WordPress environments.
		 *
		 * @param   string	$action_name
		 * @param 	string 	$class_name
		 * @param 	string	$method_name
		 * @param 	int		$priority
		 * @return  bool
		 * @see 	https://developer.wordpress.org/plugins/hooks/
		 */
	    public function remove_wp_class_action( $action_name, $class_name = '', $method_name = '', $priority = 10 ) {
	        return $this->remove_wp_class_filter( $action_name, $class_name, $method_name, $priority );
	    }
		
		/**
		 * Extracts a certain option from plugin options in WordPress environments.
		 *
		 * @param   array	$data
		 * @param 	string 	$key
		 * @return  mixed|string
		 */
		public function extract_wp_option( $data, $key ) {
			if ( ! $this->is_wp_env() ) {
				return false;
			}
			
			return trim( ( isset( $data[$key] ) && isset( $data[$key][0] ) && !empty( $data[$key][0] ) ) ? $data[$key][0] : '' );
		}
		
		/**
		 * Renders an input field.
         * 
		 * @param   array	$args
		 * @return  void
		 */
    	public function render_input( $args ) {
  
    		$option = get_option( $args['container'] );
            
            if ( ! isset( $args['class'] ) ) {
                $args['class'] = '';
            }
        
            if ( $args['type'] === 'checkbox' ) {
    			echo '<input type="hidden" name="' . $args['container'] . '[' . $args['name'] .']" value="0">';
    			echo '<input type="' . $args['type'] . '" id="' . $args['name'] . '" name="' . $args['container'] . '[' . $args['name'] . ']" ';
    			echo 'value="1" ';
                if ( $this->get_value( $option, $args ) === "1" ) {
    				echo 'checked="checked />"';
    			} else {
    				echo "/>";
    			}
    		}
    		else if ( $args['type'] === 'textarea' ) {
    			echo '<textarea id="' . $args['name'] . '" class="' . $args['class'] . '" name="' . $args['container'] . '[' . $args['name'] . ']" ';
                echo 'rows="' . $args['rows'] . '" cols="' . $args['cols'] . '"';
                if ( ! empty( $args['placeholder'] ) ) {
    				echo ' placeholder="' . $args['placeholder'] . '"';
    			}
                echo '>';
                echo $this->get_value( $option, $args );
                echo '</textarea>';
    		}
    		else if ( $args['type'] === 'select' ) {
    			$values = $this->get_value( $option, $args );
    			echo '<select id="' . $args['name'] . '" name="' . $args['container'] . '[' . $args['name'] . '][]" ';
                if ( ! empty( $args['multiple'] ) ) {
                    echo 'multiple';
                }
                echo '>';
    			foreach ( $args['options'] as $key => $value ) {
    				$selected = is_array( $values ) && in_array( $value, $values ) ? ' selected="selected"' : '';
    		        echo '<option value="' . esc_attr( $value ) . '"' . $selected . '>' . esc_html( $key ) . '</option>';
    			}
    			echo '</select>';
    		}
            else if ( $args['type'] === 'number' ) {
    			echo '<input type="number" step="0.01" id="'. $args['name'] .'" name="'.$args['container'] . '[' . $args['name'] .']" ';
                echo 'value="' . $this->get_value( $option, $args ) . '"';
    			if ( ! empty( $args['placeholder'] ) ) {
    				echo ' placeholder="' . $args['placeholder'] . '"';
    			}
		        echo ' />';
    		}
			else if ( $args['type'] === 'hidden' ) {
				echo '<input type="hidden" id="' . $args['name'] . '" class="' . $args['class'] . '" name="' . $args['name'] . '" value="1" />';
			}
    		else if ( $args['type'] === 'link' ) {
    			echo '<a href="' . $args['url'] . '" class="' . $args['class'] . '">' . $args['label'] . '</a>';
    		}
            else if ( $args['type'] === 'colorpicker' ) {
    		    echo '<input type="text" id="'. $args['name'] .'" name="'.$args['container'] . '[' . $args['name'] .']" ';
    			echo 'value="' . $this->get_value( $option, $args ) . '"';
    			if ( ! empty( $args['placeholder'] ) ) {
    				echo ' placeholder="' . $args['placeholder'] . '"';
    			}
    			echo ' class="msltns_color_picker" data-default-color="" />';
    		}
            else {
    			echo '<input type="'. $args['type'] .'" id="'. $args['name'] .'" class="' . $args['class'] . '" name="'.$args['container'] . '[' . $args['name'] .']" ';
    			echo 'value="' . $this->get_value( $option, $args ) . '"';
    			if ( ! empty( $args['placeholder'] ) ) {
    				echo ' placeholder="' . $args['placeholder'] . '"';
    			}
    			if ( ! empty( $args['size'] ) ) {
    				echo ' size="' . $args['size'] . '"';
    			}
    			echo ' />';
    		}
    		if ( ! empty( $args['description'] ) ) {
    			echo '<legend>' . $args['description'] . '</legend>';
    		}
    	}
    
		/**
         * Returns a certain value from options array.
         * 
		 * @return mixed
		 */
    	private function get_value( $option, $args ) {
    		return !empty( $option[$args['name']] ) ? $option[$args['name']] : '';
    	}
	    
		/**
         * Checks whether a certain value is available.
         * 
		 * @return bool
		 */
    	private function has_value( $option, $args, $value ) {
    		if ( !empty( $option[$args['name']] ) && is_array( $option[$args['name']] ) ) {
    			return in_array( $value, $option[$args['name']] );
    		}
    		return false;
    	}
        
		/**
         * Displays all stored admin notices in WordPress environments.
		 *
		 * @return void
		 */
        public function display_admin_notices() {
            $notices = $this->get_admin_notices();
            foreach ( $notices as $key => $notice ) {
                $type        = $notice['type'];
                $message     = base64_decode( $notice['message'] );
                // $message     = $this->unesc_string( $message );
                $dismissible = $notice['dismissible'] ? ' is-dismissible' : '';
                $out = "<div class='notice notice-{$type}{$dismissible}'><p>{$message}</p></div>";
                echo $out;
            }
            $this->set_admin_notices( [] );
        }
        
		/**
         * Adds an admin notice in WordPress environments.
		 *
		 * @param 	string 	$message
		 * @param   string	$level
		 * @param 	bool	$dismissible
		 * @return  void|bool
		 */
        public function add_admin_notice( $message, $level = 'info', $dismissible = true ) {
            // $message        = $this->esc_string( $message );
            $message        = base64_encode( $message );
            $notices        = $this->get_admin_notices();
            $key            = md5( $message );
            $notices[$key]  = [ 'type' => $level, 'message' => $message, 'dismissible' => $dismissible ];
            $this->set_admin_notices( $notices );
        }
        
		/**
         * Retrieves all admin notices from database.
		 *
		 * @return array
		 */
        private function get_admin_notices() {
            $notices = $this->get_transient( 'msltns_admin_notices' );
            if ( is_string( $notices ) ) {
                $notices = maybe_unserialize( $notices );
            }
            if ( !is_array( $notices ) ) {
                $notices = [];
            }
    
            return $notices;
        }
        
		/**
         * Stores admin notices in database.
		 *
		 * @param 	array 	$notices
		 * @return  void
		 */
        private function set_admin_notices( $notices ) {
            $expiration = strtotime( date( DATE_ATOM, strtotime( '+ 3 HOUR' ) ) ) - time();
            $this->set_transient( 'msltns_admin_notices', $notices, $expiration );
        }
        
		/**
         * Obtains the privacy page url from wordpress options.
		 *
		 * @return string|bool
		 */
        public function get_privacy_page_url() {
        	$page_id = get_option( 'wp_page_for_privacy_policy', false );
        	if ( $page_id ) {
        		return get_permalink( $page_id );
        	}
	
        	return false;
        }
        
		/**
		 * Retrieves the translation of $string in WordPress environments.
		 *
		 * @param   string	$type
		 * @param 	string 	$text_domain
		 * @return  bool|string
		 */
		public function translate( $string = '' ) {
			
			if ( empty( $string ) ) return $string;
			
            $textdomain = apply_filters( 'msltns_textdomain', 'msltns' );
            
            if ( empty( $textdomain ) ) return $string;
            
			return $this->wp_translate( $string, $textdomain );
		}
        
		/**
		 * Retrieves the translation of $string in WordPress environments.
		 *
		 * @param   string	$type
		 * @param 	string 	$textdomain
		 * @return  bool|string
		 */
		public function wp_translate( $string = '', $textdomain = '' ) {
			
			if ( empty( $string ) || empty( $textdomain ) ) return $string;
			
			if ( function_exists('__') && !empty( $textdomain ) ) {
				return __( $string, $textdomain );
			}
			
			return $string;
		}
		
		/**
		 * Get request referer.
		 * 
		 * @return string|bool
		 */
		public function get_referer() {
			
			$ref = $this->parent->get_referer();
			
			if ( empty( $ref ) ) {
				if ( !empty( $_REQUEST['_wp_http_referer'] ) ) {
					$ref = $_REQUEST['_wp_http_referer'];
				}
				else if ( $referer = wp_get_referer() ) {
					$ref = $referer;
				}
			}
			
			return $ref;
		}
		
		/**
		 * Redirects to another page in WordPress environments.
		 *
		 * @param   string	$location
		 * @param 	int 	$status
		 * @return  bool|void
		 */
		public function wp_redirect( $location, $status = 302 ) {
			
			if ( ! $this->is_wp_env() ) {
				return false;
			}
			
			if ( ! headers_sent() ) {
				wp_redirect( $location, $status );
				exit();
			} else {
				die( '<script type="text/javascript">'
				     . 'document.location = "' . str_replace( '&amp;', '&', esc_js( $location ) ) . '";'
				     . '</script>' );
			}
		}
		
		/**
		 * Generate password.
		 *
		 * @param int     $length
		 * @param bool    $special_chars
		 * @param bool    $extra_special_chars
		 * @return string
		 */
		public function generate_password( $length = 12, $special_chars = true, $extra_special_chars = true ) {
			return $this->generate_random_password( $length, $special_chars, $extra_special_chars );
		}
        
		/**
		 * Generates a random password.
		 *
		 * @param int $length The length of password to generate.
		 * @param bool $special_chars Whether to include standard special characters.
		 * @param bool $extra_special_chars Whether to include other special characters.
		 * @return string The generated password.
		 */
		public function generate_random_password( $length = 12, $special_chars = true, $extra_special_chars = true ) {
			return $this->check_password( '', $length, $special_chars, $extra_special_chars );
		}
        
		/**
		 * Filters a generated password and corrects it if necessary.
		 *
		 * @param string $password The generated password.
		 * @param int $length The length of password to generate.
		 * @param bool $special_chars Whether to include standard special characters.
		 * @param bool $extra_special_chars Whether to include other special characters.
		 * @return string The (corrected) password.
		 */
		public function check_password( $password = '', $length = 12, $special_chars = false, $extra_special_chars = false ) {
			
            $length = apply_filters( 'msltns_password_minimum_length', $length );
            
			if ( empty( $password ) ) {
				$password = wp_generate_password( $length, $special_chars, $extra_special_chars );
			}
			
			if ( ! preg_match( '/[a-z]+/', $password ) ) {
				$random_position = rand( 0, strlen( $password ) -1 );
				$chars 			 = "qwertyuiopasdfghjklzxcvbnm";
				$random_char 	 = $chars[ rand( 0, strlen( $chars ) -1 ) ];
				$password 		 = substr( $password, 0, $random_position ) . $random_char . substr( $password, $random_position );
			}
			
			if ( ! preg_match( '/[A-Z]+/', $password ) ) {
				$random_position = rand( 0, strlen( $password ) -1 );
				$chars 			 = "QWERTYUIOPASDFGHJKLZXCVBNM";
				$random_char 	 = $chars[ rand( 0, strlen( $chars ) -1 ) ];
				$password 		 = substr( $password, 0, $random_position ) . $random_char . substr( $password, $random_position );
			}
			
			// check password contains special characters @#/\*+-_.'§$%&^?<>:;,()!
			if ( ! preg_match( '/[\@#\\\\\/*+-\_\.\'§\$%\&\^\?<>\:\;\,\(\)\!]+/', $password ) ) {
				$random_position = rand( 0, strlen( $password ) -1 );
				$chars 			 = "@#*+-_.§$%&^?<>:;,!";
				$random_char 	 = $chars[ rand( 0, strlen( $chars ) -1 ) ];
				$password 		 = substr( $password, 0, $random_position ) . $random_char . substr( $password, $random_position );
			}
			
			if ( ! preg_match( '/[0-9]+/', $password ) ) {
				$random_position = rand( 0, strlen( $password ) -1 );
				$chars 			 = "0123456789";
				$random_char 	 = $chars[ rand( 0, strlen( $chars ) -1 ) ];
				$password 		 = substr( $password, 0, $random_position ) . $random_char . substr( $password, $random_position );
			}
			
			if ( strlen( $password ) < $length ) {
				for ( $i = $length; $i <= $length; $i++ ) {
					$random_position = rand( 0, strlen( $password ) -1 );
					$chars 			 = "qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM0123456789-_";
					$random_char 	 = $chars[ rand( 0, strlen( $chars ) -1 ) ];
					$password 		 = substr( $password, 0, $random_position ) . $random_char . substr( $password, $random_position );
				}
			}
			
			return $password;
		}
        
		/**
		 * Validates a given password.
		 * 
		 * Password Requirements:
		 * 
		 * 	at least 8 characters
		 * 	at least 1 lower case character
		 * 	at least 1 upper case character
		 * 	at least 1 special character
		 * 	at least 1 numeric character
		 *
		 * @param 	string $password	The password to validate.
		 * @return 	bool|MS_Error 		The validation result.
		 */
		public function validate_password( string $password = '' ) {
			
			$error = new \MS_Error();
			
			// check if password is empty
			if ( empty( $password ) ) {
				$error->add( 'empty_password', 'Password must not be empty.' );
			}
            
            // check password length
			$length = apply_filters( 'msltns_password_minimum_length', 12 );
            if ( strlen( $password ) < $length ) {
				$error->add( 'password_too_short', 'Password is too short. Please enter at least ' . $length . ' characters' );
			}
			
			// check password contains lowercase characters
			if ( ! preg_match( '/[a-z]+/', $password ) ) {
				$error->add( 'password_without_lowercase_chars', 'Password must contain at least one lowercase character.' );
			}
			
			// check password contains uppercase characters
			if ( ! preg_match( '/[A-Z]+/', $password ) ) {
				$error->add( 'password_without_uppercase_chars', 'Password must contain at least one uppercase character.' );
			}
			
			// check password contains special characters @#/\*+-_.'§$%&^?<>:;,()!
			if ( ! preg_match( '/[\@#\\\\\/*+-\_\.\'§\$%\&\^\?<>\:\;\,\(\)\!]+/', $password ) ) {
				$error->add( 'password_without_special_chars', 'Password must contain at least one special character.' );
			}
			
			// check password contains numeric characters
			if ( ! preg_match( '/[0-9]+/', $password ) ) {
				$error->add( 'password_without_numeric_chars', 'Password must contain at least one numeric character.' );
			}
			
			// add further checks here if required
			
			if ( $error->has_errors() ) {
				$this->log( 'Password validation failed: ' . implode( ' ', $error->get_error_messages() ) );
				return new \MS_Error( 'password_does_not_meet_security_requirements', 'The password does not meet our security requirements. Please make sure that your password is at least 8 characters long, contains at least one lowercase and one uppercase letter each, and at least one of the following special characters @#/\*+-_.\'§$%&^?<>:;,()!.' );
			}
			
			return true;
		}
        
        /**
         * Debug Pending Updates
         * Displays hidden plugin and theme updates on update-core screen.
         */
        public function debug_pending_updates() {

        	// Rough safety nets
        	if ( ! is_user_logged_in() || ! current_user_can( 'update_plugins' ) || ! current_user_can( 'update_themes' ) ) return;

        	$output = "";

        	// Check plugins
        	$plugin_updates = get_site_transient( 'update_plugins' );
        	if ( $plugin_updates && ! empty( $plugin_updates->response ) ) {
        		foreach ( $plugin_updates->response as $plugin => $details ) {
        			$output .= "<p><strong>Plugin</strong> <u>$plugin</u> is reporting an available update.</p>";
        		}
        	}

        	// Check themes
        	wp_update_themes();
        	$theme_updates = get_site_transient( 'update_themes' );
        	if ( $theme_updates && ! empty( $theme_updates->response ) ) {
        		foreach ( $theme_updates->response as $theme => $details ) {
        			$output .= "<p><strong>Theme</strong> <u>$theme</u> is reporting an available update.</p>";
        		}
        	}

        	if ( empty( $output ) ) $output = "No pending updates found in the database.";

        	echo "<h2>Pending updates</h2>" . $output;
        }
        
        /**
         * Registers a new custom post type with or without related category.
         * 
         * @param string	$singular		The singular name of the custom post type.
         * @param string	$plural			The plural name of the custom post type.
         * @param string	$slug			The slug of the new custom post type.
         * @param bool		$append_post_id	If true, the post id will be appended to url.
         * @param string	$menu_icon		The icon to be displayed next to the menu item label.
         * @param bool		$add_category	If true, a new related taxonomy will be added.
         * @param bool		$has_archive	If true, an archive page will be generated.
         * @param bool		$show_in_rest	If true, the post type is queryable via REST-API.
         * @return void
         */
        public function register_custom_post_type( $singular, $plural, $slug, $append_post_id = true, $menu_icon = '', $add_category = false, $has_archive = true, $show_in_rest = true ) {
	        register_post_type( 
        		$slug, 
        		[
        			'labels' => [
        				'name'              => $plural,
        				'singular_name' 	=> $singular,
        				'menu_name'         => ucwords( $plural ),
        				'search_items'      => sprintf( $this->translate( 'Search %s' ), $plural ),
        				'all_items'         => sprintf( $this->translate( 'All %s' ), $plural ),
        				'parent_item'       => sprintf( $this->translate( 'Parent %s' ), $singular ),
        				'parent_item_colon' => sprintf( $this->translate( 'Parent %s:' ), $singular ),
        				'edit_item'         => sprintf( $this->translate( 'Edit %s' ), $singular ),
        				'update_item'       => sprintf( $this->translate( 'Update %s' ), $singular ),
        				'add_new_item'      => sprintf( $this->translate( 'Add New %s' ), $singular ),
        				'new_item_name'     => sprintf( $this->translate( 'New %s Name' ),  $singular )
        			],
        			'public' 		=> true,
        			'has_archive' 	=> $has_archive,
        			'rewrite' 		=> [ "slug" => $slug . ( $append_post_id ? '/%post_id%' : '' ) ],
        			'show_in_rest' 	=> true,
        			'menu_icon' 	=> $menu_icon
        		],
        	);
	
        	if ( $add_category ) {
		
        		// Register taxonomy
        		$cat_singular = $this->translate( 'Category' );
        		$cat_plural   = $this->translate( 'Categories' );
                $textdomain   = apply_filters( 'msltns_textdomain', 'msltns' );

        		register_taxonomy( "{$slug}_category",
        			[ $slug ],
        			[
        				'hierarchical'          => true,
        				'label'                 => $cat_plural,
        				'labels'                => [
        					'name'              => sprintf( '%s %s', $singular, $cat_plural ),
        					'singular_name'     => $cat_singular,
        					'menu_name'         => ucwords( $cat_plural ),
        					'search_items'      => sprintf( $this->translate( 'Search %s %s' ), $singular, $cat_plural ),
        					'all_items'         => sprintf( $this->translate( 'All %s %s' ), $singular, $cat_plural ),
        					'parent_item'       => sprintf( $this->translate( 'Parent %s' ), $cat_singular ),
        					'parent_item_colon' => sprintf( $this->translate( 'Parent %s:' ), $cat_singular ),
        					'edit_item'         => sprintf( $this->translate( 'Edit %s %s' ), $singular, $cat_singular ),
        					'update_item'       => sprintf( $this->translate( 'Update %s %s' ), $singular, $cat_singular ),
        					'add_new_item'      => sprintf( $this->translate( 'Add New %s %s' ), $singular, $cat_singular ),
        					'new_item_name'     => sprintf( $this->translate( 'New %s Name' ),  $cat_singular )
        				],
        				'show_ui'           => true,
        				'show_tagcloud'     => false,
        				'public'            => true,
        				'rewrite'           => [
        					'slug'          => _x( "{$slug}-category", 'The category slug - resave permalinks after changing this', $textdomain ),
        					'with_front'    => false,
        					'hierarchical'  => false
        				],
        			]
        		);
        	}
        }
        
		/**
		 * Obtains a list of terms for the given post type and taxonomy.
		 *
		 * @param string $post_type     The post type.
		 * @param string $taxonomy      The taxonomy.
		 * @param int    $limit         The limit of items.
		 * @param bool   $hierarchical  If hierarchical or not.
		 * @param string $orderby       The order criteria.
		 * @param string $order         The order direction.
		 * @param bool   $hide_empty    If hide empty.
		 * @return array The terms list.
		 */
    	public function get_term_list( $post_type = 'post', $taxonomy = 'category', $limit = -1, $hierarchical = false, $orderby = 'count', $order = 'DESC', $hide_empty = true ) {
    		$terms = get_terms( $taxonomy, [
    		    'post_type'		=> $post_type,
    		    'orderby'		=> $orderby,
    		    'order'			=> $order,
    			'hide_empty'	=> $hide_empty,
    			'hierarchical'	=> $hierarchical,
    		] );
    		if ( $limit > 0 ) {
    			$terms = array_slice( $terms, 0, $limit );
    		}
            
    		return $terms;
    	}
        
    	/**
    	 * Recursively sort an array of taxonomy terms hierarchically. Child 
         * categories will be placed under a 'children' member of their parent term.
         *
    	 * @param array $cats    The taxonomy term objects to sort.
    	 * @param int $parent_id The current parent ID to put them in.
		 * @return array The sorted terms list.
    	 */
    	public function sort_terms_hierarchically( array $cats, $parent_id = 0 ) {
    	    $into = [];
    	    foreach ( $cats as $i => $cat ) {
    	        if ( $cat->parent === $parent_id ) {
    	            $cat->children = $this->sort_terms_hierarchically( $cats, $cat->term_id );
    	            $into[ $cat->term_id ] = $cat;
    	        }
    	    }
            
    	    return $into;
    	}
	
    	/**
    	 * Sort an array of taxonomy terms alphabetically. Categories will be
    	 * placed under the start letter of each term.
         *
    	 * @param array $cats   The taxonomy term objects to sort.
		 * @return array The sorted terms list.
    	 */
    	public function sort_terms_alphabetically( array $cats ) {
    	    $into = [ 'A' => [], 'B' => [], 'C' => [], 'D' => [], 'E' => [], 'F' => [], 'G' => [], 'H' => [], 'I' => [], 'J' => [], 'K' => [], 'L' => [], 'M' => [], 'N' => [], 'O' => [], 'P' => [], 'Q' => [], 'R' => [], 'S' => [], 'T' => [], 'U' => [], 'V' => [], 'W' => [], 'X' => [], 'Y' => [], 'Z' => [] ];
    		foreach ( $cats as $cat ) {
    			$name = $cat->name;
    			$sletter = strtoupper( $name[0] );
    			$into[ $sletter ][ $name ] = $cat;
    	    }
            
    		return $into;
    	}
        
    	/**
    	 * Sort an array of posts alphabetically. Posts will be
    	 * placed under the start letter of each post.
         *
    	 * @param array $posts   The post objects array to sort.
		 * @return array The sorted posts list.
    	 */
    	public function sort_posts_alphabetically( array $posts ) {
    	    $into = [ 'A' => [], 'B' => [], 'C' => [], 'D' => [], 'E' => [], 'F' => [], 'G' => [], 'H' => [], 'I' => [], 'J' => [], 'K' => [], 'L' => [], 'M' => [], 'N' => [], 'O' => [], 'P' => [], 'Q' => [], 'R' => [], 'S' => [], 'T' => [], 'U' => [], 'V' => [], 'W' => [], 'X' => [], 'Y' => [], 'Z' => [] ];
    		foreach ( $posts as $post ) {
    			$name = $post->post_title;
    			$sletter = strtoupper( $name[0] );
    			$into[ $sletter ][ $name ] = $post;
    	    }
            
    		return $into;
    	}
        
		/**
		 * Checks if user registration is enabled on the current site.
		 *
		 * @return 	bool    Whether registration is enabled.
		 */
        public function registration_enabled() {
            $enabled = false;
            if ( is_multisite() ) {
                $network_id = get_current_network_id();
                $option     = get_network_option( $network_id, 'registration' );
                $enabled    = in_array( $option, [ 'user', 'all' ], true );
            } else {
                $option  = get_option( 'users_can_register', false );
                $enabled = in_array( $option, [ 1, true, '1', 'on', 'yes' ], true );
            }
            
            return $enabled;
        }
        
		/**
		 * Sends a debug message to a logging API.
		 *
		 * @param mixed 	$message 	Debug message.
		 * @param string 	$level   	Debug level.
		 * @param array     $context   	Debug context parameters.
		 * @return void
		 */
		public function log_to_stream( $message, string $level = 'info', array $context = [] ): void {
			if ( is_array( $message ) || is_object( $message ) ) {
                $message = print_r( $message, true );
            }
            
            $logstream = MS_Logstream::getInstance();
            $logstream->log( $message, $level, $context );
		}
		
		/**
		 * Output a debug message.
		 *
		 * @param mixed 	$message 	Debug message.
		 * @param string 	$level   	Debug level.
		 * @param array     $context   	Debug context parameters.
		 * @return void
		 */
		public function log( $message, string $level = 'info', array $context = [] ): void {
			if ( ( defined( 'LOGSTREAM_ACTIVE' ) && LOGSTREAM_ACTIVE === true ) || apply_filters( 'msltns_log_to_stream', false ) ) {
                $this->log_to_stream( $message, $level, $context );
            } else {
                $this->parent->log( $message, $level, $context );
            }
		}
		
        /**
		 * Gets the database object.
		 *
		 * @return \Database_Connector
		 */
		private function get_db() {
			global $wpdb;
			return apply_filters( 'msltnswpdb', $wpdb );
		}
	}
	
    add_action( 'init', function() {
        $utils = \msltns\wordpress\MS_Utils::get_instance();
        $utils->init();
    } );
    
    /*
    
    activate:
        MS_Utils::get_instance()->setup_db_for_msltns_transients();
    
    
    deactivate: 
		if ( wp_next_scheduled ( 'cleanup_msltns_transients' ) ) {
		    wp_clear_scheduled_hook( 'cleanup_msltns_transients' );
		}
		MS_Utils::get_instance()->teardown_db_for_msltns_transients();
    
    */
    
    add_action( 'admin_notices', function() {
        $utils = \msltns\wordpress\MS_Utils::get_instance();
        $utils->display_admin_notices();
    } );
    
    add_action( 'core_upgrade_preamble', function() {
        $utils = \msltns\wordpress\MS_Utils::get_instance();
        $utils->debug_pending_updates();
    } );
}
