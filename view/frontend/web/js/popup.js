define([
    'jquery',
    'Magento_Ui/js/modal/modal',
], function ($, modal) {
    'use strict';

    var modalProductsOverlay;

    function initModalProducts(params, result) {
        var sections = result[0];
        var productName = '';
        if (params.productName) {
            productName = $.mage.__('You added product <b>%1</b> to the cart').replace('%1', params.productName);
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
        productsDiv.append($('<input type="hidden" name="return_url" value="' + (params.redirect_url ? params.redirect_url: '') + '">'));

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
        modalProductsOverlay.find('.widget-product-item ').on('click', function(e) {
            if (e.target.type === 'checkbox') {
                return;
            }
            var input = $(e.target).closest('.widget-product-item ').find('input');
            if (input.prop('checked')) {
                input.removeAttr('checked');
            } else {
                input.attr('checked', true);
            }
        });

        var formSubmited = false
        productsDiv.on('submit', function (e) {
            if (!modalProductsOverlay.find('.widget-product-item input:checked').length) {
                e.preventDefault();
                e.stopPropagation();
            } else {
                formSubmited = true;
            }
        });

        var options = {
            type: 'popup',
            innerScroll:true,
            title: (productName ? productName + '<br/>': '') + params.popupTitle,
            modalVisibleClass: "_show fyb-popup-products",
            buttons: [{
                text: params.buttonText,
                class: '',
                click: function () {
                    $(productsDiv).submit();
                    this.closeModal();
                }
            }],
            closed: function (){
                if (!params.openPopupAfter && params.redirect_url) {
                    window.location = params.redirect_url;
                }
                if (params.openPopupAfter && formSubmited) {
                    window.location.reload();
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
            },
            showLoader: true,
            contentType: 'application/json',
            type: 'GET',
            success: function(res) {
                initModalProducts(params, res);
                modalProductsOverlay.modal('openModal');
            }
        });
    }

    return function (config) {
        confirm(config);
    };
});
