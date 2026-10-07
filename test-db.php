<?php

require_once "config/database.php";

try {

    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM services");

    $result = $stmt->fetch();

    echo "<h2>Database Connected Successfully!</h2>";

    echo "<p>Services Table Found.</p>";

    echo "<p>Total Services: " . $result['total'] . "</p>";

} catch (PDOException $e) {

    echo "<h2>Database Error</h2>";

    echo "<p>" . $e->getMessage() . "</p>";

}

?>