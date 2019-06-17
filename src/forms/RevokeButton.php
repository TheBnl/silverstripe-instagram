<?php

namespace Broarm\Instagram;

use SilverStripe\Control\Director;
use SilverStripe\Forms\LiteralField;

/**
 * Class RevokeButton
 * @package Broarm\Silverstripe\Instagram
 */
class RevokeButton extends LiteralField
{

    public function __construct($name, $memberId)
    {
        $label = _t('Instagram.REVOKE_LABEL', 'Revoke access to Instagram');
        $revokeUrl = Director::absoluteURL("instagram/revoke/{$memberId}");
        $button = "<a href='{$revokeUrl}' class='btn action btn-secondary'>{$label}</a>";
        parent::__construct($name, $button);
    }

}