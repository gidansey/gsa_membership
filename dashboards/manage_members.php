<?php
    session_start();
    $timeout_duration = 1800; // 30 minutes

    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        header("Location: ../includes/timeout.php");
        exit;
    }
    $_SESSION['LAST_ACTIVITY'] = time();

    require_once '../includes/db_connect.php';

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
        header("Location: ../index.php");
        exit();
    }

    // Get filter parameters
    $filter_status = $_GET['status'] ?? '';
    $filter_member_id = $_GET['member_id'] ?? '';
    $filter_first_name = $_GET['first_name'] ?? '';
    $filter_last_name = $_GET['last_name'] ?? '';

    // Pagination
    $per_page = 50;
    $page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
    $offset = ($page - 1) * $per_page;

    // Build WHERE clause
    $conditions = [];
    if ($filter_status !== '') $conditions[] = "status = '" . $conn->real_escape_string($filter_status) . "'";
    if ($filter_member_id !== '') $conditions[] = "member_id LIKE '%" . $conn->real_escape_string($filter_member_id) . "%'";
    if ($filter_first_name !== '') $conditions[] = "first_name LIKE '%" . $conn->real_escape_string($filter_first_name) . "%'";
    if ($filter_last_name !== '') $conditions[] = "last_name LIKE '%" . $conn->real_escape_string($filter_last_name) . "%'";
    $where = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

    // Get total count
    $count_query = "SELECT COUNT(*) AS total FROM members $where";
    $count_result = $conn->query($count_query);
    $total_members = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_members / $per_page);

    // Fetch paginated data
    $sql = "SELECT * FROM members $where ORDER BY date_registered DESC LIMIT $offset, $per_page";
    $result = $conn->query($sql);
    $members = $result->fetch_all(MYSQLI_ASSOC);

    $isDashboard = true;
    include '../includes/header.php';
?>

<div class="dashboard">
    <aside class="sidebar">
        <div class="logo-container">
            <div class="logo"></div>
        </div>
        <nav>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="manage_members.php" class="active">Manage Members</a>
            <a href="manage_payments.php">Manage Payments</a>
            <a href="manage_events.php">Events</a>
            <a href="manage_users.php">User Accounts</a>
            <a href="generate_reports.php">Generate Reports</a>
            <a href="view_logs.php">Audit Logs</a>
            <a href="settings.php">Settings</a>
            <a href="send_notifications.php">Send Notifications</a>
        </nav>
        <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <?php if (isset($_GET['message'])): ?>
            <div class="alert success">
                <?= htmlspecialchars($_GET['message']) ?>
            </div>
        <?php endif; ?>

        <header>
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>Manage Members</h1>
            <p>Filter and approve/reject members</p>
        </header>

        <form method="GET" style="margin: 15px 0;">
            <input type="hidden" name="page" value="1">
            <label>Filter by Status:</label>
            <select name="status" onchange="this.form.submit()">
                <option value="">-- All --</option>
                <option value="Pending" <?= $filter_status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Approved" <?= $filter_status == 'Approved' ? 'selected' : '' ?>>Approved</option>
                <option value="Rejected" <?= $filter_status == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="Inactive" <?= $filter_status == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                <option value="Deactivate" <?= $filter_status == 'Deactivate' ? 'selected' : '' ?>>Deactivate</option>
                <option value="Reactivate" <?= $filter_status == 'Reactivate' ? 'selected' : '' ?>>Reactivate</option>
            </select>

            <label>Member ID:</label>
            <input type="text" name="member_id" value="<?= htmlspecialchars($filter_member_id) ?>">

            <label>First Name:</label>
            <input type="text" name="first_name" value="<?= htmlspecialchars($filter_first_name) ?>">

            <label>Last Name:</label>
            <input type="text" name="last_name" value="<?= htmlspecialchars($filter_last_name) ?>">

            <button type="submit">Search</button>
        </form>

        <div class="table-card">
            <h3>
                <?= $filter_status ? "$filter_status Members" : "All Members" ?>
                <?= ($filter_member_id || $filter_first_name || $filter_last_name) ? ' (Filtered)' : '' ?>
                <span style="float: right;">Total: <?= $total_members ?></span>
            </h3>

            <table>
                <tr>
                    <th>No.</th>
                    <th>Member ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Date Registered</th>
                    <th>Status</th>
                    <th>View Details</th>
                    <th>Deactivate/Delete</th>
                </tr>
                <?php if ($members): ?>
                    <?php $no = $offset + 1; ?>
                    <?php foreach ($members as $m): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($m['member_id']) ?></td>
                            <td><?= htmlspecialchars($m['first_name'] . ' ' . $m['other_names'] . ' ' . $m['last_name']) ?></td>
                            <td><?= htmlspecialchars($m['email']) ?></td>
                            <td><?= date('M d, Y', strtotime($m['date_registered'])) ?></td>
                            <td><span class="badge <?= strtolower($m['status']) ?>"><?= $m['status'] ?></span></td>
                            <td>
                                <a href="view_member_details.php?id=<?= $m['id'] ?>" class="badge info">View</a>
                                <?php if ($m['status'] === 'Pending'): ?>
                                    <a href="update_member_status.php?id=<?= $m['id'] ?>&action=approve" class="badge approved">Approve</a>
                                    <a href="update_member_status.php?id=<?= $m['id'] ?>&action=reject" class="badge rejected">Reject</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($m['status'] === 'Inactive'): ?>
                                    <a href="reactivate_member.php?id=<?= $m['id'] ?>" onclick="return confirm('Reactivate this member?')" class="badge success">Reactivate</a>
                                <?php else: ?>
                                    <a href="deactivate_member.php?id=<?= $m['id'] ?>" onclick="return confirm('Deactivate this member?')" class="badge inactive">Deactivate</a>
                                <?php endif; ?>
                                <a href="delete_member.php?id=<?= $m['id'] ?>" onclick="return confirm('Permanently delete this member?')" class="badge danger">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">No members found.</td></tr>
                <?php endif; ?>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">First</a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    if ($start_page > 1) echo '<span>...</span>';

                    for ($i = $start_page; $i <= $end_page; $i++):
                        $active = ($i == $page) ? 'class="active"' : '';
                        echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $i])) . "\" $active>$i</a>";
                    endfor;

                    if ($end_page < $total_pages) echo '<span>...</span>';
                    ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>">Last</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
