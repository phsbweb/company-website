<?php
session_set_cookie_params(['path' => '/', 'samesite' => 'Lax']);
session_start();

if (!isset($_SESSION['counter'])) {
    $_SESSION['counter'] = 1;
} else {
    $_SESSION['counter']++;
}

echo "<h1>Session Diagnostic Tool</h1>";
echo "<p><b>Session ID:</b> " . session_id() . "</p>";
echo "<p><b>Refresh Counter:</b> " . $_SESSION['counter'] . "</p>";
echo "<p>If the counter goes up when you refresh, sessions are <b>WORKING</b>.</p>";
echo "<p>If the counter stays at 1, the browser is <b>DROPPING</b> your session cookie.</p>";

echo "<h2>Debug Info</h2>";
echo "<pre>";
print_r(session_get_cookie_params());
echo "</pre>";

echo "<a href='test_session.php'>Click here to Refresh</a>";
?>
