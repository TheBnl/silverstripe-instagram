<?php

namespace Broarm\Instagram;

use SilverStripe\Forms\LiteralField;

/**
 * Class RevokeButton
 * @package Broarm\Silverstripe\Instagram
 */
class RevokeButton extends LiteralField
{

    public function __construct($name)
    {
        $label = _t('Instagram.REVOKE_LABEL', 'Revoke access to Instagram');
        $revokeUrl = '';
        $button = "<a href='{$revokeUrl}' class='btn action btn-secondary'>{$label}</a>";
        parent::__construct($name, $button);
    }

}