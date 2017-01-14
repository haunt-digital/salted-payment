<?php
namespace SaltedHerring\SaltedPayment\API;
use SaltedHerring\SaltedPayment;
use SaltedHerring\Debugger as Debugger;
class Paystation
{
    /*
    * URL paramaters:
    *
    * paystation (REQUIRED)
    * pstn_pi = paystation ID (REQUIRED) - This is an initiator flag for the payment engine and can be nothing, or if your environment requires to assign a value please send ‘_empty’
    * pstn_gi = Gateway ID (REQUIRED) - The Gateway ID that the payments will be made against
    * pstn_ms = Merchant Session (REQUIRED) - a unique identification code for each financial transaction request. Used to identify the transaction when tracing transactions. Must be unique for each attempt at every transaction.
    * pstn_am = Ammount (REQUIRED) - the amount of the transaction, in cents.
    * pstn_cu = Currency - the three letter currency identifier. If not sent the default currency for the gateway is used.
    * pstn_tm = Test Mode - sets the Paystation server into Test Mode (for the single transaction only). It uses the merchants TEST account on the VPS server, and marks the transaction as a Test in the Paystation server. This allows the merchant to run test transactions without incurring any costs or running live card transactions.
    * pstn_mr = Merchant Reference Code - a non-unique reference code which is stored against the transaction. This is recommended because it can be used to tie the transaction to a merchants customers account, or to tie groups of transactions to a particular ledger at the merchant. This will be seen from Paystation Admin. pstn_mr can be empty or omitted.
    * pstn_ct = Card Type - the type of card used. When used, the card selection screen is skipped and the first screen displayed from the bank systems is the card details entry screen. Your merchant account must be enabled for External Payment Selection (EPS), you may have to ask your bank to enable this - check with us if you have problems. CT cannot be empty, but may be omitted.
    * pstn_af = Ammount Format - Tells Paystation what format the Amount is in. If omitted, it will be assumed the amount is in cents
    *
    */
    public static function process($price, $ref, $merchant_session, $register_future_pay = false, $immediate_future_pay = true)
    {
        $amount                     =   $price * 100;
        $paystationId               =   '615684'; //Paystation ID - Replace PAYSTATIONID with your Paystation ID.
        $gatewayId                  =   'PAYSTATION'; //Gateway ID - Replace GATEWAYID with your GATEWAY ID.
        $merchantRef                =   urlencode($ref); //merchant reference is optional, but is a great way to tie a transaction in with a customer (this is displayed in Paystation Administration when looking at transaction details). Max length is 64 char. Make sure you use it!
        $testMode                   =   'true'; //change this to 'false' for production transactions.

        $hmacMode                   =   true; //change this to 'true' to use hmac and also change the next variable
        $hmac_security_code         =   '1rHH8jLWLnPBRTrH'; // change this to the Paystation supplied security code
        $hmacParameters             =   'pstn_du=https://www.nzyogo.co.nz';

        $gateway_endpoint           =   SaltedPayment::get_gateway('Paystation');
        $settings                   =   SaltedPayment::get_gateway_settings('Paystation');

        $params                     =   $settings;

        $endpoint                   =   $gateway_endpoint;
        $params['pstn_am']          =   $amount;
        $params['pstn_mr']          =   $merchantRef;
        $params['pstn_ms']          =   $merchant_session;

        if (!empty($register_future_pay)) {
            $params['pstn_fp']      =   't';
            if (!empty($immediate_future_pay)) {
                unset($params['pstn_am']);
                unset($params['pstn_af']);
                $params['pstn_fs']  =   't';
            }
        }

        $result = self::post($endpoint, $params);
        return self::parse($result);
    }

    public static function parse($result)
    {
        $xmlData = new \SimpleXMLElement($result);
        return (string) $xmlData->DigitalOrder;
    }

    public static function stringify($params)
    {
        $stringified = '';
        foreach ($params as $key => $value) {
            $stringified .= ($key . '=' . $value . '&');
        }

        return rtrim($stringified, '&');
    }

    public static function post($url, $params)
    {
        $query_params = self::stringify($params);
        $curl_handler = curl_init($url);

        curl_setopt($curl_handler, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl_handler, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl_handler, CURLOPT_POST, TRUE);
        curl_setopt($curl_handler, CURLOPT_POSTFIELDS, $query_params);

        curl_setopt($curl_handler, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl_handler, CURLOPT_HEADER, FALSE);
        curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl_handler);
        curl_close($curl_handler);
        return $output;
    }

    public static function fetch($token)
    {

    }
}
