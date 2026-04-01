<?php
session_start();

$_SESSION['flash_error'] = 'Le debloquage doit etre valide par un administrateur.';
header('Location: login.php');
exit();
