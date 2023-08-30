<?php

namespace Fyb\PopupProducts\Model\Config\Source;

class RedirectType implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['label' => __('Redirect To Checkout'), 'value' => 1],
            ['label' => __('Redirect To Cart'), 'value' => 2],
        ];
    }
}
