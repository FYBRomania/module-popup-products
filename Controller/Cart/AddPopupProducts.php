<?php
/**
 * @author FYB Romania
 * @copyright Copyright (c) FYB Romania (https://fyb.ro)
 * @package Popup Products for Magento 2
 */

namespace Fyb\PopupProducts\Controller\Cart;

class AddPopupProducts extends \Magento\Checkout\Controller\Cart\Add
{
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

        $related = $this->getRequest()->getParam('related_popup_products');
        try {
            if (!empty($related)) {
                $this->cart->addProductsByIds($related);
            }
            $this->cart->save();

            $message = __('You successfully added products to your shopping cart.',);
            $this->messageManager->addSuccessMessage($message);

            if ($this->cart->getQuote()->getHasError()) {
                $errors = $this->cart->getQuote()->getErrors();
                foreach ($errors as $error) {
                    $this->messageManager->addErrorMessage($error->getText());
                }
            }

            return $this->goBack();
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t add this item to your shopping cart right now.')
            );
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);

            return $this->goBack();
        }
    }
}
