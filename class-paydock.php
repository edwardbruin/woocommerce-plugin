<?php
/**
 * WCPayDockGateway
 */
if ( !class_exists( 'WCPayDockGateway' ) ) {

    class WCPayDockGateway extends WC_Payment_Gateway_CC {

        /**
         * Constructor
         */
        public function __construct() {

            $this->currency_list = array( 'AUD', 'USD', 'GBP', 'EUR', 'JPY', 'CAD', 'CHF', 'NZD' );
            $this->js_ver = '1.0.2';
            $this->method_title = 'PayDock';
            $this->id           = 'paydock';
            $this->has_fields   = true;
            // $this->icon         = WP_PLUGIN_URL . '/woocommerce-gateway-paydock/assets/images/logo.png';

            // Load the form fields.
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();

            // Define user set variables
            $this->enabled                  = $this->settings['enabled'];
            $this->title                    = trim( $this->settings['title'] );
            $this->description              = trim( $this->get_option( 'description' ) );
            $this->mode                     = $this->settings['sandbox'] == 'yes' ? 'sandbox' : 'production';

            if ( 'sandbox' == $this->mode )    {
                $this->api_endpoint     = "https://api-sandbox.paydock.com/";
                $this->secret_key       = trim( $this->settings['sandbox_secret_key'] );
                $this->public_key       = trim( $this->settings['sandbox_public_key'] );
                $this->gateway_id       = trim( $this->settings['sandbox_gateway_id'] );
            } else {
                $this->api_endpoint  = "https://api.paydock.com/";
                $this->secret_key    = trim( $this->settings['production_secret_key'] );
                $this->public_key    = trim( $this->settings['production_public_key'] );
                $this->gateway_id    = trim( $this->settings['production_gateway_id'] );
            }


            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
        }


        /**
         * init_form_fields function.
         *
         * @access public
         * @return void
         */
        function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title'       => __( 'Enable/Disable', WOOPAYDOCKTEXTDOMAIN ),
                    'label'       => __( 'Enable PayDock', WOOPAYDOCKTEXTDOMAIN ),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => __( 'Title', WOOPAYDOCKTEXTDOMAIN ),
                    'type'        => 'text',
                    'description' => __( 'Payment method title that the customer will see on your website.', WOOPAYDOCKTEXTDOMAIN ),
                    'default'     => __( 'PayDock', WOOPAYDOCKTEXTDOMAIN ),
                    'desc_tip'    => true
                ),
                'description' => array(
                    'title'       => __( 'Description', WOOPAYDOCKTEXTDOMAIN ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your checkout.', WOOPAYDOCKTEXTDOMAIN ),
                    'default'     => __( 'Pay securely by Credit through PayDock Secure Servers.', WOOPAYDOCKTEXTDOMAIN ),
                    'desc_tip'    => true,
                ),
                'production_secret_key' => array(
                    'title'       => __( 'Production Secret Key', WOOPAYDOCKTEXTDOMAIN ),
                    'type'        => 'text',
                    'description' => __( 'Obtained from your PayDock account. You can set this key by logging into PayDock.', WOOPAYDOCKTEXTDOMAIN ),
                    'default'     => '',
                    'desc_tip'    => true
                ),
                'production_public_key' => array(
                    'title'       => __( 'Production Public Key', WOOPAYDOCKTEXTDOMAIN ),
                    'type'        => 'text',
                    'description' => __( 'Obtained from your PayDock account. You can set this key by logging into PayDock.', WOOPAYDOCKTEXTDOMAIN ),
                    'default'     => '',
                    'desc_tip'    => true
                ),
                'production_gateway_id' => array(
                    'title'       => __( 'Production Gateway ID', WOOPAYDOCKTEXTDOMAIN ),
                    'type'        => 'text',
                    'description' => __( 'Obtained from your PayDock account. You can set this ID by logging into PayDock.', WOOPAYDOCKTEXTDOMAIN ),
                    'default'     => '',
                    'desc_tip'    => true
                ),
                'sandbox' => array(
                    'title'       => __( 'Use Sandbox', WOOPAYDOCKTEXTDOMAIN ),
                    'label'       => __( 'Enable sandbox mode during testing and development - live payments will not be taken if enabled.', WOOPAYDOCKTEXTDOMAIN ),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'sandbox_secret_key' => array(
                    'title'       => __( 'Sandbox Secret Key', WOOPAYDOCKTEXTDOMAIN ),
                    'type'        => 'text',
                    'description' => __( 'Obtained from your PayDock account. You can set this key by logging into PayDock.', WOOPAYDOCKTEXTDOMAIN ),
                    'default'     => '',
                    'desc_tip'    => true
                ),
                'sandbox_public_key' => array(
                    'title'       => __( 'Sandbox Public Key', WOOPAYDOCKTEXTDOMAIN ),
                    'type'        => 'text',
                    'description' => __( 'Obtained from your PayDock account. You can set this key by logging into PayDock.', WOOPAYDOCKTEXTDOMAIN ),
                    'default'     => '',
                    'desc_tip'    => true
                ),
                'sandbox_gateway_id' => array(
                    'title'       => __( 'Sandbox Gateway ID', WOOPAYDOCKTEXTDOMAIN ),
                    'type'        => 'text',
                    'description' => __( 'Obtained from your PayDock account. You can set this ID by logging into PayDock.', WOOPAYDOCKTEXTDOMAIN ),
                    'default'     => '',
                    'desc_tip'    => true
                ),
            );
        }


        /**
         * Check If The Gateway Is Available For Use
         *
         * @access public
         * @return bool
         */
        function is_available() {

            if ( 'yes' == $this->enabled && in_array( strtoupper( get_woocommerce_currency() ), $this->currency_list )
                && !empty( $this->secret_key ) && !empty( $this->public_key ) && !empty( $this->gateway_id ) ) {
                return true;
            }

            return false;
        }


        /**
         * Payment form on checkout page
         */
        public function payment_fields() {
            if ( $this->has_fields ) {

                if ( $description = $this->get_description() ) {
                    echo wpautop( wptexturize( $description ) );
                }

                $this->supports[] = 'tokenization';

                $this->form();
            }
        }

        public function form() {
            echo('<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="display:none;"></fieldset>');

            if ( (WC()->session->get("limitExceeded")==true) ) { 
                $this->sendUnavailableNotice();
            } else {
                try {
                    if (is_user_logged_in()) {
                        $this->getAPlinkToken();
                    } else {
                        echo('<div>Afterpay is not available until you have logged in</div>');
                    }
                } catch (Exception $e) {
                    error_log("exception detected6");
                    $this->sendUnavailableNotice();
                }
            }
            echo('<div class="clear"></div>');
            return '';
        }

        public function getAPlinkToken(){
            if ((isset ($_GET["status"]) && $_GET["status"] == "SUCCESS") && (WC()->session->get("APtotal") == WC()->cart->total) ){
                $this->PostApproval();
            } else {
                $this->PreApproval();
            }

            wp_enqueue_script( 'paydock-token', WP_PLUGIN_URL . '/woocommerce-gateway-paydock/assets/js/paydock_token.js', array(), time(), true );

            return '';
        }

        public function sendUnavailableNotice(){
            echo('<div>Afterpay is not available for this transaction</div>');
        }

        public function PostApproval(){
            echo('<div id="AP_message">Afterpay Approval completed</div>');
            $this->getOneTimeToken();
        }

        public function PreApproval(){
            $currentuser = wp_get_current_user();
            $bodymeta = array(
                'amount'        => WC()->cart->total,
                'currency'      => strtoupper( get_woocommerce_currency() ),
                'email'         => $currentuser->user_email,
                'first_name'    => empty($currentuser->user_firstname) ? 'First_name' : $currentuser->user_firstname,
                'last_name'     => empty($currentuser->user_lastname) ? 'First_name' : $currentuser->user_lastname
            );
            $checkout_url = (new WC_Cart)->get_checkout_url();

            $postfields = json_encode( array(
                    'error_redirect_url'    => $checkout_url,
                    'success_redirect_url'  => $checkout_url,
                    'gateway_id'            => $this->gateway_id,
                    'meta'                  => $bodymeta
            ));

            $args = array(
                'method'        => 'POST',
                'timeout'       => 45,
                'httpversion'   => '1.0',
                'blocking'      => true,
                'sslverify'     => false,
                'body'          => $postfields,
                'headers'       => array(
                    'Content-Type'      => 'application/json',
                    'x-user-secret-key' => $this->secret_key,
                ),
            );

            $result1 = wp_remote_post( $this->api_endpoint . 'v1/payment_sources/external_checkout', $args );

            if ( !empty( $result1['body'] ) ) {
                $res= json_decode( $result1['body'], true );
                if ( !empty( $res['resource']['data'] ) && 'payment_source' == $res['resource']['type'] ) {
                    if ( !empty( $res['resource']['data']['link'] ) && !empty( $res['resource']['data']['token'] ) ) {

                        $testcheckoutlink = $res['resource']['data']['link'];
                        $testcheckouttoken = $res['resource']['data']['token'];
                        
                        WC()->session->set("APtoken", $testcheckouttoken);
                        WC()->session->set("APlink", $testcheckoutlink);
                        WC()->session->set("APtotal", WC()->cart->total);
                        echo('<div id="AP_message">Click Afterpay to login for payment</div>');
                        echo('<a id="AP_button" href= ' . WC()->session->get("APlink") . '>
                            <div id=button_div style="vertical-align:middle;display:inline-block;padding:8px;background:rgba(225, 225, 225, .8)">
                                <img style="max-height:unset" src=' . WP_PLUGIN_URL . '/woocommerce-gateway-paydock/assets/images/logo1.png alt="Login to your Afterpay account">
                            </div>
                        </a>');
                    }

                } elseif ( !empty( $res['error']['message'] ) ) {
                    error_log("exception detected5");
                    throw new Exception( $res['error']['message'] );
                }
            }
        }

        public function getOneTimeToken(){
            $token = '';

            $postfields = json_encode( array(
                    'type'              => 'checkout_token',
                    'gateway_id'        => $this->gateway_id,
                    'checkout_token'    => WC()->session->get("APtoken"),
            ));

            $args = array(
                'method'        => 'POST',
                'timeout'       => 45,
                'httpversion'   => '1.0',
                'blocking'      => true,
                'sslverify'     => false,
                'body'          => $postfields,
                'headers'       => array(
                    'Content-Type'      => 'application/json',
                    'x-user-secret-key' => $this->secret_key,
                ),
            );
            $result = wp_remote_post( $this->api_endpoint . 'v1/payment_sources/tokens?public_key=' . $this->public_key, $args );
            if ( !empty( $result['body'] ) ) {
                $res= json_decode( $result['body'], true );
                if ( !empty( $res['resource']['data'] ) && 'token' == $res['resource']['type'] ) {

                    $token = $res['resource']['data'];

                } elseif ( !empty( $res['error']['message'] ) ) {
                    error_log("exception detected4");
                    throw new Exception( $res['error']['message'] );
                }
            }
            WC()->session->set("PDtoken", $token);
            return '';
        }


        /**
         * payment_scripts function.
         *
         * Outputs scripts used for simplify payment
         */
        public function payment_scripts() {
            // this snippet prints all the cart's items and prices, to be used later

            // $items = WC()->cart->get_cart();
            // foreach ($items as $item => $values) {
            //     $_product = wc_get_product( $values['data']->get_id() );
            //     error_log($_product->get_title() .' Quantity: '.$values['quantity']); 
            //     $price = get_post_meta($values['product_id'] , '_price', true);
            //     error_log("Price: ".$price);
            // }
            WC()->session->set("limitExceeded",  false);

            $args = array(
                'method'        => 'GET',
                'timeout'       => 45,
                'httpversion'   => '1.0',
                'blocking'      => true,
                'sslverify'     => false,
                'headers'       => array(
                    'Content-Type'      => 'application/json',
                    'x-user-secret-key' => $this->secret_key,
                ),
            );

            $result = wp_remote_post( $this->api_endpoint . 'v1/gateways/' . $this->gateway_id . '/config', $args );
            if ( !empty( $result['body'] ) ) {
                $res= json_decode( $result['body'], true );
                if ( !empty( $res['resource']['data'] ) && 'configs' == $res['resource']['type'] ) {

                    $maxamount = $res['resource']['data'][0]['maximumAmount']['amount'];
                    $maxcurrency = $res['resource']['data'][0]['maximumAmount']['currency'];

                } elseif ( !empty( $res['error']['message'] ) ) {

                    throw new Exception( $res['error']['message'] );
                }
            }

            if (WC()->cart->subtotal >= $maxamount) {

                WC()->session->set("limitExceeded",  true);
            }

            return '';
        }

        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis
         */
        function admin_options() {

            if ( 'yes' == $this->enabled && 'sandbox' == $this->mode ) { ?>

                <div class="updated woocommerce-message">
                    <div class="squeezer">
                        <h4><?php _e( 'Note: Now PayDock working in Sandbox mode.', WOOPAYDOCKTEXTDOMAIN ); ?></h4>
                    </div>
                </div>

                <?php
            }

            if ( ! in_array( strtoupper( get_woocommerce_currency() ), $this->currency_list ) ) { ?>

                <div class="error woocommerce-message">
                    <div class="squeezer">
                        <h4>
                            <?php echo __( 'Note: PayDock support only next currencies:', WOOPAYDOCKTEXTDOMAIN ) . ' ' . implode( ', ', $this->currency_list ) ?>
                        </h4>
                    </div>
                </div>

                <?php
            }
            ?>

            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
            </table><!--/.form-table-->
            <?php
        }

        /**
         * Process the payment and return the result.
         *
         * @since 1.0.0
         */
        function process_payment( $order_id ) {

            $order = wc_get_order( $order_id );

            if ( (float)$order->get_total() != WC()->session->get("APtotal") ) {
                wc_add_notice( __( 'Error:', WOOPAYDOCKTEXTDOMAIN ) . ' The order total has changed, <a href=' . (new WC_Cart)->get_checkout_url() .'><strong>please click here and try again</strong></a>', 'error' );
            } else {

                $item_name = sprintf( __( 'Order %s from %s.', WOOPAYDOCKTEXTDOMAIN ), $order->get_order_number(), urlencode( remove_accents( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ) ) );

                try {
                    // make sure token is set at this point
                    if ( !isset( $_POST['confirmStatus'] ) || !( $_POST['confirmStatus'] == "paymentready") ){
                        error_log("exception detected3");
                        throw new Exception( __( 'The PayDock Token was not generated correctly. Please go back and try again.', WOOPAYDOCKTEXTDOMAIN ) );
                    }
                    $testtoken = WC()->session->get("PDtoken");

                    $postfields = json_encode( array(
                        'amount'        => (float)$order->get_total(),
                        'currency'      => strtoupper( get_woocommerce_currency() ),
                        'reference'     => $item_name,
                        'description'   => $item_name,
                        'token'         => $testtoken,
                    ));

                    $args = array(
                        'method'        => 'POST',
                        'timeout'       => 45,
                        'httpversion'   => '1.1',
                        'blocking'      => true,
                        'sslverify'     => false,
                        'body'          => $postfields,
                        'headers'       => array(
                            'Content-Type'      => 'application/json',
                            'x-user-secret-key' => $this->secret_key,
                        ),
                    );
                    $result = wp_remote_post( $this->api_endpoint . 'v1/charges', $args );
                    
                    if ( !empty( $result['body'] ) ) {

                        $res= json_decode( $result['body'], true );

                        if ( !empty( $res['resource']['type'] ) && 'charge' == $res['resource']['type'] ) {
                            if ( !empty( $res['resource']['data']['status'] ) && 'complete' == $res['resource']['data']['status'] ) {

                                $order->payment_complete( $res['resource']['data']['_id'] );

                                // Remove cart
                                WC()->cart->empty_cart();

                                return array(
                                    'result'   => 'success',
                                    'redirect' => $this->get_return_url( $order )
                                );

                            }

                        } elseif ( !empty( $res['error']['message'] ) ) {
                            error_log("exception detected2");
                            throw new Exception( $res['error']['message'] );
                        }
                    } else {
                        error_log("exception detected1");
                        throw new Exception( __( 'Unknown error', WOOPAYDOCKTEXTDOMAIN ) );
                    }

                } catch( Exception $e ) {
                    error_log("exception caught");
                    wc_add_notice( __( 'Error:', WOOPAYDOCKTEXTDOMAIN ) . ' ' . $e->getMessage(), 'error' );
                }
            }

            return '';
        }

        //class end
    }
}