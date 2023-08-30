<?php

namespace Fyb\PopupProducts\Block;

use Magento\Framework\View\Element\Template;

class Config extends \Magento\Framework\View\Element\Template
{
    const POPUP_PAGES = [
         'checkout_index_index',
         'checkout_cart_index'
    ];

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonSerializer;

    /**
     * @var \Fyb\PopupProducts\Helper\Data
     */
    protected $helper;

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

    public function getJsonConfig()
    {
        $config = [
            'isEnabled' => $this->helper->isEnabled(),
            'title' => __($this->helper->getTitle()),
            'categoryId' => $this->helper->getMainCategory(),
            'isRedirectToCartEnabled' => $this->helper->isRedirectToCartEnabled(),
            'openPopupAfterRedirect' => $this->helper->openPopupAfterRedirect() && $this->helper->isRedirectToCartEnabled(),
            'openPopupBeforeRedirect' => $this->helper->openPopupBeforeRedirect() && $this->helper->isRedirectToCartEnabled(),
            'buttonText' => __("Continue"),
        ];

        return $this->jsonSerializer->serialize($config);
    }
}
