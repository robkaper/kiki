{if $config.geoLocation}
  <meta name="ICBM" content="{$config.geoLocation}" />
{/if}
{* // TODO: add config.geoPosition str_replace( ",", ";" ) }
{if $config.geoPosition}
  <meta name="geo.position" content="{$config.geoPosition}" />
{/if}