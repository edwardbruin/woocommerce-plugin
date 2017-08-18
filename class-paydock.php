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
            $this->icon         = WP_PLUGIN_URL . '/woocommerce-gateway-paydock/assets/images/logo.png';

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

        public function getOneTimeToken($uniqueVarName){
            // error_log('what is order id?');
            // error_log($order_id);
            // $order = wc_get_order( $order_id );
            // $order = WC()->order->id;
            // error_log(WC()->order->id);
            // error_log($order);
            //if there is no paydock token tied to the $order_id, this function will generate one and then return it
            //if there is a paydock token tied to the $order_id, this function will return it


            // error_log("$uniqueVarName");
            // error_log($uniqueVarName);
            $postfields = json_encode( array(
                    'type'              => 'checkout_token',
                    'gateway_id'        => $this->gateway_id,
                    'checkout_token'    => $uniqueVarName
            ));
            // error_log("$postfields");
            // error_log($postfields);

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
            // error_log(implode(", ", $args));
            // error_log($this->api_endpoint . 'v1/payment_sources/tokens?public_key=' . $this->public_key);
            $result = wp_remote_post( $this->api_endpoint . 'v1/payment_sources/tokens?public_key=' . $this->public_key, $args );
            // error_log(implode(", ", $result));
            if ( !empty( $result['body'] ) ) {
                $res= json_decode( $result['body'], true );
                if ( !empty( $res['resource']['data'] ) && 'token' == $res['resource']['type'] ) {

                    $tokenToReturn = $res['resource']['data'];
                    // error_log($tokenToReturn);

                } elseif ( !empty( $res['error']['message'] ) ) {

                    throw new Exception( $res['error']['message'] );
                }
            }
            return $tokenToReturn;
        }


        /**
         * payment_scripts function.
         *
         * Outputs scripts used for simplify payment
         */
        public function payment_scripts() {
            if ( ! is_checkout() || ! $this->is_available() ) {
                return '';
            }
            // error_log( wp_get_current_user()->user_email );
            // error_log( WC()->cart->get_cart_id);
            // $customer = (new WC_Session_Handler)->get_session_data();
            
            // error_log( WC()->cart->get_cart_total);
            // error_log( WC()->cart->cart_contents_total);
            
            // error_log( WC()->cart->total);
            // error_log( WC()->cart->subtotal);
            // error_log( WC()->cart->tax_total);
            // error_log( WC()->cart->taxes);
            // error_log( WC()->cart->subtotal_ex_tax);
            // error_log( WC()->cart->cart_contents_total);

            global $wpdb;
            if (isset ($_GET["order-received"])){
                return '';
            } else if (isset ($_GET["status"]) && $_GET["status"] == "SUCCESS"){
                // error_log('token and link already generated, skipping process');
                
                // $table_name = $wpdb->prefix . "paydockdata";
                // error_log($table_name);
                // $updated = $wpdb->get_row( "SELECT * FROM " . $table_name );
                // error_log("updated");

                $testcheckoutlink = WC()->session->get("APlink");
                $testcheckouttoken = WC()->session->get("APtoken");
                $testtoken = WC()->session->get("PDtoken");
            } else {
                $currentuser = wp_get_current_user();
                $bodymeta = array(
                    // 'amount'        => '50.00',
                    'amount'        => WC()->cart->subtotal,
                    'currency'      => strtoupper( get_woocommerce_currency() ),
                    'email'         => $currentuser->user_email,
                    'first_name'    => $currentuser->user_firstname,
                    'last_name'     => $currentuser->user_lastname
                );
                // error_log($bodymeta['amount']);
                // $checkout_url = WC_Cart::get_checkout_url();
                $checkout_url = (new WC_Cart)->get_checkout_url();
                // error_log($checkout_url);

                // error_log($checkout_url);

                $postfields = json_encode( array(
                        'error_redirect_url'    => $checkout_url,
                        'success_redirect_url'  => $checkout_url,
                        'gateway_id'            => $this->gateway_id,
                        'meta'                  => $bodymeta
                ));
                // error_log($postfields);

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
                // error_log(implode(", ", $args));

                $result1 = wp_remote_post( $this->api_endpoint . 'v1/payment_sources/external_checkout', $args );

                if ( !empty( $result1['body'] ) ) {
                    $res= json_decode( $result1['body'], true );
                    if ( !empty( $res['resource']['data'] ) && 'payment_source' == $res['resource']['type'] ) {
                        if ( !empty( $res['resource']['data']['link'] ) && !empty( $res['resource']['data']['token'] ) ) {

                            $testcheckoutlink = $res['resource']['data']['link'];
                            $testcheckouttoken = $res['resource']['data']['token'];
                            // error_log($testcheckoutlink);
                            // error_log($testcheckouttoken);
                            $testtoken = $this->getOneTimeToken($testcheckouttoken);
                            // error_log($oneTimeToken);

                            WC()->session->set("PDtoken", $testtoken);
                            WC()->session->set("APtoken", $testcheckouttoken);
                            WC()->session->set("APlink", $testcheckoutlink);
                        }

                    } elseif ( !empty( $res['error']['message'] ) ) {

                        throw new Exception( $res['error']['message'] );
                    }
                }
            }

            // error_log(" ");
            // error_log(implode(", ", $result));
            // error_log(" ");
            // error_log($this->api_endpoint);

            // wp_enqueue_script( 'js-paydock', 'https://app.paydock.com/v1/paydock.min.js', array(), $this->js_ver, true );
            // wp_deregister_script('jquery');
            // wp_register_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js', false, '3.2.1');
            // wp_enqueue_script('jquery');
            // wp_enqueue_script( 'jquery2', 'https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js', array(), true, true );
            wp_enqueue_script( 'paydock-token', WP_PLUGIN_URL . '/woocommerce-gateway-paydock/assets/js/paydock_token.js', array(), time(), true );
            wp_localize_script( 'paydock-token', 'paydock', array(
                'publicKey'         => $this->public_key,
                'gatewayId'         => $this->gateway_id,
                'testcheckoutlink'  => $testcheckoutlink,
                'testcheckouttoken' => $testcheckouttoken,
                'testtoken'         => $testtoken,
                'sandbox'           => 'sandbox' == $this->mode ? true : false,
            ) );

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
            // error_log($order_id);
            // error_log("process_payment has been called");
            // error_log("process_payment has been called");
            // error_log("process_payment has been called");
            // error_log($_POST['paydockToken']);

            $order = wc_get_order( $order_id );

            $item_name = sprintf( __( 'Order %s from %s.', WOOPAYDOCKTEXTDOMAIN ), $order->get_order_number(), urlencode( remove_accents( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ) ) );

            try {

                //make sure token is set at this point
                if ( !isset( $_POST['paydockToken'] ) || empty( $_POST['paydockToken'] ) ) {
                    throw new Exception( __( 'The PayDoc Token was not generated correctly. Please go back and try again.', WOOPAYDOCKTEXTDOMAIN ) );
                }

                $postfields = json_encode( array(
                    'amount'        => (float)$order->get_total(),
                    'currency'      => strtoupper( get_woocommerce_currency() ),
                    // 'reference'     => $item_name,
                    // 'description'   => $item_name,
                    // 'amount'        => '50.00',
                    // 'currency'      => 'AUD',
                    'token'         => $_POST['paydockToken'],
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
                error_log('sending token');
                $result = wp_remote_post( $this->api_endpoint . 'v1/charges', $args );
                error_log('sent token');

                if ( !empty( $result['body'] ) ) {
                    error_log('body token is present');

                    $res= json_decode( $result['body'], true );

                    if ( !empty( $res['resource']['type'] ) && 'charge' == $res['resource']['type'] ) {
                        error_log('type is present');
                        if ( !empty( $res['resource']['data']['status'] ) && 'complete' == $res['resource']['data']['status'] ) {
                            error_log('status is present');

                            $order->payment_complete( $res['resource']['data']['_id'] );

                            // Remove cart
                            WC()->cart->empty_cart();

                            return array(
                                'result'   => 'success',
                                'redirect' => $this->get_return_url( $order )
                            );

                        } else {
                            error_log('payment failed lol');
                        }

                    } elseif ( !empty( $res['error']['message'] ) ) {

                        throw new Exception( $res['error']['message'] );
                    }
                }

                throw new Exception( __( 'Unknown error', WOOPAYDOCKTEXTDOMAIN ) );

            } catch( Exception $e ) {

                wc_add_notice( __( 'Error:', WOOPAYDOCKTEXTDOMAIN ) . ' ' . $e->getMessage(), 'error' );
            }

            return '';
        }

        //class end
    }
}