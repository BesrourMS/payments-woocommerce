<?php
/**
 * Plugin Name: Flouci Payment for WooCommerce
 * Plugin URI: https://app.flouci.com
 * Description: Flouci Payment for WooCommerce
 * Version: 1.0.1
 * Author: Flouci
 * Author URI: https://flouci.com
 * Contributors: Flouci
 * Requires at least: 4.0
 * Tested up to: 5.8
 *
 * Text Domain: flouci-payment-gateway
 * Domain Path: /lang/
 *
 * @package Flouci Gateway for WooCommerce
 * @author Flouci
 */
add_action('plugins_loaded', 'init_flouci_gateway', 0);

function init_flouci_gateway()
{
    if (! class_exists('WC_Payment_Gateway')) {
        return;
    }

    load_plugin_textdomain('flouci-payment-gateway', false, dirname(plugin_basename(__FILE__)) . '/lang');

    class wc_gateway_flouci extends WC_Payment_Gateway
    {
        public function __construct() {
            global $woocommerce;

            $this->id			= 'wc_gateway_flouci';
            $this->method_title = __('Pay with Flouci', 'flouci-payment-gateway');
            $this->icon			= apply_filters('wc_gateway_flouci_icon', 'flouci-banner.png');
            $this->has_fields 	= false;

            $this->init_form_fields();
            $this->init_settings();

            $this->title = "Flouci";
            $this->description = "Payer par cartes bancaire, par carte e-dinar ou par wallet Flouci";
            $this->app_token  = $this->settings['app_token'];
            $this->app_secret = $this->settings['app_secret'];
            $this->accept_card = $this->settings['accept_card'];
            $this->notify_url = add_query_arg('wc-api', 'wc_gateway_flouci', home_url('/')).'&';
            $this->cancel_url = add_query_arg('wc-api', 'wc_gateway_flouci', home_url('/')).'&';

            add_action('init', array( $this, 'successful_request' ));
            add_action('woocommerce_api_wc_gateway_flouci', array( $this, 'successful_request' ));
            add_action('woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ));
            add_action('woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));
        }

        public function get_icon() {
            global $woocommerce;
            $icon = '';
            if ($this->icon) {
                $icon = '<img src="' . plugins_url('images/' . $this->icon, __FILE__)  . '" alt="' . $this->title . '" />';
            }
            return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
        }

        public function admin_options() {
            ?>
	    	<h3><?php _e('', 'flouci-payment-gateway'); ?></h3>
	    	<p>
                <?php _e('
				<div style="float:left;text-align:left;">
                    <a href="https://www.flouci.com" target="_blank"><img style="width:150px;" src="' . plugins_url('images/flouci-logo-with-text.png', __FILE__) . '">
                    <br><br>
                    <a href="https://www.flouci.com" target="_blank">Flouci</a> | <a href="https://flouci.stoplight.com" target="_blank">API Docs</a> |
                    <a href="https://calendly.com/flouci" target="_blank">Contactez-nous</a><br><br>
					</a>
				</div>', 'flouci-payment-gateway'); ?>
            </p>
            <table class="form-table">
	    	  <?php $this->generate_settings_html(); ?>
			</table>
			
	    	<?php
        }

        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Activer/Désactiver', 'flouci-payment-gateway'),
                    'type' => 'checkbox',
                    'label' => __('Activer Flouci', 'flouci-payment-gateway'),
                    'default' => 'yes'
                ),
                'accept_card' => array(
                    'title' => __('Accepter les paiements par cartes bancaires/e-dinar', 'flouci-payment-gateway'),
                    'type' => 'select',
                    'options' => array(
                        'true' => 'Oui',
                        'false' => 'Non'
                    ),
                    'default' => 'true'
                ),
                'app_token' => array(
                    'title' => __('Clé publique', 'flouci-payment-gateway'),
                    'type' => 'text',
                    'description' => __('Clé publique de votre portail developer <a href="https://flouci.stoplight.com" target="_blank">Savoir plus</a> ', 'flouci-payment-gateway'),
                    'default' => ''
                ),
                'app_secret' => array(
                    'title' => __('Clé privée', 'flouci-payment-gateway'),
                    'type' => 'text',
                    'description' => __('Clé privée de votre portail developer <a href="https://flouci.stoplight.com" target="_blank">Savoir plus</a> ', 'flouci-payment-gateway'),
                    'default' => ''
                )
            );
        }

        public function payment_fields() {
            if ($this->description) {
                echo wpautop(wptexturize($this->description));
            }
        }

        public function generate_flouci_form($order_id) {
            session_start();
            global $woocommerce;
            $order = new WC_Order($order_id);
            $payment_url = 'https://developers.flouci.com/api/generate_payment/wordpress';
            
            $amount_millimes = floatval($order->get_total()) * 1000;
            $body = array(
                'app_token' => $this->app_token, 
                'app_secret' => $this->app_secret,
                'accept_card' => $this->accept_card,
                'success_link' =>$this->notify_url,
                'fail_link' => $this->cancel_url,
                'amount' => $amount_millimes,
                'developer_tracking_id' => "transaction-wp-flouci-" . $order_id
            );
            $args = array(
              'method' => 'POST',
              'headers'     => array('Content-type: application/x-www-form-urlencoded'),
              'body'        => $body,
              'timeout'     => '5',
              'blocking'    => true,
              'redirection' => '5',
              'httpversion' => '1.0',
              'cookies'     => array(),
            );
            
            $response = json_decode( wp_remote_retrieve_body( wp_remote_post($payment_url, $args) ), true );
            
            echo json_encode($response);

            WC()->session->set('payment_id', $response['result']['payment_id']);

            wc_enqueue_js('
				jQuery("body").block({
                        message: "<img src=\\"'.plugins_url('images/flouci-logo-with-text.png', __FILE__). '\\" alt=\\"Redirecting...\\"' . ' style=\\"width:150px; float:left; margin-right: 10px;\\" />' . __('We are now redirecting you to Flouci to make the payment.', 'flouci-payment-gateway') . '",
						overlayCSS: {
							background: "#fff",
							opacity: 0.6
						},
						css: {
					        padding:        20,
					        textAlign:      "center",
					        color:          "#555",
					        border:         "3px solid #aaa",
					        backgroundColor:"#fff",
					        cursor:         "wait",
					        lineHeight:		"32px"
					    }
					});
					jQuery("#submit_flouci_payment_form").click();
			');

            return  '<form hidden action="'.esc_url($response['result']['link']).'" method="get" id="flouci_payment_form">
					   <input type="submit" class="button" id="submit_flouci_payment_form" value="'.__('Payer', 'flouci-payment-gateway').'" />
				    </form>';
        }

        public function process_payment($order_id) {
            session_start();
            $order = new WC_Order($order_id);
            $_SESSION['payment_id'] = $order_id;
            return array(
                'result' 	=> 'success',
                'redirect'	=> $order->get_checkout_payment_url(true)
            );
        }

        public function receipt_page($order) {
            echo $this->generate_flouci_form($order);
        }
        
        public function successful_request() {
            session_start();
            global $woocommerce;
            $verification_url = 'https://developers.flouci.com/api/verify_payment/'.WC()->session->get('payment_id');
            $headers = array(
                'apppublic' => $this->app_token,
                'appsecret' => $this->app_secret
            );
            $args = array(
                'headers'     => $headers,
            );
            $response = json_decode( wp_remote_retrieve_body( wp_remote_get($verification_url, $args) ), true );
            
            if ($response['result']['status']== "SUCCESS") {
                echo "<script>console.log(".$this->notify_url.")</script>";
                $order = new WC_Order($_SESSION['payment_id']);
                $order->add_order_note(sprintf(__('Payée avec Flouci. Identifiant de la transaction: %s.', 'flouci-payment-gateway'), WC()->session->get('payment_id')));
                $order->payment_complete();
                wp_redirect($this->get_return_url($order));
                exit;
            }
            wc_add_notice(sprintf(__('Erreur de paiement. Veuillez réessayer.', 'flouci-payment-gateway')), $notice_type = 'error');
            wp_redirect(get_permalink(get_option('woocommerce_checkout_page_id')));
            exit;
        }

        private function force_ssl($url) {
            if ('yes' == get_option('woocommerce_force_ssl_checkout')) {
                $url = str_replace('http:', 'https:', $url);
            }

            return $url;
        }
    }

    function add_flouci_gateway($methods) {
        $methods[] = 'wc_gateway_flouci';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'add_flouci_gateway');
}
