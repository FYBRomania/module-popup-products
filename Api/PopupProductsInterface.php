<?php
/**
 * @author FYB Romania
 * @copyright Copyright (c) FYB Romania (https://fyb.ro)
 * @package Popup Products for Magento 2
 */

namespace Fyb\PopupProducts\Api;

interface PopupProductsInterface
{
    /**
     * @param int $categoryId
     * @param int|null $lastProductId
     *
     * @return array
     */
    public function get($categoryId, $lastProductId = null);
}
