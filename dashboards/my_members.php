<?php
    session_start();
    require_once '../includes/db_connect.php';

    // Session timeout
    $timeout_duration = 1800;
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        header("Location: ../includes/timeout.php");
        exit;
    }
    $_SESSION['LAST_ACTIVITY'] = time();

    // Access control
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Branch Leader') {
        header("Location: ../index.php");
        exit;
    }

    $branch_id = $_SESSION['branch_id'] ?? null;
    if (!$branch_id) {
        die("Branch not assigned.");
    }

    // Pagination
    $search = $_GET['search'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Sorting
    $sort_by = in_array($_GET['sort'] ?? '', ['first_name', 'email', 'status', 'date_registered']) ? $_GET['sort'] : 'date_registered';
    $order = ($_GET['order'] ?? '') === 'asc' ? 'ASC' : 'DESC';

    // Build WHERE clause
    $where = "a.branch_id = ?";
    $params = [$branch_id];
    $types = 'i';

    if (!empty($search)) {
        $where .= " AND (m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ?)";
        $searchTerm = "%$search%";
        array_push($params, $searchTerm, $searchTerm, $searchTerm);
        $types .= 'sss';
    }

    // Total count
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM members m JOIN affiliations a ON m.id = a.member_id WHERE $where");
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_stmt->bind_result($total);
    $count_stmt->fetch();
    $count_stmt->close();

    $total_pages = ceil($total / $limit);

    // Fetch members
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    // Validate and whitelist sort column
    $allowed_sort_columns = ['first_name', 'last_name', 'email', 'status', 'date_registered'];
    $sort_by = in_array($sort_by, $allowed_sort_columns) ? $sort_by : 'date_registered';

    $query = "
        SELECT m.id, m.member_id, m.first_name, m.last_name, m.email, m.status, m.notes, m.date_registered
        FROM members m
        JOIN affiliations a ON m.id = a.member_id
        WHERE $where
        ORDER BY m.{$sort_by} {$order}
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $members = $result->fetch_all(MYSQLI_ASSOC);
    $isDashboard = true;

    include '../includes/header.php';
?>

<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="branch_dashboard.php">Dashboard</a>
            <a href="my_members.php" class="active">My Members</a>
            <a href="branch_events.php">Branch Events</a>
            <a href="branch_announcement.php">Announcements</a>
            <a href="branch_report.php">Reports</a>
        </nav>
        <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>My Branch Members</h1>
        </header>

        <!-- Sorting -->
        <div style="margin-bottom: 10px;">
            Sort by:
            <?php
            $sort_options = [
                'first_name' => 'Name',
                'email' => 'Email',
                'status' => 'Status',
                'date_registered' => 'Registration Date'
            ];
            foreach ($sort_options as $key => $label): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['sort' => $key, 'order' => ($sort_by == $key && $order == 'ASC') ? 'desc' : 'asc'])) ?>">
                    <?= $label ?> ▼
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Export Links -->
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search name or email..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
            <div class="export-options">
                <a href="export_branch_members_csv.php?search=<?= urlencode($search) ?>" class="badge approved">Export CSV</a>
                <a href="export_branch_members_pdf.php?search=<?= urlencode($search) ?>" class="badge info">Export PDF</a>
            </div>
        </form>
        
        <!-- Bulk Email Form -->
        <form method="POST" action="send_bulk_email.php">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>Member ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($members)): ?>
                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td><input type="checkbox" name="member_ids[]" value="<?= $m['id'] ?>"></td>
                                <td><?= htmlspecialchars($m['member_id']) ?></td>
                                <td><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></td>
                                <td><?= htmlspecialchars($m['email']) ?></td>
                                <td><span class="badge <?= strtolower($m['status']) ?>"><?= $m['status'] ?></span></td>
                                <td><?= nl2br(htmlspecialchars($m['notes'])) ?></td>
                                <td><a href="view_member.php?id=<?= $m['id'] ?>" class="badge partial">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No members found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <button type="submit" class="badge success" style="margin-top: 10px;">📧 Send Email to Selected</button>
        </form>

        <!-- Pagination -->
        <div class="pagination">
            <div class="pagination-info">
                Showing <?= $offset + 1 ?>–<?= min($offset + $limit, $total) ?> of <?= $total ?> members.
            </div>
            <div class="pagination-links">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&sort=<?= $sort_by ?>&order=<?= $order ?>" class="badge">Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sort_by ?>&order=<?= $order ?>" class="badge <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&sort=<?= $sort_by ?>&order=<?= $order ?>" class="badge">Next</a>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Select all checkbox functionality
        const selectAll = document.getElementById('select-all');
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('input[name="member_ids[]"]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });
        }

        // Optional: If any checkbox is unchecked, uncheck the "Select All" checkbox
        const memberCheckboxes = document.querySelectorAll('input[name="member_ids[]"]');
        memberCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (!this.checked) {
                    selectAll.checked = false;
                } else {
                    // Check if all checkboxes are now checked
                    const allChecked = Array.from(memberCheckboxes).every(cb => cb.checked);
                    selectAll.checked = allChecked;
                }
            });
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
