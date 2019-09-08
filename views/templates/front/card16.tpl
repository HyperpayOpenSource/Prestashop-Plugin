{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' }">{l s='Checkout' }</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Hyperpay Payment' }
{/capture}


<h2>{l s='Order summary' }</h2>

{assign var='current_step' value='payment'}

{include file="$tpl_dir./order-steps.tpl"}




<section id="iframe" style="display: flex;align-items: center;">

    <script>
    var wpwlOptions = {
        style: "{$cardStyle}",
        locale: "{$locale}",
        paymentTarget: "_top",
        registrations: {
            requireCvv: true,
            hideInitialPaymentForms: true
        },
        onReady: function() {
    var createRegistrationHtml = '<div class="customLabel">{l s='Store payment details?' }</div><div class="customInput"><input type="checkbox" name="createRegistration" value="true" /></div>';
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
    </style>
</section>