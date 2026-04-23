<?php
session_start();
session_unset();
session_destroy();
header('Location: /grading_systemv2/pages/login.php');
exit;
