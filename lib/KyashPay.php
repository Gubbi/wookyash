<?php

class KyashPay {
    private static $baseUri = 'http://localhost:8082/v1';
    public $key = '';
    public $secret = '';
    public $hmac = NULL;
    public $callback_secret = NULL;
    public $logger = NULL;
    public $use_https = false;


    public function __construct($key, $secret, $callback_secret, $hmac) {
        $this->key = $key;
        $this->secret = $secret;
        $this->callback_secret = $callback_secret;
        $this->hmac = $hmac;
    }

    public function createKyashCode($data) {
        return $this->api_request(self::$baseUri . '/kyashcodes/', $data);
    }

    public function getKyashCode($kyash_code) {
        return $this->api_request(self::$baseUri . '/kyashcodes/' . $kyash_code);
    }

    public function capture($kyash_code) {
        $url = self::$baseUri . '/kyashcodes/' . $kyash_code . '/capture';
        $params = "completion_expected_by=" . strtotime("+3 day");
        $params .= "&details=shipment completed";
        return $this->api_request($url, $params);
    }

    public function cancel($kyash_code, $reason='requested_by_customer') {
        $url = self::$baseUri . '/kyashcodes/' . $kyash_code . '/cancel';
        $params = "reason=".$reason;
        return $this->api_request($url, $params);
    }

    public function getPaymentPoints($pincode) {
        return $this->api_request(self::$baseUri . '/paymentpoints/' . $pincode);
    }

    public function callback_handler($order, $kyash_code, $kyash_status, $req_url) {
        $scheme = parse_url($req_url, PHP_URL_SCHEME);

        if ($scheme === 'https') {
            if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
                $this->log("Handler: Required header values missing.");
                header("HTTP/1.1 401 Unauthorized");
                return;
            }

            $httpd_username = filter_var($_SERVER['PHP_AUTH_USER'], FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_LOW);
            $httpd_password = filter_var($_SERVER['PHP_AUTH_PW'], FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_LOW);

            if ($httpd_username !== $this->key || $httpd_password !== $this->callback_secret) {
                $this->log("Handler: Required credentials not found.");
                header("HTTP/1.1 401 Unauthorized");
                return;
            }
        }
        else {
            $headers = getallheaders();
            $authorization = isset($headers['Authorization']) ? $headers['Authorization'] : '';

            if (empty($authorization)) {
                $this->log("Handler: HTTP/1.1 401 Unauthorized");
                header("HTTP/1.1 401 Unauthorized");
                return;
            }

            $prepared_signature = $this->signature('POST', $req_url, $_REQUEST);
            $this->log($authorization . '\n' . $prepared_signature);

            if ($authorization !== $prepared_signature) {
                $this->log("Handler: Signatures do not match.");
                header("HTTP/1.1 401 Unauthorized");
                return;
            }
        }

        $code = trim($_REQUEST['kyash_code']);
        $status = trim($_REQUEST['status']);
        $phone = trim($_REQUEST['paid_by']);

        if ($kyash_code !== $code) {
            $this->log("Handler: KyashCode not found");
            header("HTTP/1.1 404 Not Found");
            return;
        }

        if ($status === 'paid' && $kyash_status === 'pending') {
            $comment = "Customer(Ph: $phone) has made the payment via Kyash.";
            $order->update('paid', $comment);

            $this->log("Handler: Success - Paid");
        }
        else if ($status === 'expired' && $kyash_status === 'pending') {
            $comment = "This order was canceled since the KyashCode has expired.";
            $order->update('expired', $comment);

            $this->log("Handler: Success - Expired");
        }
        else {
            $this->log("Ignoring the callback as our status is " . $kyash_status . " and the event is for the status " . $status . ".");
        }
        exit;
    }

    public function setLogger($object) {
        $this->logger = $object;
    }

    public function parse_qs($data)
    {
        $data = preg_replace_callback('/(?:^|(?<=&))[^=[]+/', function($match) {
            return bin2hex(urldecode($match[0]));
        }, $data);

        parse_str($data, $values);

        return array_combine(array_map('hex2bin', array_keys($values)), $values);
    }

    public function signature($method, $url, $data){
        $tmp_data = array();
        $request = urlencode($method) . '&' . urlencode($url) . '&';

        if($data){
            $assoc_data = is_array($data) ? $data : $this->parse_qs($data);
            ksort($assoc_data);
            foreach ($assoc_data as $key => $value) {
                if($key == 'route' || $key == 'action') {
                    continue;
                }
                $tmp_data[$key] = $value;
            }
            $query_data = http_build_query($tmp_data);
            $request = $request . urlencode(utf8_encode(str_replace(array( '+','~' ), array('%20', '%7E'), $query_data)));
        }

        //prepare request signature
        $this->log('Normalized request string:' . $request);

        $signature = base64_encode(hash_hmac('sha256', $request, $this->hmac, true));
        $prepared_signature = "HMAC " . base64_encode($this->key . ":" . $signature);

        return $prepared_signature;
    }

    public function api_request($url, $data = NULL) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        if($this->use_https){
            curl_setopt($curl, CURLOPT_SSLVERSION, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, TRUE);
            curl_setopt($curl, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
            curl_setopt($curl, CURLOPT_USERPWD, $this->key . ':' . $this->secret);
        }
        else {
            $method = $data ? 'POST' : 'GET';
            $auth_str = $this->signature($method, $url, $data);
            curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: ' . $auth_str));
        }

        if($data) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        $response = curl_exec($curl);

        if (curl_error($curl)) {
            $error = curl_error($curl);
            $response = array("status" => "error", "message" => $error);
        } else {
            $response = json_decode($response, true);
        }
        curl_close($curl);

        $this->log('Request: ' . $url . ' => ' . $data);
        $this->log('Response: ' . json_encode($response));
        return $response;
    }

    function log($msg) {
        if ($this->logger && is_object($this->logger) && method_exists($this->logger, 'write')) {
            $this->logger->write($msg);
        }
    }
}
?>
