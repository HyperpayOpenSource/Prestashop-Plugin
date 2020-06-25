<div id="hp-refund">
    <form style="display:inline-block;border:1px solid blue;border-radius:5px;padding:5px;" method="post" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}">
        <input type="hidden" name="id_order" value="{$params.id_order|intval}"/>
    
            <input type="number" step="0.001" name="refundAmount" />
            <button type="submit" class="btn btn-default" name="submitHyperpayRefund"
                    onclick="if (!confirm('{l s='Are you sure?' mod='hyperpay'}'))return false;">
                <i class="icon-undo"></i>
                {l s='Refund by Hyperpay' mod='hyperpay'}
            </button>
    </form>
    {if $message }
        <p class="alert alert-success">{$message|escape:'htmlall':'UTF-8'}</p>
    {/if}
    {foreach from=$errors_refund item=error}
        <p class="alert alert-danger">{$error|escape:'htmlall':'UTF-8'}</p>
    {/foreach}
</div>

<script>
$($('.well.hidden-print')[0]).append($('#hp-refund'))
</script>