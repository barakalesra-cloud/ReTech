<?php
session_start();
require_once 'db_connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$userName = $_SESSION["name"] ?? "User";
$userRole = strtolower(trim($_SESSION["role"] ?? "generator"));

$statusMessage = "";
$statusError = "";

// Handle status updates for admin or collector
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_status"])) {
    if ($userRole === "admin" || $userRole === "collector") {
        $requestId = (int) ($_POST["request_id"] ?? 0);
        $newStatus = trim($_POST["status"] ?? "");
        $allowedStatuses = ["pending", "scheduled", "completed"];

        if ($requestId > 0 && in_array($newStatus, $allowedStatuses, true)) {
            $updStmt = $conn->prepare("UPDATE collection_requests SET status = ? WHERE request_id = ?");
            $updStmt->bind_param("si", $newStatus, $requestId);
            if ($updStmt->execute()) {
                $statusMessage = "Request #{$requestId} updated to '{$newStatus}'.";
            } else {
                $statusError = "Could not update request status.";
            }
            $updStmt->close();
        } else {
            $statusError = "Invalid request ID or status selected.";
        }
    } else {
        $statusError = "Only collectors and admins can update request statuses.";
    }
}

// Count e-waste items linked to this user (if generator) or all items (if admin/collector)
if ($userRole === "generator") {
    $itemStmt = $conn->prepare("SELECT COUNT(*) AS total FROM ewaste_items WHERE generator_id = ?");
    $itemStmt->bind_param("i", $userId);
} else {
    $itemStmt = $conn->prepare("SELECT COUNT(*) AS total FROM ewaste_items");
}
$itemStmt->execute();
$itemCount = (int) ($itemStmt->get_result()->fetch_assoc()["total"] ?? 0);
$itemStmt->close();

// Sum reward points for this user (or total points across system for admin)
if ($userRole === "admin") {
    $rewardStmt = $conn->prepare("SELECT COALESCE(SUM(points), 0) AS total_points FROM rewards");
} else {
    $rewardStmt = $conn->prepare("SELECT COALESCE(SUM(points), 0) AS total_points FROM rewards WHERE user_id = ?");
    $rewardStmt->bind_param("i", $userId);
}
$rewardStmt->execute();
$rewardPoints = (int) ($rewardStmt->get_result()->fetch_assoc()["total_points"] ?? 0);
$rewardStmt->close();

// Count pending requests for operational summary
if ($userRole === "generator") {
    $pendStmt = $conn->prepare("SELECT COUNT(*) AS total FROM collection_requests WHERE generator_id = ? AND LOWER(status) = 'pending'");
    $pendStmt->bind_param("i", $userId);
} else {
    $pendStmt = $conn->prepare("SELECT COUNT(*) AS total FROM collection_requests WHERE LOWER(status) = 'pending'");
}
$pendStmt->execute();
$pendingCount = (int) ($pendStmt->get_result()->fetch_assoc()["total"] ?? 0);
$pendStmt->close();

// Fetch collection requests joined with item details
if ($userRole === "generator") {
    $reqStmt = $conn->prepare(
        "SELECT cr.request_id, cr.request_date, cr.status, ei.category, ei.description, ei.quantity
         FROM collection_requests cr
         LEFT JOIN ewaste_items ei ON cr.item_id = ei.item_id
         WHERE cr.generator_id = ?
         ORDER BY cr.request_date DESC, cr.request_id DESC LIMIT 10"
    );
    $reqStmt->bind_param("i", $userId);
} else {
    $reqStmt = $conn->prepare(
        "SELECT cr.request_id, cr.request_date, cr.status, cr.generator_id, ei.category, ei.description, ei.quantity
         FROM collection_requests cr
         LEFT JOIN ewaste_items ei ON cr.item_id = ei.item_id
         ORDER BY cr.request_date DESC, cr.request_id DESC LIMIT 15"
    );
}
$reqStmt->execute();
$requestsResult = $reqStmt->get_result();
$reqStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ReTech Solutions</title>
    <link rel="stylesheet" href="css/style.css?v=2">
