<?php

namespace Broarm\Silverstripe\Instagram;

use LiteralField;

class AuthButton extends LiteralField
{
    public function __construct($name)
    {
        $label = _t('Instagram.AUTHENTICATE_LABEL', 'Authenticate Instagram');
        $authUrl = InstagramClient::getAuthenticationURL();
        $button = "<a href='{$authUrl}' class='ss-ui-button ui-button'>{$label}</a>";
        parent::__construct($name, $button);
    }
}
