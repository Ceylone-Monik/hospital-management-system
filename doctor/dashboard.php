<?php
session_start();
require '../config/db.php';

// 1. SECURITY CHECK: Ensure only Doctors can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Doctor') {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];

// 2. FETCH DOCTOR DETAILS
$stmt = $pdo->prepare("SELECT users.full_name, doctor_details.specialization 
                       FROM users 
                       JOIN doctor_details ON users.id = doctor_details.user_id 
                       WHERE users.id = ?");
$stmt->execute([$userId]);
$doctor = $stmt->fetch();

// 3. HANDLE MEDICAL REPORT SUBMISSION
if (isset($_POST['submit_report'])) {
    try {
        $stmt_rep = $pdo->prepare("INSERT INTO medical_reports (patient_id, doctor_id, symptoms, diagnosis, vitals, prescription, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_rep->execute([
            $_POST['p_id'], 
            $userId, 
            $_POST['symptoms'], 
            $_POST['diagnosis'], 
            $_POST['vitals'], 
            $_POST['prescription'], 
            $_POST['remarks']
        ]);
        header("Location: dashboard.php?msg=Success");
        exit();
    } catch (Exception $e) {
        $error = "Error saving report: " . $e->getMessage();
    }
}

// 4. FETCH ALL PATIENTS
$all_patients = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard | Hospital System</title>
    <link rel="stylesheet" href="../assets/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Section Toggle Logic */
        .section { display: none; }
        .section.active { display: block; }

        /* Modal / Pop-up Styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(10px); z-index: 1000; justify-content: center; align-items: center; }
        .modal-card { background: rgba(30, 30, 45, 1); border: 1px solid rgba(0, 255, 150, 0.3); width: 95%; max-width: 750px; padding: 40px; border-radius: 24px; color: white; position: relative; max-height: 90vh; overflow-y: auto; box-shadow: 0 0 50px rgba(0,0,0,0.6); }
        
        /* Profile Grid Layout */
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .detail-item { border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        .detail-item label { color: #00ff96; font-size: 11px; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 5px; }
        .detail-item span { font-size: 15px; }

        /* Guardian Box (Only for children) */
        .guardian-box { grid-column: span 2; background: rgba(255, 165, 0, 0.1); border: 1px solid rgba(255, 165, 0, 0.3); padding: 20px; border-radius: 15px; margin-top: 15px; }
        
        /* Report Form Area */
        #report_form { display: none; margin-top: 20px; border-top: 1px dashed rgba(0,255,150,0.5); padding-top: 20px; }
        .report-input { width: 100%; padding: 12px; margin-bottom: 15px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 8px; font-family: inherit; }
        textarea.report-input { height: 100px; resize: none; }

        /* Table Action Buttons */
        .action-btns { display: flex; gap: 10px; }
        .btn-report { background: #ffa502 !important; box-shadow: 0 4px 15px rgba(255, 165, 0, 0.2); border: none; }
    </style>
</head>
<body>

<div class="container-main">
    <div class="sidebar">
        <div class="profile-section">
            <div class="avatar">👨‍⚕️</div>
            <h3>Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></h3>
            <p><?php echo htmlspecialchars($doctor['specialization']); ?></p>
        </div>
        <div class="nav-menu">
            <div class="nav-item active" id="nav-home" onclick="showSection('home')">🏥 Dashboard</div>
            <div class="nav-item" id="nav-patients" onclick="showSection('patients')">👥 Patients</div>
        </div>
        <div class="logout-section">
            <a href="../logout.php" class="logout-btn">🛑 Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="content-header">
            <h1>Doctor Portal</h1>
            <p><?php echo date('F d, Y'); ?></p>
        </div>

        <div id="home" class="section active">
            <div class="card">
                <h3>Welcome back, Doctor.</h3>
                <p>Select the **Patients** tab to access the central medical database.</p>
            </div>
        </div>

        <div id="patients" class="section">
            <div class="users-table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>NIC / ID</th>
                            <th>Phone</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_patients as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($p['nic']); ?></td>
                            <td><?php echo htmlspecialchars($p['phone']); ?></td>
                            <td class="action-btns">
                                <button class="btn" onclick='openModal("profile", <?php echo json_encode($p); ?>)'>
                                    <i class="fas fa-eye"></i> VIEW
                                </button>
                                <button class="btn btn-report" onclick='openModal("report", <?php echo json_encode($p); ?>)'>
                                    <i class="fas fa-plus-circle"></i> ADD REPORT
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="universalModal" class="modal-overlay">
    <div class="modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 id="modal_title" style="color: #00ff96; margin:0;"></h2>
            <div style="display: flex; gap: 10px;">
                <button class="btn" id="toggleReportBtn" onclick="toggleReportForm()" style="background:#ffa502; border:none;">ADD REPORT</button>
                <button class="btn" onclick="closeModal()" style="background:#ff4757; border:none;">&times;</button>
            </div>
        </div>

        <div id="profile_view">
            <h3 id="m_name" style="margin-top: 15px; font-weight: 400; color: #eee;"></h3>
            <div class="detail-grid">
                <div class="detail-item"><label>Date of Birth</label><span id="m_dob"></span></div>
                <div class="detail-item"><label>Gender</label><span id="m_gender"></span></div>
                <div class="detail-item"><label>Blood Group</label><span id="m_blood"></span></div>
                <div class="detail-item"><label>NIC / ID</label><span id="m_nic"></span></div>
                <div class="detail-item"><label>Phone</label><span id="m_phone"></span></div>
                <div class="detail-item" style="grid-column: span 2;"><label>Medical Allergies</label><span id="m_allergies"></span></div>
                
                <div id="m_guardian_box" class="guardian-box">
                    <h4 style="color: #ffa502; margin: 0 0 10px 0;"><i class="fas fa-user-shield"></i> Guardian Details</h4>
                    <div style="display: flex; gap: 30px;">
                        <div><label style="color:#ffa502; font-size:10px;">NAME</label><span id="m_g_name"></span></div>
                        <div><label style="color:#ffa502; font-size:10px;">RELATION</label><span id="m_g_rel"></span></div>
                        <div><label style="color:#ffa502; font-size:10px;">NIC</label><span id="m_g_nic"></span></div>
                    </div>
                </div>

                <div class="detail-item" style="grid-column: span 2; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
                    <label>Emergency Contact (Next of Kin)</label>
                    <span id="m_em_name" style="font-weight: 600;"></span> 
                    <span id="m_em_phone" style="color: #00ff96; margin-left: 10px;"></span>
                </div>
            </div>
        </div>

        <div id="report_form">
            <h3 id="r_name" style="margin-top: 15px; color: #ffa502;"></h3>
            <form method="POST">
                <input type="hidden" name="p_id" id="rep_p_id">
                <div class="detail-grid" style="margin-top: 0;">
                    <input type="text" name="vitals" class="report-input" placeholder="Vitals (BP, Temp, Weight)">
                    <input type="text" name="symptoms" class="report-input" placeholder="Symptoms Observed">
                </div>
                <textarea name="diagnosis" class="report-input" placeholder="Diagnosis / Identified Disease"></textarea>
                <textarea name="prescription" class="report-input" placeholder="Prescription / Medication Plan"></textarea>
                <textarea name="remarks" class="report-input" placeholder="Additional Advice / Remarks"></textarea>
                <button type="submit" name="submit_report" class="btn" style="width: 100%; height: 50px; background: #00ff96; color: #1a1a2e; border:none; font-weight: bold;">✓ SUBMIT MEDICAL REPORT</button>
            </form>
        </div>
    </div>
</div>

<script>
    // SIDEBAR TOGGLE
    function showSection(id) {
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        document.getElementById(id).classList.add('active');
        document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
        document.getElementById('nav-' + id).classList.add('active');
    }

    // OPEN POP-UP
    function openModal(type, p) {
        document.getElementById('universalModal').style.display = 'flex';
        
        if(type === 'profile') {
            document.getElementById('modal_title').innerHTML = '<i class="fas fa-id-card"></i> Patient Profile';
            document.getElementById('profile_view').style.display = 'block';
            document.getElementById('report_form').style.display = 'none';
            document.getElementById('toggleReportBtn').innerText = "ADD REPORT";

            // Populate Profile Data
            document.getElementById('m_name').innerText = p.full_name;
            document.getElementById('m_dob').innerText = p.dob;
            document.getElementById('m_gender').innerText = p.gender;
            document.getElementById('m_blood').innerText = p.blood_group;
            document.getElementById('m_nic').innerText = p.nic;
            document.getElementById('m_phone').innerText = p.phone;
            document.getElementById('m_allergies').innerText = p.allergies || "None Provided";

            // Emergency Contact Data
            document.getElementById('m_em_name').innerText = p.emergency_contact_name || "N/A";
            document.getElementById('m_em_phone').innerText = p.emergency_phone ? "(" + p.emergency_phone + ")" : "";
            
            // Guardian Logic
            const gBox = document.getElementById('m_guardian_box');
            if(p.guardian_name) {
                gBox.style.display = 'block';
                document.getElementById('m_g_name').innerText = p.guardian_name;
                document.getElementById('m_g_rel').innerText = p.guardian_relation;
                document.getElementById('m_g_nic').innerText = p.guardian_nic;
            } else { gBox.style.display = 'none'; }

        } else {
            // Populate Report Form Data
            document.getElementById('modal_title').innerHTML = '<i class="fas fa-notes-medical"></i> Medical Report';
            document.getElementById('profile_view').style.display = 'none';
            document.getElementById('report_form').style.display = 'block';
            document.getElementById('toggleReportBtn').innerText = "BACK TO PROFILE";
            document.getElementById('r_name').innerText = "Registering Report for: " + p.full_name;
            document.getElementById('rep_p_id').value = p.id;
        }
    }

    // TOGGLE BETWEEN PROFILE AND REPORT INSIDE MODAL
    function toggleReportForm() {
        const form = document.getElementById('report_form');
        const view = document.getElementById('profile_view');
        const btn = document.getElementById('toggleReportBtn');
        if(form.style.display === 'none') {
            form.style.display = 'block'; view.style.display = 'none'; btn.innerText = "BACK TO PROFILE";
        } else {
            form.style.display = 'none'; view.style.display = 'block'; btn.innerText = "ADD REPORT";
        }
    }

    function closeModal() { document.getElementById('universalModal').style.display = 'none'; }
</script>

</body>
</html>