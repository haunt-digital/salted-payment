<?php

class StoredCreditcardExtension extends DataExtension
{
    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = array(
        'Creditcards'   =>  'StoredCreditcard',
    );
}
