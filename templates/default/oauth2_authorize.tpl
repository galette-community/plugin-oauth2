{extends file="html.tpl"}

{block name="stylesheet"}
    <link rel="stylesheet" href="{$path_css}oauth2_login.css"/>
{/block}


{block name="content"}
<h1>Grant the following access rights to <b>{$clientName}</b>?</h1>

<ul>
    {foreach from=scopes item=scope}
        <li>{$scope}</li>
    {/foreach}
</ul>

<form method="post">
    <button type="submit" name="authorized" value="true">Yes</button><br/>
    <button type="submit" name="authorized" value="false">No</button><br/>
    {include file="forms_types/csrf.tpl"}
</form>
{/block}
