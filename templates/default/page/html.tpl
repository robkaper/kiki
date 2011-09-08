<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?= Config::$language; ?>">
<?
  include Template::file('page/head');
  include Template::file('page/body');
?>
</html>
<? Log::debug( "exit: ". $_SERVER['REQUEST_URI'] ); ?>
