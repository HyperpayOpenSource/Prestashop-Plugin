
<div id="hp-capture">
    <form style="display:inline-block" method="post" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}">
        <input type="hidden" name="id_order" value="{$params.id_order|intval}"/>

            <button type="submit" class="btn btn-default" name="submitHyperpayCapture"
                    onclick="if (!confirm('{l s='Are you sure?' mod='hyperpay'}'))return false;">
                <i class="icon-undo"></i>
                {l s='Capture by Hyperpay' mod='hyperpay'}
            </button>
    </form>
    {foreach from=$errors_capture item=error}
        <p class="alert alert-danger">{$error|escape:'htmlall':'UTF-8'}</p>
    {/foreach}
</div>
<script>
$($('.well.hidden-print')[0]).append($('#hp-capture'))
</script>