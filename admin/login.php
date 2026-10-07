<?php

session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = $_GET['error'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Login | YourDream</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #071a2d;
            font-family: Arial, sans-serif;
        }

        .login-box {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            padding: 45px;
            border-radius: 14px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        }

        .logo {
            text-align: center;
            font-size: 30px;
            font-weight: 700;
            color: #10253d;
            margin-bottom: 10px;
        }

        .logo span {
            color: #b18b45;
        }

        .login-title {
            text-align: center;
            color: #263746;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 700;
            color: #263746;
        }

        input {
            width: 100%;
            padding: 14px;
            border: 1px solid #d9e0e6;
            border-radius: 7px;
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: #b18b45;
        }

        button {
            width: 100%;
            padding: 14px;
            border: 0;
            border-radius: 7px;
            background: #10253d;
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }

        button:hover {
            background: #173653;
        }

        .error {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 6px;
            background: #fdeaea;
            color: #a33a3a;
            font-size: 14px;
        }

    </style>

</head>

<body>

<div class="login-box">

    <div class="logo">
        Your<span>Dream</span>
    </div>

    <h2 class="login-title">
        Admin Login
    </h2>

    <?php if ($error != ''): ?>

        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <form
        method="POST"
        action="authenticate.php"
    >

        <div class="form-group">

            <label for="email">
                Email Address
            </label>

            <input
                type="email"
                name="email"
                id="email"
                placeholder="Enter admin email"
                required
            >

        </div>


        <div class="form-group">

            <label for="password">
                Password
            </label>

            <input
                type="password"
                name="password"
                id="password"
                placeholder="Enter password"
                required
            >

        </div>


        <button type="submit">
            Login
        </button>

    </form>

</div>

</body>

</html>
