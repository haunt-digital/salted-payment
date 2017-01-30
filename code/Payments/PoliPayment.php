<?php
use SaltedHerring\Debugger as Debugger;
use SaltedHerring\SaltedPayment\API\Poli;
class PoliPayment extends SaltedPaymentModel
{
    protected $PaymentMethod = 'POLi';
    /**
     * Database fields
     * @var array
     */
    protected static $db = array(
        'PayerAcctSortCode'         =>  'Varchar(32)',
        'PayerAcctNumber'           =>  'Varchar(32)',
        'PayerAcctSuffix'           =>  'Varchar(8)',
        'MerchantAcctName'          =>  'Varchar(128)',
        'MerchantAcctSortCode'      =>  'Varchar(32)',
        'MerchantAcctNumber'        =>  'Varchar(32)',
        'MerchantAcctSuffix'        =>  'Varchar(8)',
        'BankReceipt'               =>  'Varchar(64)',
        'ErrorCode'                 =>  'Varchar(32)',
        'FinancialInstitutionName'  =>  'Varchar(256)'
    );

    public function notify($data)
    {
        $arr = self::$db;
        foreach ($arr as $key => $value)
        {
            if (!empty($data[$key])) {
                $this->$key = $data[$key];
            }
            // SS_Log::log($key . '::' . $this->$key, SS_Log::WARN);
        }
        $this->TransacID = $data['TransactionRefNo'];
        $this->Status = $this->translate_state($data['TransactionStatusCode']);
        $this->write();
    }

    private function translate_state($state)
    {
        //'Status'            =>  "Enum('Incomplete,Success,Failure,Pending','Incomplete')",
        $state = trim($state);
        if ($state == 'Completed') {
            return 'Success';
        }

        if ($state == 'Initiated' || $state == 'FinancialInstitutionSelected') {
            return 'Incomplete';
        }

        if ($state == 'Unknown' || $state == 'Failed' || $state == 'TimedOut') {
            return 'Failure';
        }

        if ($state == 'Cancelled') {
            return 'Cancelled';
        }

        return 'Pending';
    }

}


// Array
// (
//     [PayerAcctSuffix] =>
//     [PayerAcctNumber] => 98742364
//     [PayerAcctSortCode] => 123456
//     [MerchantAcctNumber] => 0008439
//     [MerchantAcctSuffix] => 001
//     [MerchantAcctSortCode] => 030578
//     [MerchantAcctName] => YOGO Limited
//     [MerchantReferenceData] =>
//     [TransactionRefNo] => 996431424323
//     [CurrencyCode] => NZD
//     [CountryCode] => NZL
//     [PaymentAmount] => 1024
//     [AmountPaid] => 1024
//     [EstablishedDateTime] => 2017-01-12T15:08:38.267
//     [StartDateTime] => 2017-01-12T15:08:38.267
//     [EndDateTime] => 2017-01-12T15:15:15.803
//     [BankReceipt] => 00537544-167446
//     [BankReceiptDateTime] => 12 January 2017 15:15:15
//     [TransactionStatusCode] => Completed
//     [ErrorCode] =>
//     [ErrorMessage] =>
//     [FinancialInstitutionCode] => iBankNZ01
//     [FinancialInstitutionName] => iBank NZ 01
//     [MerchantReference] => 27a2cc38
//     [MerchantAccountSuffix] => 001
//     [MerchantAccountNumber] => 0008439
//     [PayerFirstName] => Mr
//     [PayerFamilyName] => DemoShopper
//     [PayerAccountSuffix] =>
// )
