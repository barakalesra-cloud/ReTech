<?php
session_start();
require_once 'db_connect.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $message = "Please enter both your email and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id, name, password_hash, role, account_status FROM users WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if ($user["account_status"] !== "active") {
                $message = "Your account is not active. Please contact support.";
            } elseif (password_verify($password, $user["password_hash"])) {
                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["name"] = $user["name"];
                $_SESSION["role"] = $user["role"];

                header("Location: dashboard.php");
                exit;
            } else {
                $message = "Invalid email or password.";
            }
        } else {
            $message = "Invalid email or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login: ReTech Solutions</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="https://placehold.co/32x32/1b4d3e/f4f6f4/png?text=R">
</head>
<body>

<header>
    <h1>
      <img src="images/logo.png" alt="ReTech Solutions" class="logo">
    </h1>
    <p>E-Waste Management</p>
  </header>

 <nav>
    <ul>
      <li><a href="index.html">Home</a></li>
      <li><a href="about.html">About Us</a></li>
      <li><a href="services.html">Services</a></li>
      <li><a href="report_ewaste.php">Book Pickup</a></li>
      <li><a href="track-order.php">Track Item</a></li>
      <li><a href="dashboard.php">Management</a></li>
      <li><a href="contact.html">Contact</a></li>
      <li><a href="login.php">Login</a></li>
      <li><a href="register.php">Sign Up</a></li>
    </ul>
  </nav>

  <main>
    <section class="auth-container">
        <h2>Log In to ReTech</h2>

        <?php if (!empty($message)): ?>
            <p class="message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="email">Email Address:</label><br>
            <input type="email" id="email" name="email" required><br><br>

            <label for="password">Password:</label><br>
            <input type="password" id="password" name="password" required><br><br>

            <button type="submit">Log In</button>
        </form>

        <p>Don't have an account? <a href="register.php">Sign up here</a></p>
    </section>
  </main>

  <footer>
    <p>ReTech Solutions • E-Waste Management Information System</p>
    <p>Site built by <em>Mariah Keturah & Lesra Baraka</em>, DBT Year 1, 2026</p>
  </footer>

</body>
</html>