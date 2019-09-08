presta 1.6 default bootstrap 

<li><a href="{$link->getModuleLink('hyperpay', 'savedCards')|escape:'html':'UTF-8'}" title="{l s='Saved Credit Cards' mod="hyperpay"}"><i class="icon-credit-card"></i><span>{l s='My Saved Credit Cards' mod="hyperpay"}</span></a></li>

presta 1.7 classic

<a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" id="returns-link" href="{$link->getModuleLink('hyperpay', 'savedCards')|escape:'html':'UTF-8'}" title="{l s='Saved Credit Cards' mod="hyperpay"}">
    <span class="link-item">
    <i class="material-icons">&#xE860;</i>
    {l s='My Saved Credit Cards' mod="hyperpay"}
    </span>
</a>