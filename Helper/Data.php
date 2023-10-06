<?php
/**
 * @author FYB Romania
 * @copyright Copyright (c) FYB Romania (https://fyb.ro)
 * @package Popup Products for Magento 2
 */

namespace Fyb\PopupProducts\Helper;

use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        Context $context,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
    ) {
        parent::__construct($context);

        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param string $path
     * @param string $scope
     *
     * @return mixed
     */
    public function getConfigValue($path, $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue($path, $scope);
    }

    /**
     * @return bool
     */
    public function isRedirectToCartEnabled()
    {
        return (bool)$this->getConfigValue('checkout/cart/redirect_to_cart');
    }

    /**
     * @return bool
     */
    public function openPopupAfterRedirect()
    {
        return (int)$this->getConfigValue('fyb_popup_products/general/popup_force') == 2;
    }

    /**
     * @return bool
     */
    public function openPopupBeforeRedirect()
    {
        return (int)$this->getConfigValue('fyb_popup_products/general/popup_force') == 1;
    }

    /**
     * @return mixed
     */
    public function getAllConfig()
    {
        return $this->getConfigValue('fyb_popup_products/general');
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->getConfigValue('fyb_popup_products/general/enable');
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->getConfigValue('fyb_popup_products/general/popup_title');
    }

    /**
     * @return int
     */
    public function getMainCategory()
    {
        return (int)$this->scopeConfig->getValue('fyb_popup_products/general/main_category');
    }

    /**
     * @return array
     */
    public function getExcludedCategories()
    {
        $excludedCategories = explode(
            ',',
            $this->scopeConfig->getValue('fyb_popup_products/general/exclude_categories')
        );
        $isDisabledForPopupCategories = (bool)$this->scopeConfig->getValue(
            'fyb_popup_products/general/exclude_selected_category'
        );

        if ($isDisabledForPopupCategories) {
            $popupCategories = $this->getPopupCategories(true);
            $excludedCategories = array_unique(array_merge($excludedCategories, $popupCategories));
        }

        return $excludedCategories;
    }

    /**
     * @param bool $onlyIds
     *
     * @return array|\Magento\Catalog\Model\Category[]|\Magento\Catalog\Model\ResourceModel\Category\Collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPopupCategories($onlyIds = false)
    {
        $categoryId = $this->getMainCategory();
        $mainCategory = $this->categoryRepository->get($categoryId);
        $subCategories = $mainCategory->getChildrenCategories();

        if ($onlyIds) {
            $ids = [];
            foreach ($subCategories as $key => $category) {
                $ids[] = $category->getId();
            }

            return $ids;
        }

        return $subCategories;
    }

    /**
     * @return int
     */
    public function getMaxProducts()
    {
        return (int)$this->scopeConfig->getValue('fyb_popup_products/general/max_products');
    }

    /**
     * @return int
     */
    public function getRedirectType()
    {
        return (int)$this->scopeConfig->getValue('fyb_popup_products/general/redirect_to');
    }
}
