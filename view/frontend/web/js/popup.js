define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'jquery/jquery-storageapi',
], function ($, modal) {
    'use strict';

    var modalProductsOverlay;

    function initModalProducts(params, result) {
        var sections = result[0];
        var productName = '';
        if (result[1]) {
            productName = $.mage.__('You added product <b>%1</b> to the cart').replace('%1', result[1]);
        }
        modalProductsOverlay = $('<div></div>', {
            id: "popupProducts"
        });
        modalProductsOverlay.hide().appendTo($('body'));

        var productsDiv = $('<form></form>', {
            id: "popupProductsList",
            class: "products-grid fyb-popup-products-list products-grid grid",
            action: '/fyb/cart/addPopupProducts',
            method: 'post'
        });

        productsDiv.append($('<input type="hidden" name="form_key" value="' + $('input[name="form_key"]').val() + '">'));

        $.each(sections, function (_, section) {
            var productSection = $('<div></div>', {
                class: "popup-products-section"
            })
            productSection.append($('<h3>' + section.title +'</h3>'))
            var sectionContainer =  $('<ol></ol>', {
                class: "product-items widget-popup-products"
            });

            $.each(section.products, function (_, product) {
                var productElem = '<div class="product-item-info">' +
                    '<input type="checkbox" class="checkbox related-popup-product" name="related_popup_products[]" value="' + product.id + '">' +
                    '<img alt="' + product.sku + '" src="' + product.image + '"/>' +
                    '<div class="product-item-details">' +
                    '<div class="product-item-details-name">' + product.name + '</div>' +
                    '<div class="price-box price-final_price">' +
                    (product.regular_price != product.final_price ?'<span class="old-price">' + product.regular_price_container + '</span>': '') +
                    '<span class="special-price">' + product.final_price_container + '</span>' +
                    '</div>'+
                    '</div>' +
                    '</div>';

                var productContainer =  $('<li></li>', {
                    class: "widget-product-item "
                });
                productContainer.append(productElem)
                sectionContainer.append(productContainer);
            });

            productSection.append(sectionContainer);
            productsDiv.append(productSection);
        });

        modalProductsOverlay.append(productsDiv);
        modalProductsOverlay.find('.product-item').on('click', function(e) {
            var input = $(e.target).closest('.product-item').find('input');
            if (input.prop('checked')) {
                input.removeAttr('checked');
            } else {
                input.attr('checked', true);
            }
        });

        // Modify initial form if redirect to cart enabled and popup is opened to add related products also
        productsDiv.on('submit', function (e) {
            if (window.formQueue) {
                e.preventDefault();
                e.stopPropagation();

                var newForm = $('<form></form>', {
                    action: $(window.formQueue).attr('action'),
                    method: 'post'
                })
                newForm.append($(window.formQueue).html());
                window.formQueue = null;
                var relatedProducts = modalProductsOverlay.find('.product-item input:checked');
                var addedProducts = [];
                $.each(relatedProducts, function (_, product) {
                    addedProducts.push(product.value);
                });

                if (addedProducts.length) {
                    var checkedProduct = $('<input />', {
                        name: "related_product",
                        type: 'hidden',
                        value: addedProducts.join(',')
                    });
                    $(newForm).append(checkedProduct);
                }
                $(newForm).appendTo($('body')).hide();
                $(newForm).submit();
            } else {
                if (!modalProductsOverlay.find('.product-item input:checked').length) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }
        });

        var options = {
            type: 'popup',
            innerScroll:true,
            title: (productName ? productName + '<br/>': '') + params.title,
            modalVisibleClass: "_show fyb-popup-products",
            buttons: [{
                text: $.mage.__(params.buttonText),
                class: '',
                click: function () {
                    $(productsDiv).submit();
                    this.closeModal();
                }
            }],
            closed: function (){
                if (window.formQueue) {
                    $(window.formQueue).submit();
                }
            }
        };

        modal(options, modalProductsOverlay);
    }

    function confirm(params) {
        $.ajax({
            url: '/rest/V1/get-popup-products',
            data: {
                categoryId: params.categoryId,
                lastProductId: window.lastAddedProductId
            },
            showLoader: true,
            contentType: 'application/json',
            type: 'GET',
            success: function(res) {
                initModalProducts(params, res);
                modalProductsOverlay.modal('openModal');
            },
            error: function (res) {
                if (window.formQueue) {
                    $(window.formQueue).submit();
                }
            },
        });
    }

    return function () {
        var storage = $.initNamespaceStorage('mage-cache-storage').localStorage;
        if (storage.get('openPopup')) {
            window.popupProducts.redirectAfterClose = false;
            window.lastAddedProductId = storage.get('lastAddedProductId');
            storage.set('openPopup', false);
        }

        confirm(window.popupProducts);
    };
});
