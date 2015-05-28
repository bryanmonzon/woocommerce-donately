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
 
// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'mnzn_donately_init', 0 );
function mnzn_donately_init() {
    // If the parent WC_Payment_Gateway class doesn't exist
    // it means WooCommerce is not installed on the site
    // so do nothing
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
     
    // If we made it this far, then include our Gateway Class
    include_once( 'includes/woocommerce-donately.php' );
 
    // Now that we have successfully included our class,
    // Lets add it too WooCommerce
    add_filter( 'woocommerce_payment_gateways', 'mnzn_donately_gateway' );
    function mnzn_donately_gateway( $methods ) {
        $methods[] = 'MNZN_Donately';
        return $methods;
    }
}
 
// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'mnzn_donately_action_links' );
function mnzn_donately_action_links( $links ) {
    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'spyr-authorizenet-aim' ) . '</a>',
    );
 
    // Merge our new link with the default ones
    return array_merge( $plugin_links, $links );    
}



/**
 * Add the field to the checkout
 */
add_action( 'woocommerce_after_order_notes', 'mnzn_donately_checkout_fields' );
 
function mnzn_donately_checkout_fields( $checkout ) {
    
    $dntly_settings = get_option( 'woocommerce_mnzn_donately_settings' );

    $anonymous = $dntly_settings['anonymous'];
    $onbehalf = $dntly_settings['onbehalf'];
    // echo '<pre>';
    // print_r( $dntly_settings );
    // echo '</pre>';
    
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

