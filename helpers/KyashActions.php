<?php
class KyashActions                                                                                                                                                   
{
	public static function getPaymentPoints()
	{
		$pincode = $_GET['postcode'];
		$settings = self::getSettings();
		$api = new KyashPay($settings['public_api_id'],$settings['api_secret']);
		$api->setLogger(new KyashHelper);
		
		$response = $api->getPaymentPoints($pincode);
		if(isset($response['status']) && $response['status'] == 'error')
		{
			$error = $response['message'];
			include_once(KYASH_DIR.'views/error.php');
		}
		else
		{
			$payments = $response;
			include_once(KYASH_DIR.'views/payment-points.php');
		}
		exit;
	}
	
	public static function getPaymentPointsSuccess()
	{
		$pincode = $_GET['postcode'];
		$settings = self::getSettings();
		$api = new KyashPay($settings['public_api_id'],$settings['api_secret']);
		$api->setLogger(new KyashHelper);
		$response = $api->getPaymentPoints($pincode);
		if(isset($response['status']) && $response['status'] == 'error')
		{
			$error = $response['message'];
			include_once(KYASH_DIR.'views/error.php');
		}
		else
		{
			$payments = $response;
			include_once(KYASH_DIR.'views/payment-points-success.php');
		}
		exit;
	}
	
	public static function handler()
	{
		$settings = self::getSettings();
		$api = new KyashPay($settings['public_api_id'],$settings['callback_secret'],$settings['hmac_secret']);
		$api->setLogger(new KyashHelper);
		
		$params = array();
		$params['order_id'] = trim($_POST['order_id']);
		$params['kyash_code'] = trim($_POST['kyash_code']);
		$params['status'] = trim($_POST['status']);
		$params['paid_by'] = trim($_POST['paid_by']);
		$params['amount'] = trim($_POST['amount']);
		
		$order_id = substr($params['order_id'],1);
		$order = new WC_Order( $order_id );
		if(!$order)
		{
			KyashHelper::log("HTTP/1.1 500 Order is not found");
			header("HTTP/1.1 500 Order is not found");
			exit;								 
		}
		else
		{
			$url = get_home_url().'/?action=kyash-handler';
			$updater = new KyashUpdater($order);
			$api->handler($params,KyashHelper::getKyashOrder($order_id,'kyash_code'),KyashHelper::getKyashOrder($order_id,'kyash_status'),$url,$updater);
		}
	}
	
	public static function getSettings()
	{
		$settings = get_option( 'woocommerce_kyash_settings', null );
		if ( $settings && is_array( $settings ) ) 
		{
			$settings = array_map( array( 'KyashActions', 'format_settings' ), $settings );
		}
		return $settings;
	}
	
	public static function format_settings( $value ) 
	{
		return is_array( $value ) ? $value : $value;
	}
}

class KyashUpdater
{
	public $order = NULL;
	
	public function __construct($order)
	{
		$this->order = $order;
	}
	
	public function update($status,$comment)
	{
		if($status == 'paid')
		{
			KyashHelper::updateKyashOrder($order_id,'kyash_status','paid');
			$this->order->update_status( 'processing',$comment);
		}
		else if($status == 'expired')
		{
			KyashHelper::updateKyashOrder($order_id,'kyash_status','expired');
			$this->order->update_status( 'cancelled',$comment);
		}
	}
}