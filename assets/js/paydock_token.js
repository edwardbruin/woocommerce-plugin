function getQueryVariable(variable) {
    var query = window.location.search.substring(1);
    var vars = query.split('&');
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split('=');
        if (decodeURIComponent(pair[0]) == variable) {
            return decodeURIComponent(pair[1]);
        }
    }
    return false;
}

function paydockFormHandler() {

    var $form = jQuery( 'form.checkout, form#order_review' );
    var $ccForm = jQuery( '#wc-paydock-cc-form' );

    if ( (jQuery( '#payment_method_paydock' ).is( ':checked' )) && (getQueryVariable('status') == 'SUCCESS') ) {

        $form.block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

        $ccForm.append( '<input type="hidden" class="confirmStatus" name="confirmStatus" value="paymentready"/>' );
        $form.unbind().submit();
        $form.submit();
        return true;
    } else {
        jQuery( '.woocommerce-error, .confirmStatus', $ccForm ).remove();
        $form.unblock();
        $ccForm.prepend( '<ul class="woocommerce-error"><li>No token found please refresh the page and try again</li></ul>' );
    }
    return false;
}

jQuery( function () {

    /* Checkout Form */
    jQuery( 'form.checkout' ).on( 'checkout_place_order_paydock', function () {
        return paydockFormHandler();
    });

    /* Pay Page Form */
    jQuery( 'form#order_review' ).on( 'submit', function (e) {
        e.preventDefault();
        return paydockFormHandler();
    });

    /* Both Forms */
    jQuery( 'form.checkout, form#order_review' ).on( 'change', '#wc-paydock-cc-form input', function() {
        jQuery( '.confirmStatus' ).remove();
    });

    jQuery( 'form.checkout, form#order_review' ).on( 'blur', '#wc-paydock-cc-form input', function() {
        jQuery( '.confirmStatus' ).remove();
    });

});