{extends file='page.tpl'}

{block name="content"}
  <section id="hyperpay-redirect">
    <form id="hyperpay_server_to_server_form" method="{$redirectMethod|escape:'htmlall':'UTF-8'}" action="{$redirectUrl|escape:'htmlall':'UTF-8'}">
      {foreach from=$redirectFields item=field}
        <input type="hidden" name="{$field.name|escape:'htmlall':'UTF-8'}" value="{$field.value|escape:'htmlall':'UTF-8'}">
      {/foreach}
      <p>{l s='Redirecting you to the payment page...' mod='hyperpay'}</p>
      <button type="submit" class="btn btn-primary">{l s='Continue to payment' mod='hyperpay'}</button>
    </form>
    <script>
      document.getElementById('hyperpay_server_to_server_form').submit();
    </script>
  </section>
{/block}
