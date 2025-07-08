<?php

namespace msltns\wordpress;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use msltns\utilities\Logstream;

/**
 * Class WP_Logstream provides a way to log debug messages to an API.
 *
 * @category 	Class
 * @package  	Utilities
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

// use msltns\wordpress\WP_Logstream;
// WP_Logstream::getInstance( 'sender' )->log( 'message', 'level' );
if ( ! class_exists( '\msltns\wordpress\WP_Logstream' ) ) {
	
	class WP_Logstream extends Logstream {
        
		/**
		 * @var \WP_Logstream
		 */
		private static $instance;
        
        /**
         * @var string handlers
         */
        private $handlers;
		
		/**
		 * Main constructor.
		 *
		 * @return void
		 */
		private function __construct() {
            
		}
        
		/**
		 * Singleton instance.
		 * 
         * @param   string  $plattform      The platform.
         * @param   string  $environment    The environment.
         * @param   string  $service        The service.
		 * @return \WP_Logstream
		 */
		final public static function instance( string $platform = '', string $environment = '', string $service = '' ) {
			return self::getInstance( $platform, $environment, $service );
		}
        
		/**
		 * Singleton instance.
		 * 
         * @param   string  $plattform      The platform.
         * @param   string  $environment    The environment.
         * @param   string  $service        The service.
		 * @return \WP_Logstream
		 */
		final public static function get_instance( string $platform = '', string $environment = '', string $service = '' ) {
			return self::getInstance( $platform, $environment, $service );
		}
		
		/**
		 * Singleton instance.
		 * 
         * @param   string  $plattform      The platform.
         * @param   string  $environment    The environment.
         * @param   string  $service        The service.
		 * @return \WP_Logstream
		 */
		final public static function getInstance( string $platform = '', string $environment = '', string $service = '' ) {
			
			if ( !isset( self::$instance ) ) {
				self::$instance = new self();
				self::$instance->init( $platform, $environment, $service );
			}
            
			return self::$instance;
		}
		
		/**
		 * Initialization method.
		 * 
         * @param   string  $plattform      The platform.
         * @param   string  $environment    The environment.
         * @param   string  $service        The service.
		 * @return void
		 */
		protected function init( string $platform = '', string $environment = '', string $service = '' ) {
            
            $this->handlers = $this->get_logstream_handlers();
            
            if ( defined( 'LOGSTREAM_DISPLAY_SETTINGS' ) && LOGSTREAM_DISPLAY_SETTINGS === true ) {
                add_action( 'admin_init', array( $this, 'register_settings_fields' ) );
            }
            
            $handler = get_option( 'logstream_handler', false );
			if ( !empty( $handler ) && !defined( 'LOGSTREAM_HANDLER' ) ) {
				define( 'LOGSTREAM_HANDLER', $handler );
			}
            
            $url = get_option( 'logstream_url', false );
			if ( !empty( $url ) && !defined( 'LOGSTREAM_API_URL' ) ) {
				define( 'LOGSTREAM_API_URL', $url );
			}
            
            $token = get_option( 'logstream_token', false );
			if ( !empty( $token ) && !defined( 'LOGSTREAM_API_TOKEN' ) ) {
				define( 'LOGSTREAM_API_TOKEN', $token );
			}
            
            if ( empty( $platform ) ) {
                $setting = get_option( 'logstream_platform', false );
                if ( !empty( $setting ) ) {
                    $platform = $setting;
                } else if ( defined( 'LOGSTREAM_PLATFORM' ) ) {
                    $platform = LOGSTREAM_PLATFORM;
                } else {
                    $platform = parse_url( home_url(), PHP_URL_HOST );
                }				
			}
            
            if ( empty( $environment ) ) {
                $setting = get_option( 'logstream_environment', false );
                if ( !empty( $setting ) ) {
                    $environment = $setting;
                } else if ( defined( 'LOGSTREAM_ENVIRONMENT' ) ) {
                    $environment = LOGSTREAM_ENVIRONMENT;
                }
            }
            
            if ( empty( $service ) ) {                
                $setting = get_option( 'logstream_service', false );
                if ( !empty( $setting ) ) {
                    $service = $setting;
                } else if ( defined( 'LOGSTREAM_SERVICE' ) ) {
                    $service = LOGSTREAM_SERVICE;
                }
            }
            
            $this->platform    = $platform;
            $this->environment = $environment;
            $this->service     = $service;
			
            
            // WP Errors
            $log_wp_errors = get_option( 'logstream_wp_errors', false );
			
            if ( $log_wp_errors === '1' || ( defined( 'LOGSTREAM_WP_ERRORS' ) && LOGSTREAM_WP_ERRORS === true ) || apply_filters( 'msltns_log_wp_errors_to_stream', false ) ) {
                add_action( 'wp_error_added',           array( $this, 'handle_wp_error' ), 10, 4 );
                add_action( 'doing_it_wrong_run',       array( $this, 'handle_wp_doing_it_wrong' ), 10, 3 );
                add_action( 'deprecated_function_run',  array( $this, 'handle_wp_deprecated_function' ), 10, 3 );
            }
		}
				
		public function register_settings_fields() {
            
            add_settings_section(
                'msltns_logstream',
                __( 'Logstream Settings', 'msltns' ),
                array(),
                'general'
            );
            
            register_setting( 'general', 'logstream_active' );
		    add_settings_field(
		        'logstream_active_id', 
		        'Use Logstream',
		        array( $this, 'print_logstream_settings_field' ),
		        'general',
		        'msltns_logstream',
		        array(
                    'name' => 'logstream_active',
                    'type' => 'checkbox',
                )
		    );
            
			register_setting( 'general', 'logstream_platform' );
		    add_settings_field(
		        'logstream_platform_id', 
		        'Platform',
		        array( $this, 'print_logstream_settings_field' ),
		        'general',
		        'msltns_logstream',
		        array(
                    'name' => 'logstream_platform',
                    'type' => 'text',
                )
		    );
            
			register_setting( 'general', 'logstream_environment' );
		    add_settings_field(
		        'logstream_environment_id', 
		        'Environment',
		        array( $this, 'print_logstream_settings_field' ),
		        'general',
		        'msltns_logstream',
		        array(
                    'name' => 'logstream_environment',
                    'type' => 'text',
                )
		    );
            
			register_setting( 'general', 'logstream_service' );
		    add_settings_field(
		        'logstream_service_id', 
		        'Service Name',
		        array( $this, 'print_logstream_settings_field' ),
		        'general',
		        'msltns_logstream',
		        array(
                    'name' => 'logstream_service',
                    'type' => 'text',
                )
		    );
		
       	 	register_setting( 'general', 'logstream_handler' );
		    add_settings_field(
		        'logstream_handler_id', 
		        'Logstream Handler',
		        array( $this, 'print_logstream_settings_field' ),
		        'general',
		        'msltns_logstream',
		        array(
                    'name'    => 'logstream_handler',
                    'type'    => 'select',
                    'options' => $this->handlers,
                )
		    );
            
            register_setting( 'general', 'logstream_url' );
		    add_settings_field(
		        'logstream_url_id', 
		        'Logstream API Url',
		        array( $this, 'print_logstream_settings_field' ),
		        'general',
		        'msltns_logstream',
		        array(
                    'name' => 'logstream_url',
                    'type' => 'text',
                )
		    );
            
			register_setting( 'general', 'logstream_token' );
		    add_settings_field(
		        'logstream_token_id', 
		        'Logstream API Token',
		        array( $this, 'print_logstream_settings_field' ),
		        'general',
		        'msltns_logstream',
		        array(
                    'name' => 'logstream_token',
                    'type' => 'text',
                )
		    );
            
            register_setting( 'general', 'logstream_wp_errors' );
		    add_settings_field(
		        'logstream_wp_errors_id', 
		        'Log WP Errors',
		        array( $this, 'print_logstream_settings_field' ),
		        'general',
		        'msltns_logstream',
		        array(
                    'name' => 'logstream_wp_errors',
                    'type' => 'checkbox',
                )
		    );
            
		}

		public function print_logstream_settings_field( $args ) {
            $name  = $args['name'];
            $type  = $args['type'];
            $value = get_option( $name );
            if ( in_array( $type, [ 'text', 'number', 'date' ] ) ) {
    		    ?>
    		    <input type="text" name="<?php echo esc_attr( $name ); ?>" class="regular-text" value="<?php echo esc_attr( $value ); ?>" />
    		    <?php
            }
		    else if ( $type === 'select' ) {
		        ?>
                <select id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" style="min-width:350px;">
                    <?php
                    foreach( $args['options'] as $o_value => $o_label ) {
                        ?>
                        <option value="<?php echo esc_attr( $o_value ); ?>"<?php if ( $o_value === $value ) { echo ' selected="selected"'; } ?>><?php echo esc_html( $o_label ); ?></option>
                        <?php
                    }
                    ?>
                </select>
                <?php
		    }
    		else if ( $type === 'checkbox' ) {
                ?>
    			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="0">
    			<input type="checkbox" 
                       id="<?php echo esc_attr( $name ); ?>" 
                       name="<?php echo esc_attr( $name ); ?>" 
                       value="1" 
                       <?php if ( $value === "1" ) { echo 'checked="checked"'; } ?>>           
                <?php
    		}
		}
        
		/**
		 * Fires when an error is added to a WP_Error object.
		 *
		 * @param string|int $code     Error code.
		 * @param string     $message  Error message.
		 * @param mixed      $data     Error data. Might be empty.
		 * @param WP_Error   $wp_error The WP_Error object.
		 */
		public function handle_wp_error( $code, $message, $data, $WP_Error ) {
            if ( is_object( $data ) ) {
                $data = print_r( $data, true );
            }
            
            if ( ! is_array( $data ) ) {
                $data = [ 
                    'code' => $code,
                    'data' => $data
                ];
            }
            
            $context = [];
            if ( !empty( $data ) ) {
                $context = $data;
            }
            
            $this->log( "WP_Error: {$message}", 'error', $context );
		}
		
		/**
		 * Fires when the given function is being used incorrectly.
		 *
		 * @param string $function The function that was called.
		 * @param string $message  A message explaining what has been done incorrectly.
		 * @param string $version  The version of WordPress where the message was added.
		 */
		public function handle_wp_doing_it_wrong( $function, $message, $version ) {
			$this->log( "Function {$function} called wrong. {$message}. WordPress-Version: {$version}", 'error' );
		}
		
		/**
		 * Fires when a deprecated function is called.
		 *
		 * @param string $function    The function that was called.
		 * @param string $replacement The function that should have been called.
		 * @param string $version     The version of WordPress that deprecated the function.
		 */
		public function handle_wp_deprecated_function( $function, $replacement, $version ) {
			$this->log( "Function {$function} is deprecated, use {$replacement} instead. WordPress-Version: {$version}", 'notice' );
		}
        
        private function get_logstream_handlers() {
            $handlers = [
                '\msltns\utilities\Logstream_Handler'     => 'Default Debug Logstream',
                '\msltns\utilities\Grafana_Loki_Handler'  => 'Grafana Loki Handler',
            ];
            
            return apply_filters( 'msltns_logstream_handlers', $handlers );
        }
		
	}
	
}
