<?php
require_once 'db_connect.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "generator";

    if (empty($name) || empty($email) || empty($password)) {
        $message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {
        $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $message = "An account with that email already exists.";
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $accountStatus = "active";

            $insertStmt = $conn->prepare(
                "INSERT INTO users (name, email, password_hash, role, account_status) VALUES (?, ?, ?, ?, ?)"
            );
            $insertStmt->bind_param("sssss", $name, $email, $passwordHash, $role, $accountStatus);

            if ($insertStmt->execute()) {
                $message = "Registration successful! You can now log in.";
            } else {
                $message = "Something went wrong while registering. Please try again.";
            }
            $insertStmt->close();
        }
        $checkStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up: ReTech Solutions</title>
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
        <h2>Create a ReTech Account</h2>

        <?php if (!empty($message)): ?>
            <p class="message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <label for="name">Full Name:</label><br>
            <input type="text" id="name" name="name" required><br><br>

            <label for="email">Email Address:</label><br>
            <input type="email" id="email" name="email" required><br><br>

            <label for="password">Password:</label><br>
            <input type="password" id="password" name="password" required><br><br>

            <label for="role">Account Role:</label><br>
            <select id="role" name="role">
                <option value="generator">E-Waste Generator</option>
                <option value="collector">Collection Personnel</option>
                <option value="admin">Administrator</option>
            </select><br><br>

            <button type="submit">Sign Up</button>
        </form>

        <p>Already have an account? <a href="login.php">Log in here</a></p>
    </section>
  </main>

  <footer>
    <p>ReTech Solutions • E-Waste Management Information System</p>
    <p>Site built by <em>Mariah Keturah & Lesra Baraka</em>, DBT Year 1, 2026</p>
  </footer>

</body>
</html>