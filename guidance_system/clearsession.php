<?php
session_start();
session_destroy();
echo "Session cleared! <a href='slogin.php'>Go to Login</a>";
?>