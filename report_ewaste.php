<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $category = trim($_POST["category"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $quantity = (int) ($_POST["quantity"] ?? 1);
    $pickupAddress = trim($_POST["pickup_address"] ?? "");
    $preferredDate = $_POST["preferred_date"] ?? "";

    if (empty($category) || empty($description) || $quantity < 1 || empty($pickupAddress) || empty($preferredDate)) {
        $message = "Please fill in all fields to submit your e-waste report.";
    } else {
        // 1. Insert into ewaste_items using exact columns: generator_id, category, description, quantity
        $itemStmt = $conn->prepare(
            "INSERT INTO ewaste_items (generator_id, category, description, quantity) VALUES (?, ?, ?, ?)"
        );
        $itemStmt->bind_param("issi", $userId, $category, $description, $quantity);

        if ($itemStmt->execute()) {
            $newItemId = $conn->insert_id;
            $status = "pending";

            // 2. Create linked collection request
            $reqStmt = $conn->prepare(
                "INSERT INTO collection_requests (item_id, generator_id, request_date, status) VALUES (?, ?, ?, ?)"
            );
            $reqStmt->bind_param("iiss", $newItemId, $userId, $preferredDate, $status);
            if ($reqStmt->execute()) {
                header("Location: dashboard.php");
                exit;
            } else {
                $message = "Item saved, but failed to create collection request: " . $conn->error;
            }
            $reqStmt->close();
        } else {
            $message = "Failed to save e-waste item: " . $conn->error;
        }
        $itemStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report E-Waste: ReTech Solutions</title>
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
        <h2>Report E-Waste & Request Pickup</h2>
        <p><a href="dashboard.php">Back to Dashboard</a> | <a href="logout.php">Log Out</a></p>

        <?php if (!empty($message)): ?>
            <p class="message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form method="POST" action="report_ewaste.php">
            <label for="category">Equipment Category:</label><br>
            <select id="category" name="category" required>
                <option value="">-- Select Category --</option>
                <option value="Mobile Phones & Tablets">Mobile Phones & Tablets</option>
                <option value="Laptops & Desktops">Laptops & Desktops</option>
                <option value="Printers & Accessories">Printers & Accessories</option>
                <option value="Monitors & Displays">Monitors & Displays</option>
            </select><br><br>

            <label for="quantity">Quantity (Units):</label><br>
            <input type="number" id="quantity" name="quantity" min="1" max="50" value="1" required><br><br>

            <label for="description">Item Description / Condition:</label><br>
            <textarea id="description" name="description" rows="3" placeholder="e.g., Dell Latitude Laptop - Working" required></textarea><br><br>

            <label for="pickup_address">Pickup Location / Address:</label><br>
            <input type="text" id="pickup_address" name="pickup_address" placeholder="e.g., Westlands, Nairobi" required><br><br>

            <label for="preferred_date">Preferred Pickup Date:</label><br>
            <input type="date" id="preferred_date" name="preferred_date" required><br><br>

            <button type="submit">Submit E-Waste Report</button>
        </form>
    </section>
  </main>

  <footer>
    <p>ReTech Solutions • E-Waste Management Information System</p>
    <p>Site built by <em>Mariah Keturah & Lesra Baraka</em>, DBT Year 1, 2026</p>
  </footer>

</body>
</html>