{foreach from=$paymentMethods item=paymentMethod}
  <div class="row">
      <div class="col-xs-12">
          <p class="payment_module" id="hyperpay_payment_module">
              <a style="background: url({$paymentMethod['logo']|escape:'htmlall':'UTF-8'}) 15px center no-repeat #fbfbfb;background-size: 50px;" href="{$paymentMethod['link']|escape:'htmlall':'UTF-8'}"  >
                    {$paymentMethod['title']}
              </a>
          </p>
      </div>
  </div>
{/foreach}
