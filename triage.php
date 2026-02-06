<?php
include 'config.php';

// SELF-HEALING: Update Schema
$conn->query("ALTER TABLE triage ADD COLUMN IF NOT EXISTS oxygen VARCHAR(20)");
$conn->query("ALTER TABLE triage MODIFY COLUMN height VARCHAR(20)");
$conn->query("ALTER TABLE triage MODIFY COLUMN weight VARCHAR(20)");
$conn->query("ALTER TABLE triage MODIFY COLUMN temperature VARCHAR(20)");
$conn->query("ALTER TABLE triage ADD COLUMN IF NOT EXISTS nurse_notes TEXT");
$conn->query("ALTER TABLE appointments ADD COLUMN IF NOT EXISTS is_urgent INT DEFAULT 0");

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}
if (!in_array($_SESSION['role'], ['nurse', 'admin'])) {
    header("Location: index");
    exit();
}

// List patients waiting for Initial Examination
$sql_q = "SELECT a.*, p.full_name_ar as p_name, p.gender, p.file_number, p.photo, p.date_of_birth 
          FROM appointments a 
          JOIN patients p ON a.patient_id = p.patient_id 
          WHERE a.status = 'pending_triage' 
          AND DATE(a.appointment_date) = CURDATE()
          ORDER BY a.created_at ASC";
$queue = $conn->query($sql_q);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['appointment_id'])) {
    $aid = intval($_POST['appointment_id']);
    // Get patient name for status broadcast
    $p_info = $conn->query("SELECT p.full_name_ar FROM patients p JOIN appointments a ON p.patient_id = a.patient_id WHERE a.appointment_id = $aid")->fetch_assoc();
    $p_name = $p_info['full_name_ar'] ?? 'مريض';

    if (isset($_SESSION['user_id'])) {
        $st = $conn->prepare("UPDATE users SET current_task = 'قياس العلامات الحيوية', active_patient_name = ? WHERE user_id = ?");
        $st->bind_param("si", $p_name, $_SESSION['user_id']);
        $st->execute();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['save_triage'])) { // This block handles the form submission for saving triage data
    if (isset($_SESSION['user_id'])) {
        // Clear active patient name after triage is saved
        $conn->query("UPDATE users SET current_task = NULL, active_patient_name = NULL WHERE user_id = " . $_SESSION['user_id']);
    }
    $appt_id = $_POST['appt_id'];
    if (empty($appt_id)) {
        die("Error: Appointment ID is missing");
    }

    $weight = $conn->real_escape_string($_POST['weight']);
    $height = $conn->real_escape_string($_POST['height']);
    $temp = $conn->real_escape_string($_POST['temp']);
    $bp = $conn->real_escape_string($_POST['bp']);
    $pulse = $conn->real_escape_string($_POST['pulse']);
    $oxygen = $conn->real_escape_string($_POST['oxygen']);
    $notes = $conn->real_escape_string($_POST['notes']);
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;

    $insert_sql = "INSERT INTO triage (appointment_id, weight, height, temperature, blood_pressure, pulse, oxygen, nurse_notes) 
                  VALUES ($appt_id, '$weight', '$height', '$temp', '$bp', '$pulse', '$oxygen', '$notes')";

    if (!$conn->query($insert_sql)) {
        die("Database Error (Insert Triage): " . $conn->error);
    }

    // Update appointment status and urgency
    $update_sql = "UPDATE appointments SET status = 'waiting_doctor', is_urgent = $is_urgent WHERE appointment_id = $appt_id";
    if (!$conn->query($update_sql)) {
        die("Database Error (Update Appointment): " . $conn->error);
    }

    $_SESSION['msg'] = "تم تسجيل البيانات وتحويل المريض للطبيب بنجاح";
    $_SESSION['msg_type'] = "success";
    header("Location: triage"); // Refresh current page
    exit();
}

include 'header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0"><i class="fas fa-user-nurse text-danger me-2"></i>قسم الفحص الأولي (Triage)</h2>
        <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-2"><?php echo $queue->num_rows; ?> في
            الانتظار</span>
    </div>

    <?php if (isset($_SESSION['msg'])): ?>
        <div
            class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4">
            <?php echo $_SESSION['msg'];
            unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex flex-column gap-3">
        <?php while ($r = $queue->fetch_assoc()): ?>
            <div class="patient-list-item d-flex align-items-center justify-content-between p-3 bg-white rounded-4 shadow-sm border border-transparent"
                onclick="openTriageModal(<?php echo htmlspecialchars(json_encode($r)); ?>)"
                style="cursor: pointer; transition: all 0.2s;">

                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm">
                        <?php if (!empty($r['photo']) && file_exists(__DIR__ . '/' . $r['photo'])): ?>
                            <img src="<?php echo $r['photo']; ?>" class="rounded-circle shadow-sm border border-2 border-white"
                                style="width: 55px; height: 55px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center border border-danger-subtle"
                                style="width: 55px; height: 55px;">
                                <i class="fas fa-user-injured fa-lg"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><?php echo $r['p_name']; ?></h6>
                        <div class="text-muted small d-flex align-items-center gap-2">
                            <span class="badge bg-light text-dark border rounded-pill px-2">ID:
                                <?php echo $r['file_number']; ?></span>
                        </div>
                    </div>
                </div>

                <div class="d-none d-md-flex align-items-center gap-4 text-secondary">
                    <div class="d-flex align-items-center gap-1" title="الجنس">
                        <i class="fas fa-venus-mars text-muted"></i>
                        <span><?php echo $r['gender'] == 'male' ? 'ذكر' : 'أنثى'; ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-1" title="العمر">
                        <i class="fas fa-birthday-cake text-muted"></i>
                        <span><?php echo date('Y') - date('Y', strtotime($r['date_of_birth'])); ?> سنة</span>
                    </div>
                </div>

                <div>
                    <button class="btn btn-danger-subtle btn-sm rounded-pill fw-bold px-4 py-2 border-0">
                        <i class="fas fa-stethoscope me-1"></i> فحص
                    </button>
                </div>
            </div>
        <?php endwhile; ?>

        <?php if ($queue->num_rows == 0): ?>
            <div class="text-center py-5 apple-card bg-white mt-4">
                <i class="fas fa-check-circle text-success fa-4x mb-3 opacity-25"></i>
                <h5 class="text-muted">لا يوجد مراجعين في قائمة الانتظار حالياً</h5>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Triage Form Modal -->
