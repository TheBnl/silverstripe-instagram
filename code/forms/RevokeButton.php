<?php

namespace Broarm\Silverstripe\Instagram;

use Controller;
use LiteralField;

class RevokeButton extends LiteralField
{
    public function __construct($name, $memberId)
    {
        $label = _t('Instagram.REVOKE_LABEL', 'Revoke access to Instagram');
        $revokeUrl = Controller::join_links(CallbackController::REVOKE_URL, $memberId);
        $button = "<a href='{$revokeUrl}' class='ss-ui-button ui-button'>{$label}</a>";
        parent::__construct($name, $button);
    }
}
