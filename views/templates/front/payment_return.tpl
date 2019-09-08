
{extends "$layout"}

{block name="content"}
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
{/block}
