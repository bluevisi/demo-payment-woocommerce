
<?php
 class WC_LunaPay_Gateway extends WC_Payment_Gateway {

    /**
     * Class constructor, more about it in Step 3
     */
    public function __construct() {
        $this->id = 'lunapay'; // payment gateway plugin ID
        $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields = true; // in case you need a custom credit card form
        $this->method_title = 'WooCommerce LunaPay';
        $this->method_description = 'Description of LunaPay Woo payment gateway'; // will be displayed on the options page

        // gateways can support subscriptions, refunds, saved payment methods,
        // but in this tutorial we begin with simple payments
        $this->supports = array(
            'products'
        );

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->enabled = $this->get_option( 'enabled' );
        $this->api_url = $this->get_option( 'api_url' );
        $this->host_key = $this->get_option( 'host_key' );
 

        // This action hook saves the settings
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        // We need custom JavaScript to obtain a token
        // add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
        // You can also register a webhook here
        add_action( 'woocommerce_api_processPayment', array( $this, 'processPayment_api' ) );

    }

    /**
     * Plugin options, we deal with it in Step 3 too
     */
    public function init_form_fields(){
        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable LunaPay Payment Processor',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => 'Credit Card',
                'desc_tip'    => true,
            ),
            'api_url' => array(
                'title'       => 'Api Url',
                'type'        => 'text'
            ),
            'host_key' => array(
                'title'       => 'Host Key',
                'type'        => 'text'
            )
        );
    }
    
    /*
     * We're processing the payments here, everything about it is in Step 5
     */
    public function process_payment( $order_id ) {
        global $woocommerce;

        $order = new WC_Order( $order_id );
        $order_total = $order->get_total();

        // Sanitize and validate the input data
        $billingDetails = [
            'first_name' => sanitize_text_field($_REQUEST['billing_first_name']),
            'last_name'  => sanitize_text_field($_REQUEST['billing_last_name']),
            'email'      => sanitize_email($_REQUEST['billing_email']),
        ];
    
        $cardDetails = [
            'number'     => sanitize_text_field($_REQUEST['card_number']),
            'expiration' => sanitize_text_field($_REQUEST['expiration']),
            'cvv'        => sanitize_text_field($_REQUEST['cvv_code']),
        ];

        $customerName = $billingDetails['first_name'] . ' ' . $billingDetails['last_name'];

        // Validate input fields
        if (!$this->validate_fields($billingDetails)) {
            wc_add_notice('Invalid billing details. Please check your information and try again.', 'error');
            return;
        }

        // Validate input fields
        if (!$this->validate_fields($cardDetails)) {
            wc_add_notice('Invalid payment details. Please check your information and try again.', 'error');
            return;
        }
        
        $host_key    = $this->host_key;
        $apiEndpoint = $this->api_url;
        $callbackUrl = $this->get_return_url($order);

        $paymentPayload = array(
            'billingData' => $billingDetails,
            'paymentData' => $cardDetails,
        );
    

        if (!empty($apiEndpoint)) {
            try {
                $response = wp_remote_post($apiEndpoint, [
                    'method'      => 'POST',
                    'timeout'     => 45,
                    'blocking'    => true,
                    'body'        => $paymentPayload,
                    'httpversion' => '1.0'
                ]);
    
                $decodedResponse = json_decode(wp_remote_retrieve_body($response));
    
                if ($decodedResponse->status === 1) {
                    return $this->handlePaymentSuccess($order, $decodedResponse, $callbackUrl);
                } else {
                    throw new Exception($decodedResponse->message);
                }
            } catch (Exception $e) {
                wc_add_notice($e->getMessage(), 'error');
            }
        } else {
            wc_add_notice('API endpoint missing, contact administrator.', 'error');
        }
    
        return;
    }
 }
