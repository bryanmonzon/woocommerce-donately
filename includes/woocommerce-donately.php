<?php
if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

/* Donately AIM Payment Gateway Class */
class MNZN_Donately extends WC_Payment_Gateway {
 
    // Setup our Gateway's id, description and other values
    function __construct() {
 
        // The global ID for this Payment method
        $this->id = "mnzn_donately";
 
        // The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
        $this->method_title = __( "Donately", 'mnzn-donately' );
 
        // The description for this Payment Gateway, shown on the actual Payment options page on the backend
        $this->method_description = __( "Donately payment gateway for WooCommerce", 'mnzn-donately' );
 
        // The title to be used for the vertical tabs that can be ordered top to bottom
        $this->title = __( "Donately", 'mnzn-donately' );
 
        // If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
        $this->icon = null;
 
        // Bool. Can be set to true if you want payment fields to show on the checkout 
        // if doing a direct integration, which we are doing in this case
        $this->has_fields = true;
 
        // Supports the default credit card form
        $this->supports = array( 'default_credit_card_form' );
 
        // This basically defines your settings which are then loaded with init_settings()
        $this->init_form_fields();
 
        // After init_settings() is called, you can get the settings and load them into variables, e.g:
        // $this->title = $this->get_option( 'title' );
        $this->init_settings();
         
        // Turn these settings into variables we can use
        foreach ( $this->settings as $setting_key => $value ) {
            $this->$setting_key = $value;
        }
         
        // Lets check for SSL
        add_action( 'admin_notices', array( $this,  'do_ssl_check' ) );
         
        // Save settings
        if ( is_admin() ) {
            // Versions over 2.0
            // Save our administration options. Since we are not going to be doing anything special
            // we have not defined 'process_admin_options' in this class so the method in the parent
            // class will be used instead
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }       
    } // End __construct()
 
