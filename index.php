<?php
include 'Game.php';
session_unset();
printHeader();
?>

<section id="input">
   <form method="post" action="Game.php">
      <input type="text" name="gameNum" placeholder="Game ID between 1-30"></input>
      <input type="submit" name="submit" value="Play"></input>
   </form>
</section>

<?
printFooter();
?>