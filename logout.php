<?php
/* ============================================================
   logout.php — Destroys the current session and redirects
   ============================================================ */

session_start();
session_destroy(); // Clear all session data (log the user out)
header("Location: index.php?success=You+have+been+logged+out");
exit();
?>