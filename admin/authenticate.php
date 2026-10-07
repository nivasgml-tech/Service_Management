<?php

session_start();

require_once "../config/database.php";


$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';


if ($email == '' || $password == '') {

    header("Location: login.php?error=Please enter email and password.");
    exit;

}


/* FIND ADMIN */

$stmt = $pdo->prepare("
    SELECT *
    FROM users
    WHERE email = ?
    AND role = 'admin'
    AND status = 1
    LIMIT 1
");

$stmt->execute([$email]);

$user = $stmt->fetch();


/* VERIFY LOGIN */

if (
    !$user ||
    !password_verify($password, $user['password'])
) {

    header("Location: login.php?error=Invalid email or password.");
    exit;

}


/* CREATE SESSION */

session_regenerate_id(true);

$_SESSION['admin_id'] = $user['id'];

$_SESSION['admin_name'] = $user['name'];

$_SESSION['admin_email'] = $user['email'];

$_SESSION['admin_role'] = $user['role'];


/* REDIRECT */

header("Location: dashboard.php");

exit;

?>
