<?php
session_start();
require_once 'db_connect.php';

$searchId = trim($_GET["request_id"] ?? "");

if ($searchId !== "" && ctype_digit($searchId)) {
    $idInt = (int) $searchId;
    $stmt = $conn->prepare(
        "SELECT cr.request_id, cr.item_id, cr.generator_id, cr.request_date, cr.status,
                ei.category, ei.description, ei.quantity
         FROM collection_requests cr
         LEFT JOIN ewaste_items ei ON cr.item_id = ei.item_id
         WHERE cr.request_id = ?
         ORDER BY cr.request_id DESC"
    );
    $stmt->bind_param("i", $idInt);
} else {
    $stmt = $conn->prepare(
        "SELECT cr.request_id, cr.item_id, cr.generator_id, cr.request_date, cr.status,
                ei.category, ei.description, ei.quantity
         FROM collection_requests cr
         LEFT JOIN ewaste_items ei ON cr.item_id = ei.item_id
         ORDER BY cr.request_id DESC"
    );
}

$stmt->execute();
$ordersResult = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Track Request Status: ReTech Solutions</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="icon" type="image/png" href="https://placehold.co/32x32/2D4048/CCF4E5/png?text=R">
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
    <section>
      <h2>Track E-Waste Progress</h2>
      <p>View live collection requests and submitted e-waste items from the system database.</p>

      <form method="GET" action="track-order.php">
        <label for="request_id">Filter by Request ID:</label>
        <input type="number" id="request_id" name="request_id" min="1"
               value="<?php echo htmlspecialchars($searchId); ?>" placeholder="e.g., 1">
        <button type="submit">Search</button>
        <?php if ($searchId !== ""): ?>
          <a href="track-order.php">Show All</a>
        <?php endif; ?>
      </form>
      <br>

      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>Request ID</th>
              <th>Category</th>
              <th>Item Description</th>
              <th>Qty</th>
              <th>Request Date</th>
              <th>Current Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($ordersResult->num_rows > 0): ?>
              <?php while ($row = $ordersResult->fetch_assoc()): ?>
                <tr>
                  <td><?php echo (int) $row["request_id"]; ?></td>
                  <td><?php echo htmlspecialchars($row["category"] ?? "N/A"); ?></td>
                  <td><?php echo htmlspecialchars($row["description"] ?? "N/A"); ?></td>
                  <td><?php echo (int) ($row["quantity"] ?? 1); ?></td>
                  <td><?php echo htmlspecialchars($row["request_date"]); ?></td>
                  <td><?php echo htmlspecialchars($row["status"]); ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="6">No matching collection requests found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <aside>
    <h2>Status Key</h2>
    <p><strong>pending:</strong> Awaiting collection assignment.<br><strong>scheduled:</strong> Assigned to collection staff.<br><strong>completed:</strong> Collection & recovery recorded.</p>
  </aside>

  <footer>
    <p>ReTech Solutions • E-Waste Management Information System</p>
    <p>Site built by <em>Mariah Keturah & Lesra Baraka</em>, DBT Year 1, 2026</p>
  </footer>

</body>
</html>
