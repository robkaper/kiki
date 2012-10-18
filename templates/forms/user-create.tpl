<form id="createUserForm" action="{$config.kikiPrefix}/account/create.php" method="POST">
  <p>
    <label for="email">{"E-mail"|i18n}</label>
    <input type="text" name="email" value="{$email}">
  </p>
  <p>
    <label for="password">{"Password"|i18n}</label>
    <input type="password" name="password" value="{$password}">
  </p>
  <p>
    <label for="password-repeat">{"Verify password"|i18n}</label>
    <input type="password" name="password-repeat" value="{$password}">
  </p>

  {if !$config.adminUsers}
    <p>
      <label for="password-admin">{"Administrator password"|i18n}<br><span class="small">This website has no administrator accounts. Enter the password from <tt>config.php</tt> to create this account as an administrator account.</span></label>
      <input type="text" name="password-admin" value="{$adminPassword}">
      </p>
  {/if}

  <p>
    <button id="submit" name="submit" type="submit">{"Create account"|i18n}</button>
  </p>
  <br class="spacer">
</form>
