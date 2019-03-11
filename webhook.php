<?php

/*
 * this script handles the notification requests made by the paysafecard api
 * handling the requests after a payment was successful / failed
 */

error_reporting(E_ALL);
include_once 'PaymentClass.php';
include_once "PaysafeLogger.php";

/**
 *
 * Check config.php for configuration
 *
 */

include_once "config.php";

$payment_str = file_get_contents('php://input');
$json_obj = json_decode($payment_str);

// create new Payment Controller
$pscpayment = new PaysafecardCashController($config['psc_key'], $config['environment']);
$logger     = new PaysafeLogger();

$logger->log("WEBHOOK: ". print_r($json_obj, true), print_r($json_obj, true), print_r($json_obj, true));

// checking for actual action
if (isset($json_obj->data->mtid)) {

    // get payment status with retrieve Payment details
    $response = $pscpayment->retrievePayment($json_obj->data->mtid);
    $logger->log("WEBHOOK: ".$pscpayment->getRequest(), $pscpayment->getCurl(), $pscpayment->getResponse());

}
