{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='hyperpay'}">{l s='Checkout' mod='hyperpay'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Hyperpay Payment' mod='hyperpay'}
{/capture}

<section id="hyperpay-redirect">
  <form id="hyperpay_server_to_server_form" method="{$redirectMethod|escape:'htmlall':'UTF-8'}" action="{$redirectUrl|escape:'htmlall':'UTF-8'}">
    {foreach from=$redirectFields item=field}
      <input type="hidden" name="{$field.name|escape:'htmlall':'UTF-8'}" value="{$field.value|escape:'htmlall':'UTF-8'}">
    {/foreach}
    <p>{l s='Redirecting you to the payment page...' mod='hyperpay'}</p>
    <button type="submit" class="button btn btn-default button-medium"><span>{l s='Continue to payment' mod='hyperpay'}</span></button>
  </form>
  <script type="text/javascript">
    document.getElementById('hyperpay_server_to_server_form').submit();
  </script>
</section>