<div class="modal fade" id="triageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white py-3 border-0">
                <h5 class="modal-title fw-bold" id="modalPatientName">تسجيل الفحص الأولي</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="save_triage.php">
                <div class="modal-body p-4 bg-light bg-opacity-50">
                    <input type="hidden" name="appt_id" id="modalApptId">

                    <div class="row g-3">
                        <!-- Weight & Height -->
                        <div class="col-md-6 text-end">
                            <label class="form-label small fw-bold text-muted">الوزن (kg)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0 shadow-sm"><i
                                        class="fas fa-weight text-danger"></i></span>
                                <input type="text" name="weight"
                                    class="form-control border-0 shadow-sm text-center fw-bold" placeholder="0.0">
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <label class="form-label small fw-bold text-muted">الطول (cm)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0 shadow-sm"><i
                                        class="fas fa-ruler-vertical text-danger"></i></span>
                                <input type="text" name="height"
                                    class="form-control border-0 shadow-sm text-center fw-bold" placeholder="0">
                            </div>
                        </div>

                        <!-- BP & Pulse -->
                        <div class="col-md-6 text-end">
                            <label class="form-label small fw-bold text-muted">الضغط (BP)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0 shadow-sm"><i
                                        class="fas fa-heartbeat text-danger"></i></span>
                                <input type="text" name="bp" class="form-control border-0 shadow-sm text-center fw-bold"
                                    placeholder="120/80">
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <label class="form-label small fw-bold text-muted">النبض (Pulse)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0 shadow-sm"><i
                                        class="fas fa-tachometer-alt text-danger"></i></span>
                                <input type="text" name="pulse"
                                    class="form-control border-0 shadow-sm text-center fw-bold" placeholder="72">
                            </div>
                        </div>

                        <!-- Temp & Oxygen -->
                        <div class="col-md-6 text-end">
                            <label class="form-label small fw-bold text-muted">الحرارة (Temp)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0 shadow-sm"><i
                                        class="fas fa-thermometer-half text-danger"></i></span>
                                <input type="text" name="temp"
                                    class="form-control border-0 shadow-sm text-center fw-bold" placeholder="37.0">
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <label class="form-label small fw-bold text-muted">الأوكسجين (O2)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0 shadow-sm"><i
                                        class="fas fa-lungs text-danger"></i></span>
                                <input type="text" name="oxygen"
                                    class="form-control border-0 shadow-sm text-center fw-bold" placeholder="98%">
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="col-12 text-end">
                            <label class="form-label small fw-bold text-muted">التفاصيل / ملاحظات إضافية</label>
                            <textarea name="notes" class="form-control border-0 shadow-sm" rows="3"
                                placeholder="اكتب أي ملاحظات هنا..."></textarea>
                        </div>

                        <!-- Urgent Toggle -->
                        <div class="col-12">
                            <div
                                class="form-check form-switch p-3 bg-white rounded-4 shadow-sm border border-danger border-opacity-10 d-flex justify-content-between align-items-center">
                                <label class="form-check-label fw-bold text-danger mb-0" for="isUrgentSwitch">
                                    <i class="fas fa-exclamation-triangle me-2"></i> حالة حرجة / تعبانة (أولوية قصوى)
                                </label>
                                <input class="form-check-input ms-0" type="checkbox" name="is_urgent"
                                    id="isUrgentSwitch">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 bg-light bg-opacity-75">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold"
                        data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-5 fw-bold shadow">حفظ وإرسال
                        للطبيب</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .patient-file-card {
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid transparent;
        background: #fff;
    }

    .patient-file-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
        border-color: rgba(220, 53, 69, 0.2);
    }

    .btn-danger-subtle {
        background: #fdf2f2;
        color: #dc3545;
        border: 1px solid #fee2e2;
    }

    .btn-danger-subtle:hover {
        background: #dc3545;
        color: #fff;
    }

    .form-check-input:checked {
        background-color: #dc3545;
        border-color: #dc3545;
    }

    .input-group-text {
        font-size: 1.1rem;
    }
</style>

<script>
    function openTriageModal(patient) {
        // Reset form first
        const form = document.querySelector('#triageModal form');
        form.reset();

        document.getElementById('modalPatientName').innerText = 'فحص: ' + patient.p_name;
        document.getElementById('modalApptId').value = patient.appointment_id;

        var myModal = new bootstrap.Modal(document.getElementById('triageModal'));
        myModal.show();
    }
</script>

<?php include 'footer.php'; ?>