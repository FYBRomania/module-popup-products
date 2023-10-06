<?php
/**
 * @author FYB Romania
 * @copyright Copyright (c) FYB Romania (https://fyb.ro)
 * @package Popup Products for Magento 2
 */

namespace Fyb\PopupProducts\Model\Config\Source;

use Magento\Catalog\Helper\Category;

class CategoryList implements \Magento\Framework\Data\OptionSourceInterface
{

    /**
     * @var \Magento\Catalog\Helper\Category
     */
    protected $_categoryHelper;

    /**
     * @param \Magento\Catalog\Helper\Category $catalogCategory
     */
    public function __construct(Category $catalogCategory)
    {
        $this->_categoryHelper = $catalogCategory;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $categories = $this->_categoryHelper->getStoreCategories(true, true, false);
        $options = [['label' => __('-- Please Select a Parent Category --'), 'value' => '']];
        /** @var \Magento\Catalog\Model\Category $category */
        foreach ($categories as $category) {
            if ($category->getChildrenCount()) {
                $options[] = [
                    'value' => $category->getEntityId(),
                    'label' => $this->getCategoryName($category),
                ];
            }
        }

        return $options;
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     *
     * @return string
     */
    protected function getCategoryName($category)
    {
        $name = [];
        foreach ($category->getParentCategories() as $categoryParent) {
            $name[] = __($categoryParent->getName());
        }

        return implode(' > ', $name);
    }
}
