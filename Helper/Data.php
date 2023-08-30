<?php

namespace Fyb\PopupProducts\Helper;

use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function getConfigValue($path, $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue($path, $scope);
    }

    public function isRedirectToCartEnabled()
    {
        return (float)$this->getConfigValue('checkout/cart/redirect_to_cart');
    }

    public function openPopupAfterRedirect()
    {
        return (float)$this->getConfigValue('fyb_popup_products/general/popup_force') == 2;
    }

    public function openPopupBeforeRedirect()
    {
        return (float)$this->getConfigValue('fyb_popup_products/general/popup_force') == 1;
    }

    public function getAllConfig()
    {
        return $this->getConfigValue('fyb_popup_products/general');
    }

    public function isEnabled()
    {
        return (float)$this->getConfigValue('fyb_popup_products/general/enable');
    }

    public function getTitle()
    {
        return $this->getConfigValue('fyb_popup_products/general/popup_title');
    }

    public function getMainCategory()
    {
        return (int)$this->scopeConfig->getValue('fyb_popup_products/general/main_category');
    }

    public function getMaxProducts()
    {
        return (int)$this->scopeConfig->getValue('fyb_popup_products/general/max_products');
    }

    public function getRedirectType()
    {
        return (int)$this->scopeConfig->getValue('fyb_popup_products/general/redirect_to');
    }
}
