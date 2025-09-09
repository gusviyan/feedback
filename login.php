<?php
session_start();
include 'config.php';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = md5($_POST['password']); // untuk demo, gunakan password_hash di real case

    $result = $conn->query("SELECT * FROM admins WHERE username='$username' AND password='$password'");

    if ($result->num_rows > 0) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header("Location: admin.php");
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background:#111; color:#eee; display:flex; justify-content:center; align-items:center; height:100vh; }
        .login-box { background:#222; padding:30px; border-radius:10px; width:300px; text-align:center; }
        input { width:90%; padding:10px; margin:10px 0; border-radius:5px; border:1px solid #555; }
        button { width:95%; padding:10px; border:none; border-radius:5px; background:#007bff; color:white; cursor:pointer; }
        .error { color:#ff5555; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Login Admin</h2>
        <?php if($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
