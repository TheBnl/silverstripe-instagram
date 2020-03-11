<?php

namespace Broarm\Instagram\Forms;

use Broarm\Instagram\Instagram;
use Broarm\Instagram\InstagramClient;
use SilverStripe\Forms\LiteralField;

class AuthButton extends LiteralField
{
    public function __construct($name)
    {
        $label = _t(__CLASS__ . '.Authenticate', 'Authenticate Instagram');
        $authUrl = InstagramClient::getAuthenticationURL();
        $button = "<a href='{$authUrl}' class='btn action btn-primary'>{$label}</a>";
        parent::__construct($name, $button);
    }
}
