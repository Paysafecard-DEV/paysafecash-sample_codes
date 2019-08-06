<?php

/*
 * this script handles the notification requests made by the paysafecash api
 * handling the requests after a payment was successful / failed
 */

error_reporting(E_ALL);
include_once 'PaymentClass.php';
include_once "PaysafeLogger.php";

if(!function_exists('apache_request_headers')) {
    function apache_request_headers() {
        $headers = array();
        foreach($_SERVER as $key => $value) {
            if(substr($key, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))))] = $value;
            }
        }
        return $headers;
    }
}


/**
 *
 * Check config.php for configuration
 *
 */

include_once "config.php";

// create new Cash Controller
$pscpayment = new PaysafecardCashController($config['psc_key'], $config['environment']);

// create new Logger
$logger     = new PaysafeLogger();

// get signature from the http header
$signature = str_replace('"', '', str_replace('signature="', '', explode(",",apache_request_headers()["Authorization"])[2]));

// get the raw http body from the api request
$payment_str = file_get_contents("php://input");

// decode the json data to an array
$json_obj = json_decode($payment_str);

// read the Public Key from the Certificate for validation
$pubkey = openssl_pkey_get_public(file_get_contents(getcwd()."/".$config['psc_certificate']));

// verify the webhook with ssl.
$signatur_check = openssl_verify(hash("sha256", $payment_str), base64_decode($signature), $pubkey);

// if logging is enabled. The key and signature will be shown at the log
if($config["logging"]){
	$logger->log("WEBHOOK SIGNATUR KEY: ".$signature,"", "" );
	$logger->log("WEBHOOK SIGNATUR HASH: ".hash("sha256", $payment_str),"", "" );
}
// destroy the openssl object after verification
openssl_free_key($pubkey);

// log the result of verification
if ($signatur_check == 1) {
    $logger->log("WEBHOOK SIGNATUR: Signatur is correct","", "" );
} elseif ($signatur_check == 0) {
    $logger->log("WEBHOOK SIGNATUR: Signatur is not correct","", "" );
} else {
    $logger->log("WEBHOOK SIGNATUR: ERROR: ". openssl_error_string(),"", "" );
}


// checking for actual action
if (isset($json_obj->data->mtid)) {

    // get payment status with retrieve Payment details
    $response = $pscpayment->retrievePayment($json_obj->data->mtid);
	$logger->log("WEBHOOK DATA: ". $json_obj->data->mtid, "", "");
    $logger->log("WEBHOOK: ".$pscpayment->getRequest(), $pscpayment->getCurl(), $pscpayment->getResponse());

}
