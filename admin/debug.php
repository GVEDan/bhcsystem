<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

echo "<h1>Auth Debug</h1>";
echo "<p>Session Status: " . (isset($_SESSION['user_id']) ? "Active" : "Not Active") . "</p>";
echo "<p>Is Logged In: " . (Auth::isLoggedIn() ? "Yes" : "No") . "</p>";

if (Auth::isLoggedIn()) {
    $user = Auth::getCurrentUser();
    echo "<p>User Role: " . Auth::getRole() . "</p>";
    echo "<p>Admin Role Constant: " . ADMIN_ROLE . "</p>";
    echo "<p>Is Admin: " . (Auth::getRole() === ADMIN_ROLE ? "Yes" : "No") . "</p>";
}

// Try to access the current page
echo "<p>Current Page: " . $_SERVER['PHP_SELF'] . "</p>";
echo "<p>Query String: " . $_SERVER['QUERY_STRING'] . "</p>";
?>
