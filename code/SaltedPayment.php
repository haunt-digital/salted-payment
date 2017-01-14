<?php
namespace SaltedHerring;
use SaltedHerring\Debugger;

class SaltedPayment
{
    public static function list_supported_gateways()
    {
        return \Config::inst()->get('SaltedPayment', 'PaymentGateways');
    }

    public static function get_gateway($gateway_name)
    {
        return self::list_supported_gateways()[$gateway_name];
    }

    public static function list_supported_gateway_settings()
    {
        return \Config::inst()->get('SaltedPayment', 'GatewaySettings');
    }

    public static function get_gateway_settings($gateway_name)
    {
        return self::list_supported_gateway_settings()[$gateway_name];
    }

    public static function get_merchant_setting($setting_name)
    {
        $settings = \Config::inst()->get('SaltedPayment', 'MerchantSettings');
        return $settings[$setting_name];
    }
}
