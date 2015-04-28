<?php
class KyashHelper                                                                                                                                                   
{
	public static function updateKyashOrder($order_id,$attribute,$value)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . "kyash_order"; 
		if(self::getKyashOrder($order_id))
		{
			$wpdb->update(
				$table_name,
				array(
					$attribute => $value
				),
				array( 'order_id' => $order_id )
			);
		}
		else
		{
			$wpdb->insert(
				$table_name,
				array(
					'order_id' => $order_id,
					$attribute => $value
				)
			);
		}
	}
	
	public static function getKyashOrder($order_id,$attribute='')
	{
		global $wpdb;
		if(empty($attribute))
		{
			$attribute = 'order_id';
		}
		
		$table_name = $wpdb->prefix . "kyash_order"; 
		$sql = "SELECT $attribute FROM $table_name WHERE order_id = $order_id";
		$results = $wpdb->get_results($sql);
		foreach($results as $result)
		{
			return $result->$attribute;
		}
		return false;
	}
	
	public static function getPaymentMethod($order_id)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . "postmeta"; 
		$sql = "SELECT meta_value FROM $table_name WHERE post_id = $order_id AND meta_key='_payment_method_title'";
		$results = $wpdb->get_results($sql);
		foreach($results as $result)
		{
			return $result->meta_value;
		}
	}
	
	public static function log($content, $date = true)
	{
		$filename = KYASH_DIR.'/kyash.log';
		$fp = fopen($filename, 'a+');
		if($date)
		{
			fwrite($fp, date("Y-m-d H:i:s").": ");
		}
		fwrite($fp, print_r($content, TRUE));
		fwrite($fp, "\n");
		fclose($fp);
	}
	
	public function write($content)
	{
		$filename = KYASH_DIR.'/kyash.log';
		$fp = fopen($filename, 'a+');
		fwrite($fp, print_r($content, TRUE));
		fwrite($fp, "\n");
		fclose($fp);
	}
	
	public static function addSuccess($message)
	{
		@add_action( 'admin_notices', 'success_notice',10,2 );
		@do_action('admin_notices', $message,'');
	}
	
	public static function addError($message)
	{
		@add_action( 'admin_notices', 'error_notice',10,2 );
		@do_action('admin_notices', '',$message);
	}
	
	public static function getProtocol()
	{
		$protocol = 'http';
		if((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&  $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) 
		{
  			$protocol = 'https';
		}
		return $protocol;
	}
}