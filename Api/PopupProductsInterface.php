<?php

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
