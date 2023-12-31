<?php
/**
 * @author FYB Romania
 * @copyright Copyright (c) FYB Romania (https://fyb.ro)
 * @package Popup Products for Magento 2
 */

namespace Fyb\PopupProducts\Model;

use Fyb\PopupProducts\Api\PopupProductsInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Area;
use Magento\Catalog\Pricing\Price;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

class PopupProductsRepository implements PopupProductsInterface
{
    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $appEmulation;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $priceHelper;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Fyb\PopupProducts\Helper\Data
     */
    protected $configHelper;

    /**
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilder
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Fyb\PopupProducts\Helper\Data $configHelper
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        Image $imageHelper,
        Emulation $appEmulation,
        StoreManagerInterface $storeManager,
        Data $priceHelper,
        SearchCriteriaBuilderFactory $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        \Fyb\PopupProducts\Helper\Data $configHelper
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->imageHelper = $imageHelper;
        $this->appEmulation = $appEmulation;
        $this->storeManager = $storeManager;
        $this->priceHelper = $priceHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
        $this->configHelper = $configHelper;
    }

    /**
     * @inheritdoc
     */
    public function get($categoryId, $lastProductId = null)
    {
        $mainCategory = $this->categoryRepository->get($categoryId);
        $subCategories = $mainCategory->getChildrenCategories();
        $sections = [];

        $this->appEmulation->startEnvironmentEmulation(
            $this->storeManager->getStore()->getId(), Area::AREA_FRONTEND, true
        );
        foreach ($subCategories as $key => $category) {
            $productCollection = $category->getProductCollection();
            $productCollection->getSelect()->order(['cat_index_position ASC'])
                ->limit($this->configHelper->getMaxProducts());
            $productCollection->addAttributeToFilter('status', Status::STATUS_ENABLED)
                ->addAttributeToFilter('type_id', 'simple');

            $productsIds = $productCollection->getColumnValues('entity_id');
            $products = $this->getProducts($productsIds);

            $categoryProducts = [];
            /** @var \Magento\Catalog\Model\Product $product */
            foreach ($products as $product) {
                $categoryProducts[$product->getId()] = [
                    'name' => $product->getName(),
                    'image' => $this->imageHelper->init($product, 'product_base_image')->getUrl(),
                    'id' => $product->getId(),
                    'sku' => $product->getSku(),
                    'regular_price_container' => $this->getPrice($product, Price\RegularPrice::PRICE_CODE),
                    'final_price_container' => $this->getPrice($product, Price\FinalPrice::PRICE_CODE),
                    'regular_price' => $this->getPrice($product, Price\RegularPrice::PRICE_CODE, true),
                    'final_price' => $this->getPrice($product, Price\FinalPrice::PRICE_CODE, true),
                ];
            }

            if ($categoryProducts) {
                uksort($categoryProducts, function ($a, $b) use ($productsIds) {
                    $indexA = array_search($a, $productsIds);
                    $indexB = array_search($b, $productsIds);

                    return $indexA - $indexB;
                });

                $sections[] = [
                    'id' => (int)$category->getId(),
                    'title' => $category->getName(),
                    'products' => $categoryProducts,
                ];
            }
        }

        $lastProduct = $this->getLastProduct($lastProductId);
        $this->appEmulation->stopEnvironmentEmulation();

        return [$sections, $lastProduct];
    }

    /**
     * @param int $productId
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getLastProduct($productId)
    {
        $lastProduct = '';
        if ($productId) {
            $lastProduct = $this->productRepository->getById($productId)->getName();
        }

        return $lastProduct;
    }

    /**
     * @param int[] $productIds
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    protected function getProducts($productIds)
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilder->create();
        $searchCriteriaBuilder->addFilter('entity_id', $productIds, 'in');
        $searchCriteria = $searchCriteriaBuilder->create();

        return $this->productRepository->getList($searchCriteria)->getItems();
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param string $priceCode
     * @param bool $valueOnly
     *
     * @return float|string
     */
    protected function getPrice($product, $priceCode, $valueOnly = false)
    {
        $priceInfo = $product->getPriceInfo()->getPrice($priceCode);
        $price = $priceInfo->getValue();

        return $valueOnly ? $price : $this->priceHelper->currency($price, true, true);
    }
}
