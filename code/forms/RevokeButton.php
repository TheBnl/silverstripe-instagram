<?php

namespace Broarm\Silverstripe\Instagram;

use LiteralField;

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
        $button = "<a href='{$revokeUrl}' class='ss-ui-button ui-button'>{$label}</a>";
        parent::__construct($name, $button);
    }

}