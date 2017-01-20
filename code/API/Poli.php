<?php
namespace SaltedHerring\SaltedPayment\API;
use SaltedHerring\SaltedPayment;
use SaltedHerring\Debugger as Debugger;
class Poli
{
    public static function process($price, $ref, $order_class = null)
    {
        //Debugger::inspect($order);
        /*
        SaltedPayment::get_merchant_setting('MerchantHomepageURL');
        SaltedPayment::get_merchant_setting('SuccessURL');
        SaltedPayment::get_merchant_setting('FailureURL');
        SaltedPayment::get_merchant_setting('CancellationURL');
        SaltedPayment::get_merchant_setting('NotificationURL');
        */
        $gateway_endpoint = SaltedPayment::get_gateway('POLi');
        $settings = SaltedPayment::get_gateway_settings('POLi');
        $cert_path = $settings['CERT'];
        $client_code = $settings['CLIENTCODE'];
        $auth_code = $settings['AUTHCODE'];
        $returnurl = \Director::absoluteBaseURL() . 'salted-payment/poli-complete';
        // "SuccessURL":"' . SaltedPayment::get_merchant_setting('SuccessURL') . '",
        // "FailureURL":"' . SaltedPayment::get_merchant_setting('FailureURL') . '",
        // "CancellationURL":"' . SaltedPayment::get_merchant_setting('CancellationURL') . '",
        // "NotificationURL":"' . SaltedPayment::get_merchant_setting('NotificationURL') . '"
        $json_builder = '{
            "Amount":"' . $price . '",
            "CurrencyCode":"NZD",
            "MerchantData": "' . (!empty($order_class) ? $order_class : SaltedPayment::get_default_order_class()) . '",
            "MerchantReference":"' . $ref . '",
            "MerchantHomepageURL":"' . $returnurl . '",
            "SuccessURL":"' . $returnurl . '",
            "FailureURL":"' . $returnurl . '",
            "CancellationURL":"' . $returnurl . '",
            "NotificationURL":"' . $returnurl . '"
        }';

         $auth = base64_encode($client_code . ':' . $auth_code);
         $header = array();
         $header[] = 'Content-Type: application/json';
         $header[] = 'Authorization: Basic '.$auth;

         $ch = curl_init($gateway_endpoint);
         //See the cURL documentation for more information: http://curl.haxx.se/docs/sslcerts.html
         //We recommend using this bundle: https://raw.githubusercontent.com/bagder/ca-bundle/master/ca-bundle.crt
         curl_setopt( $ch, CURLOPT_CAINFO, $cert_path);
         curl_setopt( $ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
         curl_setopt( $ch, CURLOPT_HTTPHEADER, $header);
         curl_setopt( $ch, CURLOPT_HEADER, 0);
         curl_setopt( $ch, CURLOPT_POST, 1);
         curl_setopt( $ch, CURLOPT_POSTFIELDS, $json_builder);
         curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0);
         curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
         $response = curl_exec( $ch );
         curl_close ($ch);

         $json = json_decode($response, true);

         return $json;
    }

    public static function fetch($token)
    {
        //EhnCujLNQuGDeRigzZyOpWp3dxM0y29K

        /*$token = $_POST["Token"];
        if(is_null($token)) {
            $token = $_GET["token"];
        }*/

        //$token = 'EhnCujLNQuGDeRigzZyOpWp3dxM0y29K';

        $gateway_endpoint = SaltedPayment::get_gateway('POLi');
        $settings = SaltedPayment::get_gateway_settings('POLi');
        $cert_path = $settings['CERT'];
        $client_code = $settings['CLIENTCODE'];
        $auth_code = $settings['AUTHCODE'];

        $auth = base64_encode($client_code . ':' . $auth_code);
        $header = array();
        $header[] = 'Authorization: Basic '.$auth;

        $ch = curl_init($gateway_endpoint . '?token=' . $token);
        $ch = curl_init("https://poliapi.apac.paywithpoli.com/api/Transaction/GetTransaction?token=".urlencode($token));
        //See the cURL documentation for more information: http://curl.haxx.se/docs/sslcerts.html
        //We recommend using this bundle: https://raw.githubusercontent.com/bagder/ca-bundle/master/ca-bundle.crt
        curl_setopt( $ch, CURLOPT_CAINFO, $cert_path);
        curl_setopt( $ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt( $ch, CURLOPT_HEADER, 0);
        curl_setopt( $ch, CURLOPT_POST, 0);
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec( $ch );
        curl_close ($ch);

        $json = json_decode($response, true);
        return $json;
    }
}
