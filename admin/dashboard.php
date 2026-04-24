<?php
session_start();
// Include the database connection from the config folder
require_once '../config/db.php';

// SECURITY CHECK: Admins only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// --- STAFF LOGIC (Pagination) ---
$users_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $users_per_page;

// Get total number of staff
$total_staff_stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $total_staff_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_users / $users_per_page);

// Fetch staff for current page
$staff_query = "SELECT id, full_name, email, role FROM users LIMIT " . (int)$users_per_page . " OFFSET " . (int)$offset;
$users = $pdo->query($staff_query)->fetchAll(PDO::FETCH_ASSOC);


// --- PATIENT LOGIC ---
// Get total number of patients
$p_total_stmt = $pdo->query("SELECT COUNT(*) as total FROM patients");
$total_patients = $p_total_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Fetch the 5 most recent patients for the preview table
$recent_patients = $pdo->query("SELECT id, full_name, nic, phone, created_at FROM patients ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="container-main">
    <div class="sidebar">
        <div class="profile-section">
            <div class="avatar">🧑‍💼</div>
            <h3><?php echo htmlspecialchars($_SESSION['username'] ?? 'Pawan'); ?></h3>
            <p><?php echo htmlspecialchars($_SESSION['role'] ?? 'Administrator'); ?></p>
        </div>

        <div class="nav-menu">
            <div class="nav-item active" id="btn-staff" onclick="showSection('staff')">
                <i class="fas fa-users-cog"></i> Staff Management
            </div>
            <div class="nav-item" id="btn-patients" onclick="showSection('patients')">
                <i class="fas fa-hospital-user"></i> Patient Records
            </div>
            <div class="nav-item" id="btn-reports" onclick="showSection('reports')">
                <i class="fas fa-chart-pie"></i> Reports
            </div>
        </div>

        <div class="logout-section">
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="content-header">
            <h1>Admin Dashboard</h1>
            <p>Current Date: <?php echo date('F d, Y'); ?></p>
        </div>

        <div id="staff" class="section active">
            <div class="card">
                <h3>📋 Staff Management</h3>
                <p>Register new Doctors and Nurses. You currently have <strong><?php echo $total_users; ?></strong> staff members.</p>
                <a href="add_staff.php" class="btn">+ Add New Staff</a>
            </div>

            <div class="users-table-container">
                <div class="table-header">
                    <h3>👥 Registered Staff Members</h3>
                    <div class="user-count">Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></div>
                </div>

                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="role-badge <?php echo strtolower($user['role']); ?>">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo ($i == $current_page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="patients" class="section">
            <div class="card">
                <h3>🏥 Patient Management</h3>
                <p>You have <strong><?php echo $total_patients; ?></strong> registered patients in the system.</p>
                <a href="add_patient.php" class="btn">+ Add New Patient</a>
            </div>

            <div class="users-table-container">
                <div class="table-header">
                    <h3>📑 Recent Patient Admissions</h3>
                </div>

                <?php if (count($recent_patients) > 0): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient Name</th>
                            <th>NIC / ID</th>
                            <th>Phone</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_patients as $p): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($p['id']); ?></td>
                            <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($p['nic']); ?></td>
                            <td><?php echo htmlspecialchars($p['phone']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-users">
                    <p>📭 No patients registered yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="reports" class="section">
            <div class="card">
                <h3>📊 Hospital Reports</h3>
                <p>Detailed analytics and occupancy reports are being processed.</p>
                <button class="btn btn-disabled">Coming Soon</button>
            </div>
        </div>
    </div>
</div>

<script>
    function showSection(sectionId) {
        // 1. Hide all sections
        const sections = document.querySelectorAll('.section');
        sections.forEach(section => section.classList.remove('active'));

        // 2. Show the selected section
        document.getElementById(sectionId).classList.add('active');

        // 3. Update Sidebar Active State
        const navItems = document.querySelectorAll('.nav-item');
        navItems.forEach(item => item.classList.remove('active'));
        
        // Find which button was clicked based on sectionId
        document.getElementById('btn-' + sectionId).classList.add('active');
    }
</script>

</body>
</html>