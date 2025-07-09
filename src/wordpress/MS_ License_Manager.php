<?php

namespace msltns\wordpress;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use msltns\wordpress\MS_Utils;

/**
 * Class MS_License_Manager lets you manage your plugin and theme licenses.
 *
 * This class handles the complete license management of any wp plugin. It integrates with
 * Software License Manager (@see https://wordpress.org/plugins/software-license-manager/).
 * 
 * First of all you should integrate the license input form into your settings area. You
 * can easily do this by calling 
 * 
 * <code>
 * do_action( '<your-plugin-prefix>_license_form' );
 * </code> 
 * 
 * within your settings.php file.
 * 
 * To initialize the license manager add the following code:  
 * 
 * <code>
 * use msltns\wordpress\MS_License_Manager;
 * 
 * $lm = new MS_License_Manager();
 * $lm->init(
 * 	'<YOUR_PREFIX>',
 * 	'<YOUR-SLUG>',
 * 	'<License Server API URL>',
 * 	'<Secret Key for License Verification Requests>'
 * );
 * </code>
 * 
 * giving a plugin prefix, the plugin reference. 
 * 
 * You can then check whether a license key is active using 
 * 
 * <code>
 * $license_active = apply_filters( '<your-plugin-prefix>_license_active', false );
 * </code>
 * 
 * There are two action hooks that inform you about license key activation and deactivation: 
 * 
 * <code>
 * do_action( '<your-plugin-prefix>_license_activated' );
 * do_action( '<your-plugin-prefix>_license_deactivated' );
 * </code>
 * 
 * You only need to add these action listeners to your plugin class in order to get up-to-date.
 * 
 * <code>
 * add_action( '<your-plugin-prefix>_license_activated', array( $this, 'on_license_activated' ) );
 * add_action( '<your-plugin-prefix>_license_deactivated', array( $this, 'on_license_deactivated' ) );
 * </code>
 *
 * @category 	Class
 * @package  	MS_License_Manager
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
if ( ! class_exists( '\msltns\wordpress\MS_License_Manager' ) ) {
	
	class MS_License_Manager {
        
        // Utilities class
        private $utils;
		// Prefix for Plugin related Context
		private $prefix = '';
		// Item Reference for License Verification Requests
		private $reference = '';
		// Secret Key for License Verification Requests
		private $token = '';
		// Server URL for License Verification Requests
		private $host = '';
						
		public function __construct() {
            $this->utils = MS_Utils::instance();
		}

		public function init( $prefix = '', $reference = '', $host = '', $token = '' ) {

            if ( empty( $prefix ) ) {
				$plugin_dir_arr = explode( "/", plugin_basename( __FILE__ ), 2 );
				$prefix 		= $plugin_dir_arr[0];
			}
			
			if ( empty( $reference ) ) {
				$plugin_data = get_plugin_data( __FILE__, false, false );
				$reference 	 = $plugin_data['Name'];
			}
			
			if ( empty( $host ) ) {
				throw new \Exception( 'Parameter $host must not be empty' );
			}
			
			if ( empty( $token ) ) {
				throw new \Exception( 'Parameter $token must not be empty' );
			}
			
			$this->prefix	 = $prefix;
			$this->reference = $reference;
			$this->host		 = $host;
			$this->token	 = $token;
			
			$settings = get_site_option( "{$this->prefix}_license", [] );
			if ( ! $settings ) {
				update_site_option( "{$this->prefix}_license", [ 
					"{$this->prefix}_license_key"    => '', 
					"{$this->prefix}_license_status" => '',
				] );
			}
			
			register_deactivation_hook( __FILE__, array( $this, 'on_plugin_deactivation' ) );
		
			add_filter( "{$this->prefix}_license_active",       array( $this, 'license_active' ) );
			add_action( "{$this->prefix}_license_form",         array( $this, 'create_license_form' ) );
            add_action( 'msltns_register_license_settings',    array( $this, 'register_license_settings' ) );
            add_action( 'msltns_license_settings_form',        array( $this, 'print_settings_form' ) );

			add_action( 'msltns_run_hourly_job', array( $this, 'validate_license_key' ), 10 );
			if( !wp_next_scheduled( 'msltns_run_hourly_job' ) ) {
				wp_schedule_event( time(), 'hourly', 'msltns_run_hourly_job' );
			}
			
			add_action( 'admin_init', array( $this, 'handle_license_requests' ) );
		}
		
		public function on_plugin_deactivation() {
			// find out when the last event was scheduled
			$timestamp = wp_next_scheduled( 'msltns_run_hourly_job' );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'msltns_run_hourly_job' );
			} 
		}
		
		public function handle_license_requests() {
			
			$license_data = '';
			$options	  = get_site_option( "{$this->prefix}_license", [] );

			/*** License activate button was clicked ***/
			if ( isset( $_REQUEST['activate_license'] ) ) {
				
                $license_dom = $this->get_domain();
				$license_key = trim( $_REQUEST["{$this->prefix}_license"]["{$this->prefix}_license_key"] );
				
                // $this->log( "Activate license key {$license_key}" );
                
                // API query parameters
			    $api_params = array(
			        'slm_action' 		=> 'slm_activate',
			        'secret_key' 		=> $this->token,
			        'license_key' 		=> $license_key,
			        'registered_domain' => $license_dom,
			        'item_reference' 	=> urlencode( $this->reference ),
			    );

			    // Send query to the license manager server
			    $query 	  = esc_url_raw( add_query_arg( $api_params, $this->host ) );
			    $response = wp_remote_get( $query, array( 'timeout' => 30, 'sslverify' => false ) );

			    // Check for error in the response
			    if ( is_wp_error( $response ) ) {
					$msg = __( 'Your license couldn\'t be activated.', 'msltns' );
					$this->log( $msg, 'error' );
					$this->add_admin_notice( $msg, 'error', true );
			    }

			    // License data.
			    $license_data = json_decode( wp_remote_retrieve_body( $response ) );
				
				if ( $license_data->result == 'success' || $license_data->error_code === 40 ) {
                    // error_code 40: License key already in use on current domain
		
					//Success was returned for the license activation
			        //Save the license key in the options table
			        $options["{$this->prefix}_license_key"] = $license_key;
                    $options["{$this->prefix}_license_domain"] = $license_dom;
					update_site_option( "{$this->prefix}_license", $options );
                    $msg = __( 'Your license has been activated. Enjoy!', 'msltns' );
					$this->log( $msg );
					$this->add_admin_notice( $msg, 'success', true );
					do_action( "{$this->prefix}_license_activated" );
		
			    } else {
					//Reset license key
			        $options["{$this->prefix}_license_key"] = '';
					update_site_option( "{$this->prefix}_license", $options );
                    $msg = sprintf( __( 'Your license couldn\'t be activated. Error message was: %s', 'msltns' ), $license_data->message );
					$this->log( $msg, 'error' );
					$this->add_admin_notice( $msg, 'error', true );
					do_action( "{$this->prefix}_license_deactivated" );
				}
			}
			/*** End of license activation ***/

			/*** License deactivate button was clicked ***/
			if ( isset( $_REQUEST['deactivate_license'] ) ) {
				
                $license_dom = $this->get_domain();
				$license_key = trim( $_REQUEST["{$this->prefix}_license"]["{$this->prefix}_license_key"] );
				
                // $this->log( "Deactivate license key {$license_key}" );
				
				// API query parameters
			    $api_params = array(
			        'slm_action' 		=> 'slm_deactivate',
			        'secret_key' 		=> $this->token,
			        'license_key' 		=> $license_key,
			        'registered_domain' => $license_dom,
			        'item_reference' 	=> urlencode( $this->reference ),
			    );

			    // Send query to the license manager server
			    $query	  = esc_url_raw( add_query_arg( $api_params, $this->host ) );
			    $response = wp_remote_get( $query, array( 'timeout' => 20, 'sslverify' => false ) );

			    // Check for error in the response
			    if ( is_wp_error( $response ) ) {
					$msg = __( 'Your license couldn\'t be deactivated.', 'msltns' );
					$this->log( $msg, 'error' );
					$this->add_admin_notice( $msg, 'error', true );
			    }

			    $license_data = json_decode( wp_remote_retrieve_body( $response ) );
				
				if ( $license_data->result == 'success' || $license_data->error_code === 80 ) {
                    // error_code 80: License key already inactive
		
					//Success was returned for the license activation
			        //Remove the license key from the options table. It will need to be activated again.
					$options["{$this->prefix}_license_key"] = '';
					$options["{$this->prefix}_license_status"] = 'deactivated';
					update_site_option( "{$this->prefix}_license", $options );
					$msg = __( 'Your license has been deactivated.', 'msltns' );
					$this->log( $msg );
					$this->add_admin_notice( $msg, 'success', true );
		
				} else {
					$msg = sprintf( __( 'Your license couldn\'t be deactivated. Error message was: %s', 'msltns' ), $license_data->message );
					$this->log( $msg, 'error' );
					$this->add_admin_notice( $msg, 'error', true );
			    }

				do_action( "{$this->prefix}_license_deactivated" );
			}
			/*** End of license deactivation ***/
			
			$this->validate_license_key();
		}
		
		public function license_active() {
			
			$options	 	= get_site_option( "{$this->prefix}_license", [] );
			$license_key 	= $options["{$this->prefix}_license_key"];
			$license_status = $options["{$this->prefix}_license_status"];
			
			return !empty( $license_key ) && !empty( $license_status ) && $license_status === 'active';
		}
		
		public function validate_license_key() {
			
			$status 	 = '';
			$options	 = get_site_option( "{$this->prefix}_license", [] );
			$license_key = $options["{$this->prefix}_license_key"];
			
			if ( $license_key && !empty( trim( $license_key ) ) ) {
				$api_params = array(
					'slm_action'  => 'slm_check',
					'secret_key'  => $this->token,
					'license_key' => $license_key
				);
				$response = wp_remote_get( add_query_arg( $api_params, $this->host ), array( 'timeout' => 20, 'sslverify' => false ) );
				if ( ! is_wp_error( $response ) ) {
					$license_data = json_decode( wp_remote_retrieve_body( $response ) );
					if ( $license_data && isset( $license_data->status ) ) {
						$status = $license_data->status;
						if ( $status === 'active' ) {
                            $domain = $this->get_domain();
							$found  = false;
							$registered = $license_data->registered_domains;
							foreach ( $registered as $d ) {
								if ( $d->registered_domain === $domain ) {
									// status is really active
									$found = true;
								}
							}
							if ( ! $found && ( empty( $license_data->registered_domains ) || count( $license_data->registered_domains ) <= intval( $license_data->max_allowed_domains ) ) ) {
								// status is actually pending
								$status = 'pending';
							}
						} else if ( in_array( $status, array( 'expired', 'blocked' ) ) ) {
							do_action( "{$this->prefix}_license_deactivated" );
						}						
					}
			    }
			}
			
			$options["{$this->prefix}_license_status"] = $status;
			update_site_option( "{$this->prefix}_license", $options );
			
			return $status === 'active';
		}
        
        public function get_update_params( $slug ) {
            
            $lic_key = $this->get_license_key();
            $lic_dom = $this->get_domain();
            
            $data = array(
                'sl' => $slug,
                'lk' => $lic_key,
                'ld' => $lic_dom,
            );
			
            return '?' . http_build_query( $data );
        }
		
		public function check_license_status() {
			
			$options	 	= get_site_option( "{$this->prefix}_license", [] );
			$license_status = $options["{$this->prefix}_license_status"];
			
		    if ( $license_status && $license_status === 'active' ) {
				return true;
			
		    } else if ( ! $license_status || $license_status === 'pending' ) {
			
				$link = '<a href="' . add_query_arg( array( 'page' => "{$this->prefix}_settings", 'tab' => 'license-settings' ), admin_url( 'admin.php' ) ) . '">' . __( 'activate', 'msltns' ) . '</a>';
				$msg = sprintf( __( 'Please %s your plugin to use its functionality.', 'msltns' ), $link );
				$this->add_admin_notice( $msg, 'warning', true );
			
			} else if ( $license_status && $license_status === 'expired' ) {
			
				$msg = __( 'Your license has expired. Please renew it or uninstall the plugin.', 'msltns' );
				$this->add_admin_notice( $msg, 'error', true );
			
			} else if ( $license_status && $license_status === 'blocked' ) {
			
				$msg = __( 'Your license has been blocked. Please renew it or uninstall the plugin.', 'msltns' );
				$this->add_admin_notice( $msg, 'error', true );
				
			}
			
			return false;
		}
		
		public function register_license_settings() {

            // do_action( 'msltns_register_license_settings' );

			// === LICENSE SETTINGS ===
            $option_group = "{$this->prefix}-license";
            $option_name  = "{$this->prefix}_license";

			register_setting( $option_group, $option_name );

			add_settings_section(
	        	$option_name,
	        	false,
	        	false,
	        	$option_group,
	    	);
            
            $license_active = $this->license_active();

			$name		 = ( $license_active ) ? 'deactivate_license' : 'activate_license';
			$description = ( $license_active ) ? '<span style="color:#78b370;">' . __( 'Your license is active.', 'msltns' ) . '</span>' : '<span style="color:#f21717;">' . __( 'Your license is inactive.', 'msltns' ) . '</span>';
		
			add_settings_field(
	        	"{$this->prefix}_license_key",
	        	__( 'License Key', 'msltns' ),
	        	array( $this, 'render_input' ),
	        	$option_group,
	        	$option_name,
	        	array( 
					'type'        => 'text',
					'name'        => "{$this->prefix}_license_key",
					'container'   => $option_name,
					'description' => $description,
				),
	        	"{$this->prefix}_license_key"
	    	);
			
			add_settings_field(
	        	$name,
	        	'',
	        	array( $this, 'render_input' ),
	        	$option_group,
	        	$option_name,
	        	array(
					'type'      => 'hidden',
					'name'      => $name,
					'container' => $option_name,
				),
	        	$name
	    	);
		}

		public function print_settings_form() {
            settings_fields( "{$this->prefix}-license" );
    		do_settings_sections( "{$this->prefix}-license" );
            
            $license_active = $this->license_active();
            
			$text = ( $license_active ) ? __( 'Deactivate License', 'msltns' ) : __( 'Activate License', 'msltns' );
			$type = 'primary';
			$name = ( $license_active ) ? 'deactivate_license_btn' : 'activate_license_btn';
			$id   = $name;
			
			echo get_submit_button( $text, $type, $name );
        }
		
		public function render_input( $args ) {
            $this->utils->render_input( $args );
		}
        
        private function get_license_key() {
            
			$options = get_site_option( "{$this->prefix}_license", [] );
			
            return isset( $options["{$this->prefix}_license_key"] ) ? $options["{$this->prefix}_license_key"] : '';
        }
        
        private function get_domain() {
            $ddata = $this->utils->parse_url( untrailingslashit( home_url() ) );
            $license_dom = $ddata['domain'];
            $license_dom = trim( wp_unslash( strip_tags( $license_dom ) ) );
            
            return $license_dom;
        }
		
		private function add_admin_notice( $message, $level = 'info', $dismissible = true ) {
			$this->utils->add_admin_notice( $message, $level, $dismissible );
		}
		
    	/**
    	 * Output a debug message.
    	 *
    	 * @param string 	$message 	Debug message.
    	 * @param string 	$level   	Debug level.
    	 * @return void
    	 */
    	private function log( $message, $level = 'info' ) {
            $this->utils->log( $message, $level );
    	}
	}
}
