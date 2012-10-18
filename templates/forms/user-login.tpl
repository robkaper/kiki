<form id="loginForm" action="{$config.kikiPrefix}/login/" method="POST">
  <p>
    <label for="email">{"E-mail"|i18n}</label>
    <input type="text" name="email" value="{$email}">
  </p>
  <p>
    <label for="password">{"Password"|i18n}</label>
    <input type="password" name="password" value="{$password}">
  </p>
  <p>
    <button name="submit" id="submit" type="submit">{"Login"|i18n}</button>
  </p>
  <br class="spacer">
</form>
