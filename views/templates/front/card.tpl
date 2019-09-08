{extends "$layout"}

{block name="content"}


<section id="iframe" style="display: flex;align-items: center;">

    <script>
    var wpwlOptions = {
        style: "{$cardStyle}",
        locale: "{$locale}",
        paymentTarget: "_top",
        onReady: function() {
    var createRegistrationHtml = '<div class="customLabel">Store payment details?</div><div class="customInput"><input type="checkbox" name="createRegistration" value="true" /></div>';
    $('form.wpwl-form-card').find('.wpwl-button').before(createRegistrationHtml);
    $('.wpwl-control-brand').hide();
    $('.wpwl-label-brand').hide();
  }
    }
    </script>
    <script src="{$originUrl}paymentWidgets.js?checkoutId={$checkoutId}"></script>

    <form action="{$src}" class="paymentWidgets" data-brands="{$brands}"></form>


    <style>
    {$cardCss nofilter}

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
    </style>
</section>
{/block}