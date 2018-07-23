<?php

namespace Broarm\Instagram;

use SilverStripe\Forms\LiteralField;
use SilverStripe\Security\Member;

/**
 * Class AccountInformationField
 * @package Broarm\Silverstripe\Instagram
 */
class AccountInformationField extends LiteralField
{

    public function __construct($name, Member $data)
    {
        parent::__construct($name, $data->RenderWith('InstagramAccountInformation'));
    }

}