{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=5")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' }">{l s='Checkout' }</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Hyperpay Payment' }
{/capture}


<h2>{l s='Order summary' }</h2>

{assign var='current_step' value='payment'}

{include file="$tpl_dir./order-steps.tpl"}


  <section>
    <p>Oops Payment failed</p>
    <p>Reason\s:</p>
    <ul>
      {foreach from=$params key=name item=value}
        <li>{$name}: {$value}</li>
      {/foreach}
    </ul>
    <p>Try to contact the admin, or try again</p>
  </section>
