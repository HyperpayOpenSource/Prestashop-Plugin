{extends "$layout"}

{block name="content"}


<section id="iframe" style="display: flex;align-items: center;">

    <script>
        function findGetParameter(parameterName) {
            var result = null,
                tmp = [];
            location.search
                .substr(1)
                .split("&")
                .forEach(function (item) {
                    tmp = item.split("=");
                    if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
                });
            return result;
        }
        var paymentMethod = findGetParameter('method');

        function displayName(element) {
            $('.wpwl-brand-card').each(function () {
                $(element).append(this);
                console.log("displayName");
            });
        }

    var wpwlOptions = {
        style: "{$cardStyle}",
        locale: "{$locale}",
        paymentTarget: "no16_top",
        registrations: {
            requireCvv: true,
            hideInitialPaymentForms: true
        },
        onReady: function() {
            if(paymentMethod === 'MADA') {
                $('.wpwl-wrapper-cardNumber').each(function () {
                    displayName(this);
                });
            }
    var createRegistrationHtml = '<div class="customLabel">{l s='Store payment details?' }</div><div class="customInput"><input type="checkbox" name="createRegistration" value="true" /></div>';
    $('form.wpwl-form-card').find('.wpwl-button').before(createRegistrationHtml);
    $('.wpwl-control-brand').hide();
    $('.wpwl-label-brand').hide();

    $('.wpwl-form-virtualAccount-STC_PAY .wpwl-wrapper-radio-qrcode').hide();
    $('.wpwl-form-virtualAccount-STC_PAY .wpwl-wrapper-radio-mobile').hide();
    $('.wpwl-form-virtualAccount-STC_PAY .wpwl-group-paymentMode').hide();
    $('.wpwl-form-virtualAccount-STC_PAY .wpwl-group-mobilePhone').show();
    $('.wpwl-form-virtualAccount-STC_PAY .wpwl-wrapper-radio-mobile .wpwl-control-radio-mobile').attr('checked',true);
    $('.wpwl-form-virtualAccount-STC_PAY .wpwl-wrapper-radio-mobile .wpwl-control-radio-mobile').trigger('click');
  }
    }
    wpwlOptions.applePay = {
    merchantCapabilities: ["supports3DS"],
    supportedNetworks: ["amex", "masterCard", "visa", "mada"]
}
    </script>
    <script src="{$originUrl}paymentWidgets.js?checkoutId={$checkoutId}"></script>

    <form action="{$src}" class="paymentWidgets" data-brands="{$brands}"></form>


    <style>
    {$cardCss nofilter}

    .wpwl-control-cardNumber,.wpwl-control-cvv{
        direction:ltr!important;
    }
    .js-payment-hyperpay.disabled form {
        display: none;
    }

    .spinner {
        display: none;
    }

    .wpwl-container.wpwl-container-card.wpwl-clearfix {
        display: block;
        margin: 40px;
    }
    #iframe div{
        flex:1;
    }

    .wpwl-brand-MADA{
        display:block;
        visibility:visible;
        position:absolute;
        right:8px;
        top:7px;
        width:65px;
        z-index:10;
        float:right;
    }
    .wpwl-brand-MASTER{
        top:0px;
    }
    </style>
</section>
{/block}
