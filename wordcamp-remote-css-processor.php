<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace WordCamp\RemoteCSS\Engine;

if ( ! function_exists( safe_css ) ) {

    function safecss_class() {
        // Wrapped so we don't need the parent class just to load the plugin
        if ( class_exists( 'safecss' ) ){
            return;
        }
        
        require_once( dirname( __FILE__ ) . '/csstidy/class.csstidy.php' );

        class safecss extends csstidy_optimise {

            function __construct( &$css ) {
                return $this->csstidy_optimise( $css );
            }

            function postparse() {
                return parent::postparse();
            }

            function subvalue() {
                return parent::subvalue();
            }

        }

    }

}

function get_raw_css( $url ) {
    $response = wp_remote_get( $url );
    if ( ! is_array( $response ) ) {
        return false;
    }

    $body = $response[ 'body' ];

    if ( empty( $body ) ) {
        return false;
    }

    return $body;
}

function sanitise_raw_css( $raw ) {
    $warnings = array();
    safecss_class();
    $csstidy = new csstidy();
    $csstidy->optimise = new safecss( $csstidy );
    $csstidy->set_cfg( 'remove_bslash', false );
    $csstidy->set_cfg( 'compress_colors', false );
    $csstidy->set_cfg( 'compress_font-weight', false );
    $csstidy->set_cfg( 'optimise_shorthands', 0 );
    $csstidy->set_cfg( 'remove_last_;', false );
    $csstidy->set_cfg( 'case_properties', false );
    $csstidy->set_cfg( 'discard_invalid_properties', true );
    $csstidy->set_cfg( 'css_level', 'CSS3.0' );
    $csstidy->set_cfg( 'preserve_css', true );
    $csstidy->set_cfg( 'template', dirname( __FILE__ ) . '/csstidy/wordpress-standard.tpl' );
    $css = $orig = $raw;
    $css = preg_replace( '/\\\\([0-9a-fA-F]{4})/', '\\\\\\\\$1', $prev = $css );
    if ( $css != $prev )
        $warnings[] = 'preg_replace found stuff';
    // Some people put weird stuff in their CSS, KSES tends to be greedy
    $css = str_replace( '<=', '&lt;=', $css );
    // Why KSES instead of strip_tags?  Who knows?
    $css = wp_kses_split( $prev = $css, array(), array() );
    $css = str_replace( '&gt;', '>', $css ); // kses replaces lone '>' with &gt;
    // Why both KSES and strip_tags?  Because we just added some '>'.
    $css = strip_tags( $css );
    if ( $css != $prev ){
        $warnings[] = 'kses found stuff';
    }
    
    $csstidy->parse( $css );
    $css = $csstidy->print->plain();
    

    return $css;
}

function save_remote_css() {
    $url = get_option( '_wordcamp-remote-css-url' );
    if ( ! $url ) {
        return;
    }
    $css = sanitise_raw_css( get_raw_css( $url ) );
    if ( ! $css ) {
        return;
    }
    update_option( '_wordcamp-remote-css', $css );
}

function set_cron_job() {

    $frequency = get_option( '_wordcamp-remote-css-update-frequency', 'twicedaily' );

    if ( ! wp_next_scheduled( '_wordcamp_update_remote_css' ) ) {
        wp_schedule_event( time(), $frequency, '_wordcamp_update_remote_css' );
    }

    add_action( '_wordcamp_update_remote_css', 'save_remote_css' );
}
