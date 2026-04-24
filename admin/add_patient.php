<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

$message = "";

if (isset($_POST['add_patient'])) {
    try {
        $pdo->beginTransaction();

        // Check if patient is adult or minor based on NIC field presence
        $nic = !empty($_POST['nic']) ? $_POST['nic'] : 'CHILD-' . time(); // Fallback for DB uniqueness if minor
        
        $sql = "INSERT INTO patients (full_name, dob, guardian_name, guardian_nic, guardian_relation, gender, nic, phone, address, blood_group, allergies, emergency_contact_name, emergency_phone) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['full_name'], $_POST['dob'], 
            $_POST['guardian_name'] ?? null, $_POST['guardian_nic'] ?? null, $_POST['guardian_relation'] ?? null,
            $_POST['gender'], $nic, $_POST['phone'], $_POST['address'] ?? null, 
            $_POST['blood_group'], $_POST['allergies'], $_POST['emergency_name'], $_POST['emergency_phone']
        ]);
        
        $pdo->commit();
        $message = "Patient record created successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Add Patient</title>
    <link rel="stylesheet" href="../assets/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; color: #b0b0b0; font-size: 11px; margin-bottom: 8px; text-transform: uppercase; font-weight: 600; letter-spacing: 1px; }
        
        .input-group input, .input-group select, .input-group textarea {
            width: 100%; padding: 12px; border-radius: 10px; background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.2); color: white; font-size: 14px; transition: 0.3s;
        }

        /* Blur/Disabled Style */
        input:disabled {
            background: rgba(255, 255, 255, 0.02);
            border-color: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.2);
            cursor: not-allowed;
            filter: blur(1px);
        }

        .form-section { max-width: 850px; background: rgba(30, 30, 40, 0.85); padding: 40px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.1); margin: auto; box-shadow: 0 15px 35px rgba(0,0,0,0.5); }
        h2, h4 { color: #00ff96; margin-bottom: 20px; font-weight: 600; }
        
        #guardian_section { 
            display: none; 
            background: rgba(0, 255, 150, 0.03); 
            border: 1px solid rgba(0, 255, 150, 0.1); 
            padding: 25px; 
            border-radius: 15px; 
            margin: 20px 0;
            animation: slideDown 0.4s ease-out;
        }
        
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
<div class="container-main">
    <div class="sidebar">
        <div class="profile-section">
            <div class="avatar">🧑‍💼</div>
            <h3>Pawan</h3>
            <p>Administrator</p>
        </div>
        <div class="nav-menu">
            <div class="nav-item" onclick="window.location.href='dashboard.php'"><i class="fas fa-users-cog"></i> Staff Management</div>
            <div class="nav-item active"><i class="fas fa-hospital-user"></i> Patient Records</div>
        </div>
        <div class="logout-section"><a href="../logout.php" class="logout-btn">Logout</a></div>
    </div>

    <div class="main-content">
        <?php if ($message): ?>
            <div style="padding:15px; background:rgba(0,255,150,0.1); color:#00ff96; border-radius:10px; margin-bottom:20px; border: 1px solid #00ff96;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-section">
            <h2><i class="fas fa-user-plus"></i> Add New Patient</h2>
            <form method="POST" id="patientForm">
                <div class="form-grid">
                    <div class="input-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" placeholder="Enter patient name" required>
                    </div>
                    <div class="input-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" id="dob_input" required onchange="checkAge()">
                    </div>
                    <div class="input-group">
                        <label id="nic_label">NIC / ID Number</label>
                        <input type="text" name="nic" id="nic_input" placeholder="Patient NIC" required>
                    </div>
                    <div class="input-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div id="guardian_section">
                    <h4 style="color: #00ff96;"><i class="fas fa-user-shield"></i> Guardian Details (Required for Minor)</h4>
                    <div class="form-grid">
                        <div class="input-group">
                            <label>Guardian Name</label>
                            <input type="text" name="guardian_name" id="g_name">
                        </div>
                        <div class="input-group">
                            <label>Guardian NIC (Unique ID)</label>
                            <input type="text" name="guardian_nic" id="g_nic">
                        </div>
                        <div class="input-group full-width">
                            <label>Relationship to Child</label>
                            <input type="text" name="guardian_relation" placeholder="e.g. Mother, Father, Aunt">
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="input-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" required>
                    </div>
                    <div class="input-group">
                        <label>Blood Group</label>
                        <select name="blood_group">
                            <option value="Unknown">Unknown</option>
                            <option value="A+">A+</option><option value="A-">A-</option>
                            <option value="B+">B+</option><option value="B-">B-</option>
                            <option value="O+">O+</option><option value="O-">O-</option>
                            <option value="AB+">AB+</option><option value="AB-">AB-</option>
                        </select>
                    </div>
                    <div class="input-group full-width">
                        <label>Medical Allergies</label>
                        <textarea name="allergies" placeholder="List any known allergies..."></textarea>
                    </div>
                    
                    <div class="full-width"><h4><i class="fas fa-phone-alt"></i> Emergency Contact</h4></div>
                    <div class="input-group">
                        <label>Contact Name</label>
                        <input type="text" name="emergency_name">
                    </div>
                    <div class="input-group">
                        <label>Emergency Phone</label>
                        <input type="text" name="emergency_phone">
                    </div>
                </div>

                <button type="submit" name="add_patient" class="btn" style="margin-top: 20px; width: 100%;">✓ REGISTER PATIENT</button>
            </form>
            <a href="dashboard.php" style="color:#00ff96; text-decoration:none; display:block; margin-top:20px; text-align: center;">← Back to Dashboard</a>
        </div>
    </div>
</div>

<script>
function checkAge() {
    const dobValue = document.getElementById('dob_input').value;
    if (!dobValue) return;

    const dob = new Date(dobValue);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const monthDiff = today.getMonth() - dob.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
        age--;
    }

    const guardianSection = document.getElementById('guardian_section');
    const nicInput = document.getElementById('nic_input');
    const gName = document.getElementById('g_name');
    const gNic = document.getElementById('g_nic');

    if (age < 18) {
        // Minor logic
        guardianSection.style.display = 'block';
        nicInput.disabled = true;
        nicInput.placeholder = "Not required for minors";
        nicInput.value = "";
        nicInput.required = false;

        // Guardian info becomes required
        gName.required = true;
        gNic.required = true;
    } else {
        // Adult logic
        guardianSection.style.display = 'none';
        nicInput.disabled = false;
        nicInput.placeholder = "Enter Patient NIC";
        nicInput.required = true;

        // Guardian info no longer required
        gName.required = false;
        gNic.required = false;
    }
}
</script>

</body>
</html>