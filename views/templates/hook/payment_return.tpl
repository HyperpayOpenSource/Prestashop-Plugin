<section id="hyperpay-payment-return">
  {if $hyperpay_pending}
    <p>{l s='Your payment is awaiting confirmation from the payment provider.' mod='hyperpay'}</p>
    <p>{l s='Your order will be updated as soon as the payment is confirmed.' mod='hyperpay'}</p>
  {else}
    <p>{l s='Your payment has been received and your order is confirmed.' mod='hyperpay'}</p>
  {/if}
</section>
