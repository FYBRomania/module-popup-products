define([
    "jquery",
    'underscore',
    "Magento_Ui/js/modal/modal",
    'mage/translate',
    'Magento_Catalog/js/catalog-add-to-cart',
    'Magento_Catalog/product/view/validation',
    'Fyb_PopupProducts/js/popup',
    'jquery/jquery-storageapi',
], function ($, _, modal, $t, catalogAddToCart, validation, popupOverlay) {
    $.widget('mage.fybCart', {
        options: {
            topCartSelector: '[data-block="minicart"]',
            addToCartButtonSelector: '.action.tocart'
        },
        addToCartButtonDisabledClass: 'disabled',

        _create: function (options) {
            var self = this;

            self.bindSubmit();
        },

        bindSubmit: function() {
            var self = this;

            if ($('body').is('.checkout-cart-configure') || !self.options.openPopupOnAdd) {
                return;
            }

            this.element.attr('submit-observed', true);
            this.element.off( "submit" );
            this.element.on('submit', function(e) {
                //disable for paypal button
                if ($(this).find('input[data-role="pp-checkout-url"][name="return_url"]').length > 0) {
                    return true;
                }

                e.preventDefault();
                e.stopImmediatePropagation();
                e.stopPropagation();

                var validator = $(this).validation({ radioCheckboxClosest: '.nested'});
                if (validator.valid()) {
                    self.submitForm($(this));
                }
            });
        },

        submitForm: function(form) {
            var self = this;
            if (form.has('input[type="file"]').length && form.find('input[type="file"]').val() !== '') {
                self.element.off('submit');
                form.submit();
            } else {
                self.ajaxSubmit(form);
            }
        },

        disableButton: function (form) {
            var addToCartButton = $(form).find(this.options.addToCartButtonSelector);
            addToCartButton.addClass(this.addToCartButtonDisabledClass);
        },

        enableButton: function(form) {
            var self = this,
                addToCartButton = $(form).find(this.options.addToCartButtonSelector);
            addToCartButton.removeClass(self.addToCartButtonDisabledClass);
        },

        isWishlistForm: function (form) {
            var actionUrl = form.attr('action');

            return actionUrl.indexOf('wishlist/index/cart') > 0;
        },

        getProductId: function (form) {
            var productId = 0;
            if (form.attr('action') && form.attr('action').length) {
                productId = _.findWhere(form.serializeArray(), {
                    name: 'product'
                });

                if (!_.isUndefined(productId)) {
                    productId = productId.value;
                }

                if (!productId) {
                    productId = form.attr('action').match(/(?:product\/)(\d+)\/(?=uenc)?/);
                    productId = Array.isArray(productId) ? parseInt(productId.shift().replace(/[^\d;]/g, '')) : 0;
                }
            }

            return productId;
        },

        ajaxSubmit: function(form) {
            var self = this;

            if (form.attr('action') && self.isWishlistForm(form)){
                return false;
            }

            var data = form.serialize();
            var productId = self.getProductId(form);

            if (productId > 0) {
                data += '&product=' + productId;
            } else {
                return false;
            }

            var url = self.options.sendUrl;

            self.disableButton(form);
            $.ajax({
                url: url,
                data: data,
                type: 'post',
                dataType: 'json',
                cache: false,
                showLoader: true,
                success: function(response) {
                    if (!response.success) {
                        return;
                    }

                    if (response.redirect_url && (!response.openPopup || response.openPopupAfter)) {
                        if (response.openPopupAfter && response.openPopup) {
                            var storage = $.initNamespaceStorage('mage-cache-storage').localStorage;
                            storage.set('popupConfig', response);
                        }
                        window.location = response.redirect_url;
                        return true;
                    }

                    $(document).trigger('ajax:addToCart', {
                        productIds:[
                            response.product_id
                        ]
                    });

                    try {
                        if (response.openPopup) {
                            popupOverlay(response);
                        }
                    } catch(e) {
                        console.warn(e);
                    }
                },
                complete: function (res) {
                    if (res.state() === 'rejected') {
                        location.reload();
                    }
                }
            }).always(function() {
                self.enableButton(form);
            });

            return false;
        }
    });

    return $.mage.fybCart;
})
