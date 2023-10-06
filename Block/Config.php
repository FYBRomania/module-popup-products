<?php
/**
 * @author FYB Romania
 * @copyright Copyright (c) FYB Romania (https://fyb.ro)
 * @package Popup Products for Magento 2
 */

namespace Fyb\PopupProducts\Block;

use Magento\Framework\View\Element\Template;

class Config extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonSerializer;

    /**
     * @var \Fyb\PopupProducts\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Fyb\PopupProducts\Helper\Data $helper
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Fyb\PopupProducts\Helper\Data $helper,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helper = $helper;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @return string
     */
    public function getJsonConfig()
    {
        $config = [
            'isEnabled' => $this->helper->isEnabled(),
            'title' => __($this->helper->getTitle()),
            'categoryId' => $this->helper->getMainCategory(),
            'openPopupOnAdd' => $this->openPopupOnAdd(),
            'sendUrl' => $this->getUrl('fyb/cart/add'),
            'buttonText' => __("Continue"),
        ];

        return $this->jsonSerializer->serialize($config);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->helper->isEnabled();
    }

    /**
     * @return bool
     */
    public function openPopupOnAdd()
    {
        return !$this->helper->isRedirectToCartEnabled() || $this->helper->openPopupBeforeRedirect()
            || $this->helper->openPopupAfterRedirect();
    }

    /**
     * @return bool
     */
    public function openPopupAfter()
    {
        return $this->helper->isRedirectToCartEnabled() && $this->helper->openPopupAfterRedirect();
    }
}
