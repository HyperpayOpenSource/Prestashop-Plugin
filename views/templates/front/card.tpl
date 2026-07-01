{extends file='page.tpl'}



{block name="content"}
<section id="iframe" style="display: flex; align-items: center;">

   

    {assign var="brandsList" value=$brands}
    {assign var="shouldValidateMada" value=false}

    {if strpos($brands, 'VISA') !== false || strpos($brands, 'MASTER') !== false}
        {assign var="brandsList" value="`$brands` MADA"}
        {assign var="shouldValidateMada" value=true}
    {/if}

    <script nonce="{$NONCE_ID}">
        let shouldValidateMada = {if $shouldValidateMada}true{else}false{/if};

        function findGetParameter(parameterName) {
            var result = null,
                tmp = [];

            location.search
                .substr(1)
                .split("&")
                .forEach(function (item) {
                    tmp = item.split("=");

                    if (tmp[0] === parameterName) {
                        result = decodeURIComponent(tmp[1]);
                    }
                });

            return result;
        }

        var paymentMethod = findGetParameter('method');

        function displayName(element) {
            $('.wpwl-brand-card').each(function () {
                $(element).append(this);
            });
        }

        function validateHolder(e){
            var holder = ($('.wpwl-control-cardHolder').val() || '').trim();
            if (holder.trim().length < 2 || /\p{ldelim}Extended_Pictographic{rdelim}/u.test(holder) ){
                $('.wpwl-control-cardHolder').addClass('wpwl-has-error')
                .after('<div class="wpwl-hint wpwl-hint-cardHolderError">Invalid card holder</div>');
                return false;
            }
            return true;
        }


        var wpwlOptions = {
            style: "{$cardStyle}",
            locale: "{$locale}",
            paymentTarget: "_top",

            registrations: {
                requireCvv: true,
                hideInitialPaymentForms: true
            },

            browser: {
                threeDChallengeWindow: 5
            },

            onBlurCardNumber: function (isValid) {
                let paymentBrand = this.$form.find('.wpwl-control-brand').val();

                if (shouldValidateMada  && paymentBrand == "MADA") {
                    setTimeout(function () {
                        $('.wpwl-hint-cardNumberError').remove();
                        $('.wpwl-control-cardNumber').removeClass('wpwl-has-error');

                        $('.wpwl-control-cardNumber')
                            .addClass('wpwl-has-error')
                            .after('<div class="wpwl-hint wpwl-hint-cardNumberError">mada card is not allowed, please choose mada debit card from the payment options</div>');

                        $('.wpwl-button-pay').prop('disabled', true);
                    }, 5);
                }
            },
            onBeforeSubmitCard: function(e){
                return validateHolder(e);
            },

            onReady: function () {
                $('.wpwl-form-card').find('.wpwl-button-pay').on('click', function(e){
                    validateHolder(e);
                });

                $('.wpwl-group.wpwl-group-brand').hide();

                if (paymentMethod === 'MADA') {
                    $('.wpwl-wrapper-cardNumber').each(function () {
                        displayName(this);
                    });
                }

                var createRegistrationHtml =
                    '<div class="customLabel">{l s='Store payment details?' }</div>' +
                    '<div class="customInput">' +
                    '<input type="checkbox" name="createRegistration" value="true" />' +
                    '</div>';

                $('form.wpwl-form-card')
                    .find('.wpwl-button')
                    .before(createRegistrationHtml);

                $('.wpwl-control-brand').hide();
                $('.wpwl-label-brand').hide();

                $('.wpwl-form-virtualAccount-STC_PAY .wpwl-wrapper-radio-qrcode').hide();
                $('.wpwl-form-virtualAccount-STC_PAY .wpwl-wrapper-radio-mobile').hide();
                $('.wpwl-form-virtualAccount-STC_PAY .wpwl-group-paymentMode').hide();
                $('.wpwl-form-virtualAccount-STC_PAY .wpwl-group-mobilePhone').show();

                $('.wpwl-form-virtualAccount-STC_PAY .wpwl-wrapper-radio-mobile .wpwl-control-radio-mobile')
                    .attr('checked', true);

                $('.wpwl-form-virtualAccount-STC_PAY .wpwl-wrapper-radio-mobile .wpwl-control-radio-mobile')
                    .trigger('click');
            }
        };

        wpwlOptions.applePay = {
            merchantCapabilities: ["supports3DS"],
            supportedNetworks: ["amex", "masterCard", "visa", "mada"]
        };

        wpwlOptions.googlePay = {
            gatewayMerchantId: '{$entityId}',
            merchantId: '{$googlePayMerchantId}'
        };
    </script>

    <script
        src="{$originUrl}paymentWidgets.js?checkoutId={$checkoutId}"
        integrity="{$integrity}"
        crossorigin="anonymous"
    ></script>

    <form
        action="{$src}"
        class="paymentWidgets"
        data-brands="{$brandsList}"
    ></form>

    <style>
        {$cardCss nofilter}

        .wpwl-control-cardNumber,
        .wpwl-control-cvv {
            direction: ltr !important;
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

        #iframe div {
            flex: 1;
        }

        .wpwl-brand-MADA {
            display: block;
            visibility: visible;
            position: absolute;
            right: 8px;
            top: 7px;
            width: 65px;
            z-index: 10;
            float: right;
        }

        .wpwl-brand-MASTER {
            top: 0;
        }
    </style>

    {if $locale eq 'ar'}
        <style>
            .wpwl-brand-MADA {
                right: unset !important;
                left: 8px !important;
            }

            .wpwl-control-cardNumber,
            .wpwl-control-cvv {
                direction: rtl !important;
            }
        </style>
    {/if}

</section>
{/block}