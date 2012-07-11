#!/usr/bin/php
<?

$_SERVER['SERVER_NAME'] = isset($argv[1]) ? $argv[1] : die('SERVER_NAME argument missing'. PHP_EOL);
require_once preg_replace('~/bin/(.*)\.php~', '/lib/init.php', __FILE__ );

$db->query( "truncate objects" );
$db->query( "update articles set object_id=null" );
$db->query( "update users set object_id=null" );

$q = "select id from users"; // where object_id is null";
$rs = $db->query($q);
while ( $o = $db->fetchObject($rs) )
{
  $user = new User($o->id);
  $user->save();
}

$q = "select id from articles"; // where object_id is null";
$rs = $db->query($q);
while ( $o = $db->fetchObject($rs) )
{
  $article = new Article($o->id);
  $article->save();
}

?>