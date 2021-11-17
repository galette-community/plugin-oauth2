{extends file="html.tpl"}

{block name="stylesheet"}
    <link rel="stylesheet" href="{$path_css}oauth2_login.css"/>
{/block}

{block name="content"}
<div class="container login">

  <div class="row">
    <div class="card_user">
        <div class="row header">
            <div class="col-8 title">
              <h1>{_T string="Please sign in for" domain="oauth2"} '{$application|default}' </h1>
            </div>
            <div class="col-4 logo">
                <img
                  src="{path_for name="logo"}" alt="[ logo ]"
                  style="max-width:{$logo->getOptimalWidth()}px; max-height:{$logo->getOptimalHeight()}px"
                  />
            </div>
        </div>

      {if isset($errorMessage) neq null}
      <div class="alert alert-danger" role="alert">
        {$errorMessage}
      </div>
      {/if}

      <form class="form-signin" action="{path_for name="oauth2_login"}" method="post">
        <div class="form-group">
          <div class="input-group-append">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <input type="text" class="form-control input_user" id="username" name="username" aria-describedby="usernameHelp" value="{$username|default}" placeholder="{_T string="Username or email" domain="oauth2"}">
        </div>

        </div>
        <div class="form-group">
            <div class="input-group-append">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
                <input type="password" class="form-control input_password" id="password" name="password" placeholder="{_T string="Password" domain="oauth2"}">
            </div>
        </div>

        <div class="form-group text-center">
          <input type="submit" class="btn login_btn" value="{_T string="Login" domain="oauth2"}" />
          <input type="hidden" name="ident" value="1" />
        </div>
        {include file="forms_types/csrf.tpl"}


      </form>

      <div class="pull-right">
          {if $pref_mail_method neq constant('Galette\Core\GaletteMail::METHOD_DISABLED')}
              <a
                  id="lostpassword"
                  href="{path_for name="password-lost"}"
                  class="button{if $cur_route eq "password-lost"} selected{/if}"
              >
                  <i class="fas fa-unlock-alt" aria-hidden="true"></i>
                  {_T string="Lost your password?"}
              </a>
          {/if}
      </div>
    </div>
  </div>
</div>

{/block}
