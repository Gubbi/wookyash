<?php
/*
Plugin Name: Kyash
Plugin URI: http://www.kyash.com/
Description: Kyash for WooCommerce
Version: 0.1.4
Author: Kyash
*/

define( 'KYASH_URL', plugin_dir_url( __FILE__ ) );
define( 'KYASH_DIR', plugin_dir_path( __FILE__ ) );

add_action('plugins_loaded', 'woocommerce_kyash_init', 0);

function woocommerce_kyash_init()
{
	if(!class_exists('WC_Payment_Gateway')) 
	{
		return;
	}
	include_once(KYASH_DIR. 'lib/KyashPay.php' );
	include_once(KYASH_DIR. 'helpers/KyashHelper.php' );
	include_once(KYASH_DIR. 'helpers/KyashActions.php' );
	
	if(isset($_REQUEST['action']))
	{
		if($_REQUEST['action'] == 'kyash-handler')
		{
			KyashActions::handler();
		}
		else if($_REQUEST['action'] == 'kyash-get-paypoints')
		{
			KyashActions::getPaymentPoints(new KyashHelper);
		}
		else if($_REQUEST['action'] == 'kyash-get-paypoints-success')
		{
			KyashActions::getPaymentPointsSuccess();
		}
	}

	/**
 	* Gateway class
 	**/
	class WC_Gateway_Kyash extends WC_Payment_Gateway
	{
		public function __construct()
		{
			$this->id = 'kyash';
			$this->method_title = 'Kyash - Pay at a nearby Shop';
			$this->has_fields = false;
			
			$this->handler_url = get_home_url().'/?action=kyash-handler';
			
			// Load the form fields.
			$this->init_form_fields();
			
			// Load the settings.
			$this->init_settings();
			
			// Define user set variables
			$this->title = $this->settings['title'];
			$this->public_api_id = $this->settings['public_api_id'];
			$this->api_secret = $this->settings['api_secret'];
			$this->callback_secret = $this->settings['callback_secret'];
			$this->hmac_secret = $this->settings['hmac_secret'];
			$this->instructions = $this->settings['instructions'];
			
			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
			add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_kyash', array( $this, 'thankyou_page' ) );
		}
		
		/**
	     * Initialise Gateway Settings Form Fields
	     */
		function init_form_fields()
		{
			$this->form_fields = array(
					'enabled' => array(
								'title' => __( 'Enable/Disable', 'woocommerce'), 
								'type' => 'checkbox', 
								'label' => __( 'Enable Kyash', 'woocommerce'), 
								'default' => 'yes'
							), 
					'title' => array(
								'title' => __( 'Title', 'woocommerce'), 
								'type' => 'text', 
								'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce'), 
								'default' => __( 'Kyash - Pay at a nearby Shop'),
								'desc_tip'    => true,
							),
					'instructions' => array(
								'title' => __( 'Instructions', 'woocommerce'), 
								'type' => 'textarea', 
								'description' => __( 'Instructions on how to make the payment displayed on thank you page', 'woocommerce'),
								'default' => 'Please pay at any of the authorized outlets before expiry. <br/>You need to mention only the KyashCode and may be asked for your mobile number during payment. No other details needed. <br/>Please wait for the confirmation SMS after payment. Remember to take a payment receipt. <br/>You can verify the payment status anytime by texting this KyashCode to +91 9243710000',
								'desc_tip'    => true,
							),
					'public_api_id' => array(
								'title' => __( 'Public API ID', 'woocommerce'), 
								'type' => 'text',
								'description' => __( 'This is a unique public identifier of the Merchant sent with all API requests. This ID can be made public and only one ID is generated per Merchant.', 'woocommerce'),
								'desc_tip'    => true,
							),
					'api_secret' => array(
								'title' => __( 'API Secret', 'woocommerce'), 
								'type' => 'password', 
								'description' => __( 'You authenticate to Kyash server by using one of these API Secrets in the request. This detail should be treated as a secret and never to be shared. You can manage them from your account and have multiple API Secrets active at one time.', 'woocommerce'),
								'desc_tip'    => true,
							),
					'callback_secret' => array(
								'title' => __( 'Callback Secret', 'woocommerce'), 
								'type' => 'password', 
								'description' => __( 'Used by the Kyash Server to authenticate itself during API callbacks
over HTTPS. This detail should be treated as a secret and never to be shared. Only
one Callback Secret is generated per Merchant. It can be changed from your account.', 'woocommerce'),
								'desc_tip'    => true,
							),
					'hmac_secret' => array(
								'title' => __( 'HMAC Secret', 'woocommerce'), 
								'type' => 'password', 
								'description' => __( 'Used by the Kyash Server to authenticate itself during API callbacks
over HTTP. This detail should be treated as a secret and never to be shared. Only
one HMAC Secret is generated per Merchant. It can be changed from your account.', 'woocommerce'),
								'desc_tip'    => true,
							),
				'callback_url' => array(
								'title' => __( 'Callback Url', 'woocommerce'), 
								'type' => 'title', 
								'description' => '<strong>'.$this->handler_url.'</strong>'
							),
					
				);
		}
		
 		/**
		 * Admin Panel Options 
		 */
        public function admin_options()
		{
			echo '<h3>'.__('Kyash', 'kyash').'</h3>';
			echo '<table class="form-table">';
			// Generate the HTML For the settings form.
			$this -> generate_settings_html();
			echo '</table>';
		}
		
		public function get_description()
		{
			return false;
		}
		
		/**
		 * Output for the order received page.
		 */
		public function thankyou_page() 
		{
			$order_id = $_GET['order-received'];
			$order = new WC_Order( $order_id );
			$postcode = $order->billing_postcode;
			$kyash_instructions = $this->instructions;
			$kyash_code = KyashHelper::getKyashOrder($order_id,'kyash_code');
            $kc_expires_on = KyashHelper::getKyashOrder($order_id,'kyash_expires');
            $dateTime = new DateTime("@".$kc_expires_on);
            $dateTime->setTimeZone(new DateTimeZone('Asia/Kolkata'));
            $expires_on = $dateTime->format("j M Y, g:i A");
			$url = get_home_url().'/?action=kyash-get-paypoints-success';
			include_once(KYASH_DIR.'views/thankyou.php');
		}
		
		public function getOrderParams($order)
		{
			$address1 = $order->billing_address_1;
			if ($order->billing_address_2) {
				$address1 .= ','.$order->billing_address_2;
			}
			
			$address2 = $order->billing_first_name;
			if ($order->billing_first_name) {
				$address2 .= ','.$order->billing_first_name;
			}
			
			$params = array (
				'order_id' => 'W'.$order->id,
				'amount' => $order->order_total,
				'billing_contact.first_name' => $order->billing_first_name,
				'billing_contact.last_name' => $order->billing_last_name,
				'billing_contact.email' => $order->billing_email,
				'billing_contact.address' => $address1,
				'billing_contact.city' => $order->billing_city,
				'billing_contact.state' => $order->billing_state,
				'billing_contact.pincode' => $order->billing_postcode,
				'billing_contact.phone' => $order->billing_phone,
				'shipping_contact.first_name' => $order->shipping_first_name,
				'shipping_contact.last_name' => $order->shipping_last_name,
				'shipping_contact.address' => $address2,
				'shipping_contact.city' => $order->shipping_city,
				'shipping_contact.state' => $order->shipping_state,
				'shipping_contact.pincode' => $order->shipping_postcode,
				'shipping_contact.phone' => $order->billing_phone
			);
			
			return http_build_query($params);
		}
		
		/**
        * process payment
        * 
        * @param int $order_id
        */
        public function process_payment( $order_id ) 
        {
            global $woocommerce,$api;

			$order = new WC_Order( $order_id );
			$settings = $this->getSettings();
			$api = new KyashPay($settings['public_api_id'],$settings['api_secret'],$settings['callback_secret'],$settings['hmac_secret']);
			$api->setLogger(new KyashHelper);
			$params = $this->getOrderParams($order);
			$response = $api->createKyashCode($params);
			$json = array();
			if(isset($response['status']) && $response['status'] == 'error')
			{
				wc_add_notice( 'Payment error. '.$response['message'], 'error' );
			}
			else
			{
				$order->update_status( 'on-hold', __( 'Awaiting Kyash Payment', 'woocommerce' ) );
				$order->reduce_order_stock();
				WC()->cart->empty_cart();
				
				$method = KyashHelper::getPaymentMethod($order_id);
				update_post_meta($order_id, '_payment_method_title', $method.', Kyash code - '.$response['id']);
				KyashHelper::updateKyashOrder($order_id,'kyash_code',$response['id']);
				KyashHelper::updateKyashOrder($order_id,'kyash_status','pending');
				KyashHelper::updateKyashOrder($order_id,'kyash_expires', $response['expires_on']);

				return array(
					'result' 	=> 'success',
					'redirect'	=> $this->get_return_url( $order )
				);
			}
		}
		
		public function getSettings()
		{
			$settings = get_option( 'woocommerce_kyash_settings', null );
			if ( $settings && is_array( $settings ) ) 
			{
				$settings = array_map( array( $this, 'format_settings' ), $settings );
			}
			return $settings;
		}
		
		public function format_settings( $value ) 
		{
			return is_array( $value ) ? $value : $value;
		}
		
		public function get_icon() 
		{
			$icon_html = '';
            $customer = new WC_Customer();
            $postcode = $customer->get_postcode();
			$icon_html .= $this->getShopsLink($postcode);
			return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
		}
		
		public function getShopsLink($postcode)
		{
			$url = get_home_url().'/?action=kyash-get-paypoints';
			
			$css = '<style>'.@file_get_contents(KYASH_DIR.'assets/css/checkout.css').'</style>';
			
			$html = '<script type="text/javascript" src="//secure.kyash.com/outlets.js"></script>
                     <p id="kyash_payment_instructions">Product will be sent to the shipping address only after payment. If order is cancelled or not delivered, you can avail refund as per our policies.</p>
                     <div style="display: none">
                        <kyash:code merchant_id="'.$this->settings["public_api_id"].'" postal_code="'.$postcode.'"></kyash:code>
                     </div>';
			$js = '
			<!--script>
			var pincodePlaceHolder = "Enter Pincode";
			var loader = \'<img src="'.includes_url().'images/spinner.gif" alt="Processing..." />\';
			'.@file_get_contents(KYASH_DIR.'assets/js/checkout.js').'
			</script-->
			';
			return $css.$html.$js;
		}
	}
	
	/**
	 * Add the Gateway to WooCommerce
	 **/
	function kyash_add($methods)
	{
		$methods[] = 'WC_Gateway_Kyash';
		return $methods;
	}
	add_filter('woocommerce_payment_gateways', 'kyash_add' );
	
	function editorder($order_id)
	{
		if($order_id > 0)
		{
			$order = new WC_Order( $order_id );
			
			$kyash_code = KyashHelper::getKyashOrder($order_id,'kyash_code');
			$kyash_status = KyashHelper::getKyashOrder($order_id,'kyash_status');
			
			$kyashPayment = new WC_Gateway_Kyash;
			$settings = $kyashPayment->getSettings();
			$api = new KyashPay($settings['public_api_id'],$settings['api_secret'],$settings['callback_secret'],$settings['hmac_secret']);
			$api->setLogger(new KyashHelper);
			
			if (!empty($kyash_code)) 
			{
				if($order->post_status == 'wc-cancelled')
				{
					if($kyash_status == 'pending' || $kyash_status == 'paid')
					{
						$response = $api->cancel($kyash_code);
						if(isset($response['status']) && $response['status'] == 'error')
						{
							update_option('kyash_order_view_error',$response['message']);
						}
						else
						{
							KyashHelper::updateKyashOrder($order_id,'kyash_status','cancelled');
							$message = 'Kyash payment collection has been cancelled for this order.';
							update_option('kyash_order_view_success',$message);
						}
					}
					else if($kyash_status == 'captured')
					{
						$message = 'Customer payment has already been transferred to you. Refunds if any, are to be handled by you.';
						update_option('kyash_order_view_error',$message);
					}
				}
				else if($order->post_status == 'wc-processing')
				{
					if($kyash_status == 'pending')
					{
						$response = $api->cancel($kyash_code);
						if(isset($response['status']) && $response['status'] == 'error')
						{
							$message = 'Order #'.$order_id.': '.$response['message'];
							$error = get_option('kyash_order_view_error');
							if($error)
							{
								$message = $error.'<br/>'.$message;
							}
							update_option('kyash_order_view_error',$message);
						}
						else
						{
							KyashHelper::updateKyashOrder($order_id,'kyash_status','cancelled');
							$message='Order #'.$order_id.': You have processed before Kyash payment was done. Kyash payment collection has been cancelled for this order.';
							$success = get_option('kyash_order_view_success');
							if($success)
							{
								$message = $success.'<br/>'.$message;
							}
							update_option('kyash_order_view_success',$message);
						}
					}
				}
				else if($order->post_status == 'wc-completed')
				{
                    $server_kc = $api->getKyashCode($kyash_code);
                    if (isset($server_kc['status']) && $server_kc['status'] !== 'error') {
                        if ($kyash_status !== $server_kc['status']) {
                            KyashHelper::updateKyashOrder($order_id,'kyash_status',$server_kc['status']);
                            $kyash_status = $server_kc['status'];
                        }
                    }
					if($kyash_status == 'pending')
					{
						$response = $api->cancel($kyash_code,$reason="using_another_payment_method");
						if(isset($response['status']) && $response['status'] == 'error')
						{
							update_option('kyash_order_view_error',$response['message']);
						}
						else
						{
							KyashHelper::updateKyashOrder($order_id,'kyash_status','cancelled');
							$message = 'You have shipped before Kyash payment was done. Kyash payment collection has been cancelled for this order.';
							update_option('kyash_order_view_success',$message);
						}
					}
				}
			}
		}
	}
	add_action( 'woocommerce_process_shop_order_meta', 'editorder', 101);
	
	function admin_menu()
	{
		if( 
		   isset($_REQUEST['post_type']) && 
		   $_REQUEST['post_type'] == 'shop_order' && 
		   ( (isset($_REQUEST['marked_completed']) && $_REQUEST['marked_completed'] > 0) || (isset($_REQUEST['marked_processing']) && $_REQUEST['marked_processing'] > 0) ) && 
		   isset($_REQUEST['ids']) && 
		   !empty($_REQUEST['ids']) 
		)
		{
			$ids = explode(',',$_REQUEST['ids']);
			if(is_array($ids))
			{
				$kyashPayment = new WC_Gateway_Kyash;
				$settings = $kyashPayment->getSettings();
				$api = new KyashPay($settings['public_api_id'],$settings['api_secret'],$settings['callback_secret'],$settings['hmac_secret']);
				$api->setLogger(new KyashHelper);
			
				foreach ($ids as $order_id) 
				{
					if($order_id > 0)
					{
						$order = new WC_Order( $order_id );
						$kyash_code = KyashHelper::getKyashOrder($order_id,'kyash_code');
						$kyash_status = KyashHelper::getKyashOrder($order_id,'kyash_status');

						if (!empty($kyash_code)) 
						{
							if($order->post_status == 'wc-cancelled')
							{
								if($kyash_status == 'pending' || $kyash_status == 'paid')
								{
									$response = $api->cancel($kyash_code);
									if(isset($response['status']) && $response['status'] == 'error')
									{
										$message = 'Order #'.$order_id.': '.$response['message'];
										$error = get_option('kyash_order_view_error');
										if($error)
										{
											$message = $error.'<br/>'.$message;
										}
										update_option('kyash_order_view_error',$message);
									}
									else
									{
										KyashHelper::updateKyashOrder($order_id,'kyash_status','cancelled');
										$message = 'Order #'.$order_id.': Kyash payment collection has been cancelled for this order.';
										$success = get_option('kyash_order_view_success');
										if($success)
										{
											$message = $success.'<br/>'.$message;
										}
										update_option('kyash_order_view_success',$message);
									}
								}
								else if($kyash_status == 'captured')
								{
									$message = 'Order #'.$order_id.': Customer payment has already been transferred to you. Refunds if any, are to be handled by you.';
									$error = get_option('kyash_order_view_error');
									if($error)
									{
										$message = $error.'<br/>'.$message;
									}
									update_option('kyash_order_view_error',$message);
								}
							}
							else if($order->post_status == 'wc-processing')
							{
								if($kyash_status == 'pending')
								{
									$response = $api->cancel($kyash_code);
									if(isset($response['status']) && $response['status'] == 'error')
									{
										$message = 'Order #'.$order_id.': '.$response['message'];
										$error = get_option('kyash_order_view_error');
										if($error)
										{
											$message = $error.'<br/>'.$message;
										}
										update_option('kyash_order_view_error',$message);
									}
									else
									{
										KyashHelper::updateKyashOrder($order_id,'kyash_status','cancelled');
										$message='Order #'.$order_id.': You have processed before Kyash payment was done. Kyash payment collection has been cancelled for this order.';
										$success = get_option('kyash_order_view_success');
										if($success)
										{
											$message = $success.'<br/>'.$message;
										}
										update_option('kyash_order_view_success',$message);
									}
								}
							}
							else if($order->post_status == 'wc-completed')
							{
                                $server_kc = $api->getKyashCode($kyash_code);
                                if (isset($server_kc['status']) && $server_kc['status'] !== 'error') {
                                    if ($kyash_status !== $server_kc['status']) {
                                        KyashHelper::updateKyashOrder($order_id,'kyash_status',$server_kc['status']);
                                        $kyash_status = $server_kc['status'];
                                    }
                                }
								if($kyash_status == 'pending')
								{
									$response = $api->cancel($kyash_code,$reason="using_another_payment_method");
									if(isset($response['status']) && $response['status'] == 'error')
									{
										$message = 'Order #'.$order_id.': '.$response['message'];
										$error = get_option('kyash_order_view_error');
										if($error)
										{
											$message = $error.'<br/>'.$message;
										}
										update_option('kyash_order_view_error',$message);
									}
									else
									{
										KyashHelper::updateKyashOrder($order_id,'kyash_status','cancelled');
										$message='Order #'.$order_id.': You have shipped before Kyash payment was done. Kyash payment collection has been cancelled for this order.';
										$success = get_option('kyash_order_view_success');
										if($success)
										{
											$message = $success.'<br/>'.$message;
										}
										update_option('kyash_order_view_success',$message);
									}
								}
							}
						}
					}
				}
			}
		}
	}
	@add_action('admin_menu','admin_menu');
	@add_action( 'admin_notices', 'success_notice',10);
	@add_action( 'admin_notices', 'error_notice',20);
}
function success_notice()
{
	$success = get_option('kyash_order_view_success');
	if($success)
	{
		delete_option('kyash_order_view_success');
    ?>
    <div class="updated">
        <p><?php echo $success ?></p>
    </div>
    <?php
	}
}
function error_notice()
{
	$error = get_option('kyash_order_view_error');
	if($error)
	{
		delete_option('kyash_order_view_error');
    ?>
    <div class="error">
        <p><?php echo $error ?></p>
    </div>
    <?php
	}
}

function kyash_activate()
{
	global $wpdb;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'kyash_order`(
		`order_id` int(10) unsigned NOT NULL,
		`kyash_code` varchar(200),
		`kyash_status` varchar(200),
		`kyash_expires` int(10) unsigned NOT NULL
		) DEFAULT CHARSET=utf8';
	dbDelta($sql);
}
register_activation_hook(__FILE__, 'kyash_activate');

