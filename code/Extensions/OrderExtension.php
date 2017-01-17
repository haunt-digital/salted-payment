<?php

class OrderExtension extends DataExtension
{
    /**
     * Database fields
     * @var array
     */
    private static $db = array(
        'FullRef'       =>  'Varchar(64)'
    );

    public function Payments()
    {
        if (empty($this->owner->ID)) {
            return null;
        }
        $payments = SaltedPaymentModel::get()->filter(array('OrderID' => $this->owner->ID, 'OrderClass' => 'PropertyPage'));
        return $payments->count() > 0 ? $payments : null;
    }
}
