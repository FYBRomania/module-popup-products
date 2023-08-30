<?php

namespace Fyb\PopupProducts\Model\Config\Source;

class ForcePopupBehaviour implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['label' => __('Not Open'), 'value' => ''],
            ['label' => __('Open Before Redirect'), 'value' => 1],
            ['label' => __('Open After Redirect'), 'value' => 2],
        ];
    }
}
