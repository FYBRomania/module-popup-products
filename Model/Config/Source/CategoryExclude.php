<?php
/**
 * @author FYB Romania
 * @copyright Copyright (c) FYB Romania (https://fyb.ro)
 * @package Popup Products for Magento 2
 */
namespace Fyb\PopupProducts\Model\Config\Source;

class CategoryExclude implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $_categoryCollectionFactory;

    /**
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
    ) {
        $this->_categoryFactory = $categoryFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @param int $parentCategoryId
     * @param int $level
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCategoryCollection($parentCategoryId = null, $level = null)
    {
        $collection = $this->_categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name');

        $collection->addAttributeToFilter('level', $level ?: 1);
        $collection->addAttributeToFilter('parent_id', $parentCategoryId ?: 1);

        $collection->addAttributeToFilter('is_active', 1);
        $collection->setOrder('position', 'asc');

        return $collection;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $arr = $this->toArray();
        $optionArray = [['label' => __('-- No Exclude Categories --'), 'value' => '']];
        foreach ($arr as $value => $label) {
            $optionArray[] = [
                'value' => $value,
                'label' => $label
            ];
        }
        return $optionArray;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getChildren();
    }

    /**
     * @param int $parentCategoryId
     * @param int $level
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getChildren($parentCategoryId = null, $level = null)
    {
        $collection = $this->getCategoryCollection($parentCategoryId, $level);

        $options = [];
        foreach ($collection as $category) {
            if ($category->getLevel() > 1) {
                $options[$category->getId()] =
                    str_repeat(". ", max(0, ($category->getLevel() - 2) * 3)) . $category->getName();
            }
            if ($category->hasChildren()) {
                $options = array_replace($options, $this->getChildren($category->getId(), $category->getLevel() + 1));
            }
        }

        return $options;
    }
}
