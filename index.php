<?php

header("Location: ". (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") ."://". $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "public/index.php");

exit;
