<?php
/**
 * @author FYB Romania
 * @copyright Copyright (c) FYB Romania (https://fyb.ro)
 * @package Popup Products for Magento 2
 */

namespace Fyb\PopupProducts\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ForcePopupBehaviour implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('Not Open'), 'value' => ''],
            ['label' => __('Open Before Redirect'), 'value' => 1],
            ['label' => __('Open After Redirect'), 'value' => 2],
        ];
    }
}
