#!/usr/bin/php
<?

$_SERVER['SERVER_NAME'] = "dev.robkaper.nl";
include_once "/home/rob/git/kiki/lib/init.php";

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