</head>
<body>
    <header>
        <h1>
            <img src="images/logo.png" alt="ReTech Solutions" class="logo">
        </h1>
        <p>E-Waste Management &bull; Welcome, <strong><?php echo htmlspecialchars($userName); ?></strong> (Role: <strong><?php echo htmlspecialchars($userRole); ?></strong>)</p>
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
            <li><a href="logout.php">Log Out</a></li>
        </ul>
    </nav>

    <main>
        <?php if ($statusMessage !== ""): ?>
            <p><strong><?php echo htmlspecialchars($statusMessage); ?></strong></p>
        <?php endif; ?>
        <?php if ($statusError !== ""): ?>
            <p><strong><?php echo htmlspecialchars($statusError); ?></strong></p>
        <?php endif; ?>

        <?php if ($userRole === "admin"): ?>
            <h2>Administrator Overview</h2>
            <p>Monitor all reported e-waste items, review collection requests, and update pickup statuses across the system.</p>
        <?php elseif ($userRole === "collector"): ?>
            <h2>Collector Operations Hub</h2>
            <p>Review incoming e-waste collection requests and update pickup progress as items are scheduled or collected.</p>
        <?php else: ?>
            <h2>Generator Dashboard</h2>
            <p>Submit new e-waste reports, earn reward points, and track your scheduled pickups.</p>
        <?php endif; ?>

        <section class="card-grid" style="margin-bottom: 1.5rem;">
            <div class="image-card" style="padding: 1rem;">
                <h3><?php echo $userRole === "generator" ? "My Reported Items" : "Total Reported Items"; ?></h3>
                <p style="font-size: 1.5rem; font-weight: 700; padding: 0;"><?php echo $itemCount; ?></p>
            </div>

            <div class="image-card" style="padding: 1rem;">
                <h3>Pending Pickups</h3>
                <p style="font-size: 1.5rem; font-weight: 700; padding: 0;"><?php echo $pendingCount; ?></p>
            </div>

            <div class="image-card" style="padding: 1rem;">
                <h3><?php echo $userRole === "admin" ? "Total System Points" : "Reward Points"; ?></h3>
                <p style="font-size: 1.5rem; font-weight: 700; padding: 0;"><?php echo $rewardPoints; ?></p>
            </div>
        </section>

        <section>
            <h2>
                <?php
                if ($userRole === "admin") {
                    echo "All Collection Requests";
                } elseif ($userRole === "collector") {
                    echo "Assigned &amp; Incoming Pickups";
                } else {
                    echo "Recent Collection Requests";
                }
                ?>
            </h2>

            <?php if ($requestsResult->num_rows > 0): ?>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <?php if ($userRole !== "generator"): ?>
                                    <th>Generator ID</th>
                                <?php endif; ?>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Date</th>
                                <th>Status</th>
                                <?php if ($userRole === "admin" || $userRole === "collector"): ?>
                                    <th>Update Status</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $requestsResult->fetch_assoc()): ?>
                                <?php $currentStatus = strtolower(trim($row["status"] ?? "pending")); ?>
                                <tr>
                                    <td><?php echo (int) $row["request_id"]; ?></td>
                                    <?php if ($userRole !== "generator"): ?>
                                        <td><?php echo (int) ($row["generator_id"] ?? 0); ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <?php
                                        $desc = trim($row["description"] ?? "");
                                        $cat = trim($row["category"] ?? "");
                                        echo htmlspecialchars($desc !== "" ? $desc : ($cat !== "" ? $cat : "E-Waste Item"));
                                        ?>
                                    </td>
                                    <td><?php echo (int) ($row["quantity"] ?? 1); ?></td>
                                    <td><?php echo htmlspecialchars($row["request_date"]); ?></td>
                                    <td><?php echo htmlspecialchars($row["status"]); ?></td>
                                    <?php if ($userRole === "admin" || $userRole === "collector"): ?>
                                        <td>
                                            <form method="POST" action="dashboard.php" style="display: flex; gap: 0.4rem; align-items: center;">
                                                <input type="hidden" name="request_id" value="<?php echo (int) $row["request_id"]; ?>">
                                                <select name="status">
                                                    <option value="pending" <?php echo $currentStatus === "pending" ? "selected" : ""; ?>>pending</option>
                                                    <option value="scheduled" <?php echo $currentStatus === "scheduled" ? "selected" : ""; ?>>scheduled</option>
                                                    <option value="completed" <?php echo $currentStatus === "completed" ? "selected" : ""; ?>>completed</option>
                                                </select>
                                                <button type="submit" name="update_status" value="1">Save</button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No collection requests found yet. Use <a href="report_ewaste.php">Book Pickup</a> to submit an e-waste report.</p>
            <?php endif; ?>
        </section>
    </main>

    <aside>
        <h3>Quick Actions</h3>
        <?php if ($userRole === "generator"): ?>
            <p>Have devices ready for disposal or recycling?</p>
            <p><a href="report_ewaste.php"><button type="button">Report New E-Waste</button></a></p>
            <p><a href="track-order.php">Track My Requests</a></p>
        <?php elseif ($userRole === "collector"): ?>
            <p>Select a status in the table to mark pickups as <strong>scheduled</strong> or <strong>completed</strong>.</p>
            <p><a href="track-order.php">Open Live Tracker</a></p>
        <?php else: ?>
            <p>You have full oversight of submitted requests and live tracking records.</p>
            <p><a href="report_ewaste.php">Submit E-Waste Entry</a></p>
            <p><a href="track-order.php">View Live Tracker</a></p>
        <?php endif; ?>
    </aside>

    <footer>
        <p>ReTech Solutions &bull; E-Waste Management Information System</p>
    </footer>
</body>
</html>