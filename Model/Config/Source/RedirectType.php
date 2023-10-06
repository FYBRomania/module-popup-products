<?php
/**
 * @author FYB Romania
 * @copyright Copyright (c) FYB Romania (https://fyb.ro)
 * @package Popup Products for Magento 2
 */

namespace Fyb\PopupProducts\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class RedirectType implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('Redirect To Checkout'), 'value' => 1],
            ['label' => __('Redirect To Cart'), 'value' => 2],
        ];
    }
}
