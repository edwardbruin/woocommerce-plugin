var testcheckoutlink =  paydock.testcheckoutlink ;
var testcheckouttoken =  paydock.testcheckouttoken ;
var testtoken =  paydock.testtoken ;
// console.log(window.location.search);
// var partsArray = window.location.search.split('&');

// if (getQueryVariable('status') != false) {
//     var testtoken = makeToken(testcheckouttoken);
// }

// function makeToken(checkouttoken) {
//     //send amount, currency and checkouttoken off to become paydock token
//     console.log('trying ajax');
//     jQuery.ajax({url: 'https://requestb.in/1k77kgw1'});
//     console.log('tried ajax');
//     return testtoken;
// }

function getQueryVariable(variable) {
    var query = window.location.search.substring(1);
    var vars = query.split('&');
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split('=');
        // console.log(pair[0]);
        // console.log(pair[1]);
        if (decodeURIComponent(pair[0]) == variable) {
            return decodeURIComponent(pair[1]);
        }
    }
    console.log('Query variable %s not found', variable);
    return false;
}

function paydockFormHandler() {

    var $form = jQuery( 'form.checkout, form#order_review' );
    var $ccForm = jQuery( '#wc-paydock-cc-form' );
    // console.log('the form action is ' + $form.attr('action'));

    if ( jQuery( '#payment_method_paydock' ).is( ':checked' ) && testtoken && (getQueryVariable('status') != false)) {

        $form.block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

        //Insert the token into the form so it gets submitted to the server
        $ccForm.append( '<input type="hidden" class="paydockToken" name="paydockToken" value="' + testtoken + '"/>' );
        
        console.log('unbinding event');
        $form.unbind().submit();

        console.log('submitting form');
        $form.submit();
        console.log('loop escaped');

        // Prevent the form from submitting
        return true;
    } else {
        console.log('not working fam');
        // Show the errors on the form
        jQuery( '.woocommerce-error, .paydockToken', $ccForm ).remove();
        $form.unblock();

        $ccForm.prepend( '<ul class="woocommerce-error"><li>No token found please refresh the page and try again</li></ul>' );

        //console.log('errors', res);
    }
    return false;
}

function myScript(){
    var iframe = document.createElement('iframe');
    // var html = '<body>Foo</body>';
    iframe.src = testcheckoutlink;
    iframe.height = "800px";
    document.body.appendChild(iframe);
    // console.log('iframe.contentWindow =', iframe.contentWindow);
}

jQuery( function () {

    // window.onbeforeunload = function (e) {
    //     console.log(e);
    //     var e = e || window.event;

    //     // For IE and Firefox prior to version 4
    //     if (e) {
    //         e.returnValue = 'Are you sure you want to leave the site?';
    //     }

    //     // For Safari
    //     return 'Are you sure you want to leave the site?';
    // };

    // jQuery('#button_id').addEventListener("click", myScript);

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
        jQuery( '.paydockToken' ).remove();
        if (testtoken) {
            // console.log(testtoken);
            var $ccForm = jQuery( '#wc-paydock-cc-form' );
            $ccForm.append( '<a onclick="myScript(); return false">click me for afterpay</a>' );
        } else {
            console.log("no token found");
        }
    });

    jQuery( 'form.checkout, form#order_review' ).on( 'blur', '#wc-paydock-cc-form input', function() {
        jQuery( '.paydockToken' ).remove();
    });

});