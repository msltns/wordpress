<?php

namespace msltns\wordpress;

if ( ! defined( 'ABSPATH' ) ) { exit; }

use msltns\wordpress\MS_Utils;

/**
 * Class MS_CF7_Helper allows to translate Contact Form 7 forms.
 *
 * @category 	Class
 * @package  	MS_CF7_Helper
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
if ( ! class_exists( '\msltns\wordpress\MS_CF7_Helper' ) ) {
    
    class MS_CF7_Helper {
        
        /**
    	 * Main constructor.
    	 */
        public function __construct() {
            if ( !is_admin() ) {
                add_filter( 'wpcf7_contact_form_property_form', array( $this, 'translate_marked_form_strings' ), 99999, 2 );
                add_filter( 'wpcf7_form_tag',                   array( $this, 'correct_piped_key_value_pairs_of_selectable_form_tags' ), 10, 2 );
                add_filter( 'wpcf7_form_tag',                   array( $this, 'translate_form_tag_labels' ), 20, 2 );
            }
        }
        
        /**
         * Searches for {{...}} braced strings in cf7 forms and 
         * replaces them with localized strings if available. Please
         * make sure the strings are already translated somewhere in
         * the consuming code. To achieve this just create an array
         * containing all strings to be translated, such as
         * <pre>
         * $strings = [
         *     'string1' => __( 'string1', 'your-text-domain' ),
         *     'string2' => __( 'string2', 'your-text-domain' ),
         *     'string3' => __( 'string3', 'your-text-domain' ),
         *     // ...
         *     'stringN' => __( 'stringN', 'your-text-domain' ),
         * ];
         * </pre>
         * 
         * Make also sure that the related textdomain is transmitted via
         * <pre>
         * add_filter( 'msltns_textdomain', function( $textdomain ) { return 'your-text-domain'; } );
         * </pre>
         * 
         * @param   string  $form           The CF7 form.
         * @param   CF7     $contact_form   The CF7 form object.
         * @return  string  The translated form.
         */
        public function translate_marked_form_strings( $form, $contact_form ) {
            $form_id = (int) $contact_form->id();
            
            $textdomain  = apply_filters( 'msltns_textdomain', 'msltns' );
            
            $pattern     = '/(\{\{.*?\}\})/s';
            $pattern     = apply_filters( 'msltns_translate_cf7_form_tags_pattern', $pattern, $form_id );
            
            $replacement = ['{{','}}'];
            $replacement = apply_filters( 'msltns_translate_cf7_form_tags_replacement', $replacement, $pattern, $form_id );
            
            $form = preg_replace_callback( $pattern, function( $matches ) use ( $textdomain, $replacement ) {
                
                $match = $matches[1];
                $match = str_replace( $replacement, '', $match );
                
                return __( $match, $textdomain );
            }, $form );
            
            return apply_filters( 'msltns_translated_cf7_form', $form );
        }
        
        /**
    	 * Translate all labels in the given CF7 form tag.
         * Make sure that the related textdomain is transmitted via
         * <pre>
         * add_filter( 'msltns_textdomain', function( $textdomain ) { return 'your-text-domain'; } );
         * </pre>
    	 *
    	 * @param array     $tag        The form tag.
    	 * @param bool      $unused     Whether this tag is unused.
    	 * @return array    The translated form tag.
    	 */
        public function translate_form_tag_labels( $tag, $unused ) {
            
            if ( is_null( $tag ) ) {
                return $tag;
            }
            
            $textdomain = apply_filters( 'msltns_textdomain', 'msltns' );
            
            if ( !empty( $tag['labels'] ) ) {
                $labels = [];
                foreach ( $tag['labels'] as $label ) {
                    $labels[] = __( $label, $textdomain );
                }
                $tag['labels'] = $labels;
            }
            
            if ( $tag['basetype'] === 'submit' && !empty( $tag['values'] ) ) {
                $values = [];
                foreach ( $tag['values'] as $value ) {
                    $values[] = __( $value, $textdomain );
                }
                $tag['values'] = $values;
            }
            
            return apply_filters( 'msltns_translated_cf7_form_tag', $tag );
        }
        
        /**
    	 * Corrects piped key|value pairs of selectable form tags.
    	 *
    	 * @param array     $tag        The form tag.
    	 * @param bool      $unused     Whether this tag is unused.
    	 * @return array    The corrected form tag.
    	 */
        public function correct_piped_key_value_pairs_of_selectable_form_tags( $tag, $unused ) {
            
            if ( is_null( $tag ) ) {
                return $tag;
            }
            
            if ( in_array( $tag['basetype'], [ 'checkbox', 'radio', 'select' ] ) ) {
                $values = [];
                foreach ( $tag['raw_values'] as $raw ) {
                    if ( preg_match( '/[^\|]+\|[\w\d]+/', $raw ) ) {
                        $kv = explode( '|', $raw );
                        $values[] = $kv[1];
                    }
                }
                if ( !empty( $values ) ) {
                    $tag['values'] = $values;
                }
            }
            
            return apply_filters( 'msltns_corrected_selectable_cf7_form_tag', $tag );
        }
        
    }

}
