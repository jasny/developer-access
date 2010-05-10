<?php
session_start();
if (isset($_GET['cmd']) && $_GET['cmd'] == 'logout') unset($_SESSION['user']);

echo !empty($_SESSION['user']) ? "<h1>{$_SESSION['user']}</h1><a href='index.php?cmd=logout'>Logout</a>" : "<h1>Guest</h1>";
