<?php

namespace msltns\wordpress;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use msltns\wordpress\MS_Element;

/**
 * Class MS_TabPane adds some useful shortcodes.
 *
 * @category 	Class
 * @package  	MS_TabPane
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
if ( ! class_exists( '\msltns\wordpress\MS_TabPane' ) ) {
    
    class MS_TabPane extends MS_Element {
        
        private $tabs_data;
        
        /**
    	 * Main constructor.
    	 */
        public function __construct() {
            parent::__construct();
            
            $this->routes = [
                'msltnscss/frontend' => [
                    'file'  => MSLTNS_ASSETS_DIR . '/css/msltns.min.css',
                    'type'  => 'css',
                    'ctype' => 'text/css',
                ],
                'msltnscss/bootstrap' => [
                    'file'  => MSLTNS_ASSETS_DIR . '/css/bootstrap.min.css',
                    'type'  => 'css',
                    'ctype' => 'text/css',
                ],
                'msltnsjs/bootstrap' => [
                    'file'  => MSLTNS_ASSETS_DIR . '/js/bootstrap.bundle.min.js',
                    'type'  => 'js',
                    'ctype' => 'application/javascript',
                ],
            ];
            
            $this->scripts = [
                'msltnscss' => [
                    'action' => 'register',
                    'type'   => 'style',
                    'handle' => 'msltns',
                    'src'    => trailingslashit( home_url() ) . 'msltnscss/frontend',
                ],
                'bootstrapcss' => [
                    'action' => 'register',
                    'type'   => 'style',
                    'handle' => 'bootstrap',
                    'src'    => trailingslashit( home_url() ) . 'msltnscss/bootstrap',
                ],
                'bootstrapjs' => [
                    'action' => 'register',
                    'type'   => 'script',
                    'handle' => 'bootstrap',
                    'src'    => trailingslashit( home_url() ) . 'msltnsjs/bootstrap',
                    'deps'   => [ 'jquery' ],
                    'footer' => true,
                ],
            ];
            
			add_shortcode( 'ms_tabpane', array( $this, 'generate_tab_pane' ) );
			add_shortcode( 'ms_tab', array( $this, 'generate_single_tab' ) );
            
			add_shortcode( 'msltns_tabpane', array( $this, 'generate_tab_pane' ) );
			add_shortcode( 'msltns_tab', array( $this, 'generate_single_tab' ) );
        }
        
		/**
		 * Renders a bootstrap tab pane.
		 * 
		 * @param $attr
		 * @param $content
		 * @return string
		 */
		public function generate_tab_pane( $atts, $content = '' ) {
            
            wp_enqueue_style( 'msltns' );
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
            
            $ul_class     = apply_filters( 'msltns_tabpane_nav_class', 'text-center' );
            $li_class     = apply_filters( 'msltns_tabpane_nav_link_class', '' );
            
            if ( $this->utils->starts_with( $tabs_content, '<br />' ) ) {
                $tabs_content = substr( $tabs_content, 6 );
            }
            
            ob_start();
			?>
			<div class="<?php echo esc_attr( $class ) ?>">
                <div class="msltns-tabs-container">
                    <ul class="nav nav-tabs <?php echo esc_attr( $ul_class ) ?>">
            
                        <?php foreach ( $this->tabs_data as $index => $tab ) : ?>
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
            $class = apply_filters( 'msltns_tabpane_content_class', 'tab-pane fade half-internal-gutter single-block-padding in' );
			
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
