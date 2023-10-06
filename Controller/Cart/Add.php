<?php
/**
 * @author FYB Romania
 * @copyright Copyright (c) FYB Romania (https://fyb.ro)
 * @package Popup Products for Magento 2
 */

namespace Fyb\PopupProducts\Controller\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;

class Add extends \Magento\Checkout\Controller\Cart\Add
{
    /**
     * @var \Fyb\PopupProducts\Helper\Data
     */
    protected $popupHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Fyb\PopupProducts\Helper\Data $popupHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        \Fyb\PopupProducts\Helper\Data $popupHelper
    ) {
        parent::__construct(
            $context, $scopeConfig, $checkoutSession, $storeManager, $formKeyValidator, $cart, $productRepository
        );

        $this->popupHelper = $popupHelper;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param array $errors
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function getJsonResponse($product, $errors = [])
    {
        $result = [
            'id' => $product->getId(),
            'sku' => $product->getSku(),
            'productName' => $product->getName(),
            'success' => !$errors,
            'categoryId' => $this->popupHelper->getMainCategory(),
            'openPopup' => false,
            'openPopupAfter' => false,
        ];

        if ($result['success']) {
            $productCategories = $product->getCategoryIds();
            $excludedCategories = $this->popupHelper->getExcludedCategories();

            $hasExcluded = !empty(array_intersect($productCategories, $excludedCategories));
            if (!$hasExcluded) {
                $result['openPopup'] = true;
                $result['popupTitle'] = __($this->popupHelper->getTitle())->render();
                $result['buttonText'] = __("Continue")->render();
            }
        }

        if ($this->popupHelper->isRedirectToCartEnabled()) {
            $result['redirect_url'] = $this->popupHelper->getRedirectType() == 2 ? $this->getCartUrl(
            ) : $this->getCheckoutUrl();

            $result['openPopupAfter'] = $this->popupHelper->openPopupAfterRedirect();
        }

        $this->getResponse()->representJson(
            $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($result)
        );

        return $this->getResponse();
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(
                __('Your session has expired')
            );

            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $params = $this->getRequest()->getParams();
        try {
            if (isset($params['qty'])) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    [
                        'locale' => $this->_objectManager->get(
                            \Magento\Framework\Locale\ResolverInterface::class
                        )->getLocale(),
                    ]
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            /** Check product availability */
            if (!$product) {
                return $this->goBack();
            }

            $this->cart->addProduct($product, $params);
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }
            $this->cart->save();

            /**
             * @todo remove wishlist observer \Magento\Wishlist\Observer\AddToCart
             */
            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );

            if (!$this->_checkoutSession->getNoCartRedirect(true)) {
                if ($this->shouldRedirectToCart()) {
                    $message = __(
                        'You added %1 to your shopping cart.',
                        $product->getName()
                    );
                    $this->messageManager->addSuccessMessage($message);
                } else {
                    $this->messageManager->addComplexSuccessMessage(
                        'addCartSuccessMessage',
                        [
                            'product_name' => $product->getName(),
                            'cart_url' => $this->getCartUrl(),
                        ]
                    );
                }

                $errors = [];
                if ($this->cart->getQuote()->getHasError()) {
                    $errors = $this->cart->getQuote()->getErrors();
                    foreach ($errors as $error) {
                        $this->messageManager->addErrorMessage($error->getText());
                    }
                }

                return $this->getJsonResponse($product, $errors);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNoticeMessage(
                    $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addErrorMessage(
                        $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($message)
                    );
                }
            }

            $url = $this->_checkoutSession->getRedirectUrl(true);
            if (!$url) {
                $url = $this->_redirect->getRedirectUrl($this->getCartUrl());
            }

            return $this->goBack($url);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t add this item to your shopping cart right now.')
            );
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);

            return $this->goBack();
        }

        return $this->getResponse();
    }

    /**
     * Returns cart url
     *
     * @return string
     */
    private function getCartUrl()
    {
        return $this->_url->getUrl('checkout/cart', ['_secure' => true]);
    }

    /**
     * Returns checkout url
     *
     * @return string
     */
    private function getCheckoutUrl()
    {
        return $this->_url->getUrl('checkout', ['_secure' => true]);
    }

    /**
     * Is redirect should be performed after the product was added to cart.
     *
     * @return bool
     */
    private function shouldRedirectToCart()
    {
        return $this->_scopeConfig->isSetFlag(
            'checkout/cart/redirect_to_cart',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
