<?php

class StoredCreditcard extends DataObject
{
    /**
     * Database fields
     * @var array
     */
    private static $db = array(
        'CardNumber'        =>  'Varchar(32)',
        'CardExpiry'        =>  'Varchar(8)',
        'FuturePayToken'    =>  'Varchar(64)',
        'isPrimary'         =>  'Boolean'
    );

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = array(
        'Member'            =>  'Member'
    );
}
