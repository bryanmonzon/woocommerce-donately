<?php
/*
Plugin Name: Donately - WooCommerce Gateway
Plugin URI: http://www.donately.com/
Description: Extends WooCommerce by Adding the Donately as a gateway.
Version: 1.0
Author: bryanmonzon
Author URI: http://mnzn.co
*/
if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

/**
 * Internationalization
 *
 * @access      public
 * @since       1.6.6
 * @return      void
 */
function mnzn_donately_textdomain() {
    load_plugin_textdomain( 'mnzn_donately', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'mnzn_donately_textdomain' );
 
/**
 * Initialize everything
 */
function mnzn_donately_init() {
    
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
     
    //include the donately class
    include_once( 'includes/woocommerce-donately.php' );
 
    
    /**
     * Add the gateway to the $methods array
     * 
     * @param  [array] $methods
     * @return [array]          
     */
    function mnzn_donately_gateway( $methods ) {
        $methods[] = 'MNZN_Donately';
        return $methods;
    }
    add_filter( 'woocommerce_payment_gateways', 'mnzn_donately_gateway' );
}
add_action( 'plugins_loaded', 'mnzn_donately_init', 0 );
 
/**
 * Add custom settings pages links
 * 
 * @param  [array] $links
 * @return [array]        
 */
function mnzn_donately_action_links( $links ) {
    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'mnzn_donately' ) . '</a>',
    ); 
    // Merge our new link with the default ones
    return array_merge( $plugin_links, $links );    
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'mnzn_donately_action_links' );



/**
 * Conditionally show Donately fields based on the
 * gateway settings in the admin area.
 * 
 * @param  [type] $checkout [description]
 * @return [type]           [description]
 */
function mnzn_donately_checkout_fields( $checkout ) {
    
    $dntly_settings = get_option( 'woocommerce_mnzn_donately_settings' );

    $anonymous = $dntly_settings['anonymous'];
    $onbehalf = $dntly_settings['onbehalf'];

    if( $anonymous == 'yes' || $onbehalf == 'yes') {
        echo '<div id="dntly_anonymous"><h2>' . __('Donation Details') . '</h2>';
        
        if( $anonymous == 'yes' ){

            woocommerce_form_field( 'dntly_anonymous', array(
                'type'          => 'checkbox',
                'class'         => array('dntly-anonymous form-row-wide'),
                'label'         => __('Check the box to donate anonymously.'),
                ), $checkout->get_value( 'dntly_anonymous' ));    
        }
        
        if( $onbehalf == 'yes' ){

        woocommerce_form_field( 'dntly_onbehalf', array(
            'type'          => 'text',
            'class'         => array('dntly-onbehalf form-row-wide'),
            'label'         => __('Donate on behalf of someone.'),
            'placeholder'   => __('Name of person (optional)')
            ), $checkout->get_value( 'dntly_onbehalf' ));
        }
        
        echo '</div>';    
    }
    
}
add_action( 'woocommerce_after_order_notes', 'mnzn_donately_checkout_fields' );

