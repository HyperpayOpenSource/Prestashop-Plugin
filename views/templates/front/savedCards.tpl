
{extends "$layout"}

{block name="content"}

<h1 class="page-heading">{l s='My Saved Cards' mod='hyperpay'}</h1>
{if isset($card_deleted)}
	<p class="alert alert-success">
		{l s='Your card has been deleted.' mod='hyperpay'}
	</p>
{/if}

{if empty($cards)}
	<p class="alert alert-warning">
		{l s="You don't have any saved cards." mod='hyperpay'}
	</p>
{/if}

{if !empty($cards)}
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">

            <div class="card">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{l s="Type" mod='hyperpay'}</th>
                                <th>{l s="Card Number" mod='hyperpay'}</th>
                                <th>{l s="Holder" mod='hyperpay'}</th>
                                <th>{l s="Expiry Date" mod='hyperpay'}</th>
                                <th>{l s="Action" mod='hyperpay'}</th>
                            </tr>
                        </thead>
                        <tbody>
                        {foreach from=$cards key=index item=card}
                            <tr>
                                <td scope="row"><img width="75" src="{$smarty.const._PS_BASE_URL_ }{$smarty.const._MODULE_DIR_}hyperpay/views/imgs/payments_logos/{$card['payment_method']}.svg"/></td>
                                <td>{$card['bin']}*****{$card['last_4_digits']}</td>
                                <td>{$card['holder']}</td>
                                <td>{$card['expiry_month']} / {$card['expiry_year']}</td>
                                <td><form method="POST" action="" ><input type="hidden" name="id" value="{$card['id']}"/><input type="submit" name="delete" class="btn btn-danger" value="{l s="Delete" mod='hyperpay'}"/></form></td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                
            </div>

        </div>
    </div>
</div>
{/if}

{/block}