    // Build the administration fields for this specific Gateway
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'     => __( 'Enable / Disable', 'mnzn-donately' ),
                'label'     => __( 'Enable this payment gateway', 'mnzn-donately' ),
                'type'      => 'checkbox',
                'default'   => 'no',
            ),
            'title' => array(
                'title'     => __( 'Title', 'mnzn-donately' ),
                'type'      => 'text',
                'desc_tip'  => __( 'Payment title the customer will see during the checkout process.', 'mnzn-donately' ),
                'default'   => __( 'Credit card', 'mnzn-donately' ),
            ),
            'description' => array(
                'title'     => __( 'Description', 'mnzn-donately' ),
                'type'      => 'textarea',
                'desc_tip'  => __( 'Payment description the customer will see during the checkout process.', 'mnzn-donately' ),
                'default'   => __( 'Pay securely using your credit card.', 'mnzn-donately' ),
                'css'       => 'max-width:350px;'
            ),
            'dntly_subdomain' => array(
                'title'     => __( 'Donately Subdomain', 'mnzn-donately' ),
                'type'      => 'text',
                'desc_tip'  => __( 'This is the subdomain on your Donately account.', 'mnzn-donately' ),
            ),
            'dntly_key' => array(
                'title'     => __( 'Donately API Key', 'mnzn-donately' ),
                'type'      => 'text',
                'desc_tip'  => __( 'This is the API key provided by Donately when you signed up for an account.', 'mnzn-donately' ),
            ),
            'disable_email' => array(
                'title'       => __( 'Disable Donately Emails', 'mnzn-donately' ),
                'label'       => __( 'Disable Donately Emails', 'mnzn-donately' ),
                'type'        => 'checkbox',
                'desc_tip' => __( 'Disable the donation receipt email that Donately sends. (Create account still sends)', 'mnzn-donately' ),
                'default'     => 'no',
            ),
            'anonymous' => array(
                'title'       => __( 'Allow Anonymous Donations', 'mnzn-donately' ),
                'label'       => __( 'Allow Anonymous Donations', 'mnzn-donately' ),
                'type'        => 'checkbox',
                'desc_tip' => __( 'Check the box to allow people to donate anonymously.', 'mnzn-donately' ),
                'default'     => 'no',
            ),
            'onbehalf' => array(
                'title'       => __( 'Donating on behalf', 'mnzn-donately' ),
                'label'       => __( 'Enable donating on behalf of someone', 'mnzn-donately' ),
                'type'        => 'checkbox',
                'desc_tip' => __( 'Check the box to allow people to donate on behalf of someone.', 'mnzn-donately' ),
                'default'     => 'no',
            ),
            'environment' => array(
                'title'     => __( 'Donately Test Mode', 'mnzn-donately' ),
                'label'     => __( 'Enable Test Mode', 'mnzn-donately' ),
                'type'      => 'checkbox',
                'description' => __( 'Place the payment gateway in test mode.', 'mnzn-donately' ),
                'default'   => 'no',
            )
        );      
    }
     
    // Submit payment and handle response
    public function process_payment( $order_id ) {
        global $woocommerce;
         
        // Get this Order's information so that we know
        // who to charge and how much
        $customer_order = new WC_Order( $order_id );
         
        // Are we testing right now or is it a real transaction
        $environment = ( $this->environment == "yes" ) ? 'TRUE' : 'FALSE';
        $subdomain = ( isset( $this->dntly_subdomain ) ) ? $this->dntly_subdomain : NULL;
        $api_endpoint = "/api/v1/";
        $method       = 'accounts/'. $subdomain .'/donate_without_auth';
 
        // Decide which URL to post to
        $environment_url = ( "FALSE" == $environment ) 
                           ? 'https://'. $subdomain .'.dntly.com' . $api_endpoint . $method
                           : 'https://demo.dntly.com/' . $api_endpoint . $method;


        $exp_date  = explode( '/', $_POST['mnzn_donately-card-expiry'] );
        $exp_month = isset( $exp_date[0] ) ? $exp_date[0] : '';
        $exp_year  = isset( $exp_date[1] ) ? $exp_date[1] : '';

        $disable_email = ( $this->disable_email == "yes" ) ? "true" : "false";
        $onbehalf      = isset( $_POST['dntly_onbehalf'] ) ? $_POST['dntly_onbehalf'] : null;
        $anonymous     = isset( $_POST['dntly_anonymous'] ) ? "true" : "false";
        $comment       = isset( $_POST['order_comments'] ) ? $_POST['order_comments'] : null;
 
        // This is where the fun stuff begins
        $payload = array(
            // Donately Credentials and API Info
             // Order total
             "amount_in_cents"             => ($customer_order->order_total ) * 100,
             
             // Credit Card Information
             "card"                  => array(
                "number"             => str_replace( array(' ', '-' ), '', $_POST['mnzn_donately-card-number'] ),
                "cvc"                => ( isset( $_POST['mnzn_donately-card-cvc'] ) ) ? $_POST['mnzn_donately-card-cvc'] : '',
                "exp_month"          => $exp_month,
                "exp_year"           => $exp_year
            ),    
             
            
             // Billing Information
             "first_name"              => $customer_order->billing_first_name,
             "last_name"               => $customer_order->billing_last_name,
             "street_address"          => $customer_order->billing_address_1,
             "street_address2"         => $customer_order->billing_address_2,
             "city"                    => $customer_order->billing_city,
             "state"                   => $customer_order->billing_state,
             "zip"                     => $customer_order->billing_postcode,
             "country"                 => $customer_order->billing_country,
             "phone"                   => $customer_order->billing_phone,
             "email"                   => $customer_order->billing_email,
             
             "dont_send_receipt_email" => $disable_email,
             "on_behalf_of"            => $onbehalf,
             "anonymous"               => $anonymous,
             "comment"                 => $comment,
             
             
             // Some Customer Information
             "dump"            => 'Customer ID in WooCommerce:' . $customer_order->user_id,
             
        );
     
        // Send this payload to Donately for processing
        $response = wp_remote_post( $environment_url, array(
            'method'      => 'POST',
            'timeout'     => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'body'        => $payload,
            'cookies'     => array(),
        ) );


 
        if ( is_wp_error( $response ) ) 
            throw new Exception( __( 'We are currently experiencing problems trying to connect to this payment gateway. Sorry for the inconvenience.', 'mnzn-donately' ) );
 
        if ( empty( $response['body'] ) )
            throw new Exception( __( 'Donately\'s Response was empty.', 'mnzn-donately' ) );
             
        // Retrieve the body's resopnse if no errors found
        $response_body = wp_remote_retrieve_body( $response );

        $response_body = json_decode( $response_body );

        $donation_url = '<a href="https://'.$subdomain.'.dntly.com/admin/app#/donations/edit/' . $response_body->donation->id .'" target="_blank" title="View this donation on Donately">'. $response_body->donation->id . '</a>';


        // Test the code to know if the transaction went through or not.
        // 1 or 4 means the transaction was a success
        if ( ( $response_body->success ) ) {
            // Payment has been successful
            $customer_order->add_order_note( __( 'Donately payment completed.', 'mnzn-donately' ) );
            $customer_order->add_order_note(  __( 'Donately Donation ID: '. $donation_url , 'mnzn-donately' ) );

            if( $anonymous == 'true'){
                $customer_order->add_order_note(  __( 'Donated anonymously' , 'mnzn-donately' ) );                
            }

            if( $onbehalf ){
                $customer_order->add_order_note(  __( 'Donated on behalf of: '. $onbehalf , 'mnzn-donately' ) );
            }
            // Mark order as Paid
            $customer_order->payment_complete();
 
            // Empty the cart (Very important step)
            $woocommerce->cart->empty_cart();
 
            // Redirect to thank you page
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $customer_order ),
            );
        } else {
            // Transaction was not succesful
            // Add notice to the cart
            wc_add_notice( $response_body->error->message, 'error' );
            // Add note to the order for your reference
            $customer_order->add_order_note( 'Error: '. $response_body->error->message );
        }
 
    }
     
    // Validate fields
    public function validate_fields() {
        return true;
    }
     
    // Check if we are forcing SSL on checkout pages
    // Custom function not required by the Gateway
    public function do_ssl_check() {
        if( $this->enabled == "yes" ) {
            if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
                echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";   
            }
        }       
    }
 
} // End of MNZN_Donately