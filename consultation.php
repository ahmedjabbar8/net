<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$appt_id = $_GET['id'] ?? null;
if (!$appt_id) {
    header("Location: doctor_clinic");
    exit();
}

// Fetch Patient & Appointment & Triage
$sql = "SELECT a.*, p.*, t.* FROM appointments a 
        JOIN patients p ON a.patient_id = p.patient_id 
        LEFT JOIN triage t ON a.appointment_id = t.appointment_id 
        WHERE a.appointment_id = $appt_id";
$data = $conn->query($sql)->fetch_assoc();
if (!$data) {
    header("Location: doctor_clinic");
    exit();
}

if (isset($_SESSION['user_id'])) {
    $p_name = $data['full_name_ar'] ?? 'مريض';
    $st = $conn->prepare("UPDATE users SET current_task = 'معاينة طبية جارية', active_patient_name = ? WHERE user_id = ?");
    $st->bind_param("si", $p_name, $_SESSION['user_id']);
    $st->execute();
}

$patient_id = $data['patient_id'];
$doctor_id = $_SESSION['user_id'];

// --- Get Dynamic Prices ---
$prices_res = $conn->query("SELECT * FROM system_settings WHERE setting_key LIKE 'price_%'");
$prices = [];
while ($pr = $prices_res->fetch_assoc())
    $prices[$pr['setting_key']] = $pr['setting_value'];

$lab_price = (float) ($prices['price_lab_default'] ?? 15000);
$rad_price = (float) ($prices['price_rad_default'] ?? 30000);
$rx_price = (float) ($prices['price_rx_default'] ?? 5000);
if ($lab_price <= 0)
    $lab_price = 15000;
if ($rad_price <= 0)
    $rad_price = 30000;
if ($rx_price <= 0)
    $rx_price = 5000;

// --- Actions Handling ---

// 1. Lab Request (Multi)
if (isset($_POST['send_labs'])) {
    if (!empty($_POST['selected_tests'])) {
        foreach ($_POST['selected_tests'] as $test) {
            $test = $conn->real_escape_string($test);
            // Fetch specific price from lab_tests
            $price_q = $conn->query("SELECT price FROM lab_tests WHERE test_name = '$test'");
            if ($price_q && $p_row = $price_q->fetch_assoc()) {
                $this_price = $p_row['price'];
            } else {
                $this_price = $lab_price; // Fallback to default
            }

            $conn->query("INSERT INTO lab_requests (appointment_id, patient_id, doctor_id, test_type, price, status) VALUES ($appt_id, $patient_id, $doctor_id, '$test', $this_price, 'pending_payment')");
        }
        $_SESSION['msg'] = "تم إرسال التحاليل بنجاح للمحاسبة";
        $_SESSION['msg_type'] = "info";
    }
}

// 2. Radiology (Multi)
if (isset($_POST['send_rads'])) {
    if (!empty($_POST['selected_scans'])) {
        foreach ($_POST['selected_scans'] as $scan) {
            $scan = $conn->real_escape_string($scan);
            $conn->query("INSERT INTO radiology_requests (appointment_id, patient_id, doctor_id, scan_type, price, status) VALUES ($appt_id, $patient_id, $doctor_id, '$scan', $rad_price, 'pending_payment')");
        }
        $_SESSION['msg'] = "تم إرسال طلبات الأشعة للمحاسبة";
        $_SESSION['msg_type'] = "info";
    }
}

// 3. Referral
if (isset($_POST['send_ref'])) {
    $to_dept = $_POST['to_dept'];
    $reason = $conn->real_escape_string($_POST['reason']);
    $conn->query("INSERT INTO referrals (appointment_id, patient_id, from_doctor_id, to_department_id, reason) VALUES ($appt_id, $patient_id, $doctor_id, $to_dept, '$reason')");
    $_SESSION['msg'] = "تم إحالة المريض بنجاح";
    $_SESSION['msg_type'] = "warning";
}

// 4. Finish Visit
if (isset($_POST['finish_visit'])) {
    $ass = $conn->real_escape_string($_POST['assessment']);
    $sub = $conn->real_escape_string($_POST['notes']);
    $meds = $conn->real_escape_string($_POST['rx']);

    $conn->query("INSERT INTO consultations (patient_id, doctor_id, appointment_id, subjective, assessment, plan) VALUES ($patient_id, $doctor_id, $appt_id, '$sub', '$ass', '$meds')");

    if (!empty($meds)) {
        $conn->query("INSERT INTO prescriptions (appointment_id, patient_id, doctor_id, medicine_name, price, status) VALUES ($appt_id, $patient_id, $doctor_id, '$meds', $rx_price, 'pending_payment')");
    }

    // --- SELF HEALING: Check if completed_at column exists ---
    $check_col = $conn->query("SHOW COLUMNS FROM appointments LIKE 'completed_at'");
    if ($check_col->num_rows == 0) {
        $conn->query("ALTER TABLE appointments ADD COLUMN completed_at DATETIME");
    }

    // Set completion time
    $conn->query("UPDATE appointments SET status = 'completed', completed_at = NOW() WHERE appointment_id = $appt_id");

    $_SESSION['msg'] = "تم إنهاء الزيارة وحفظ الملف الطبي";
    $_SESSION['msg_type'] = "success";
    header("Location: doctor_clinic");
    exit();
}

include 'header.php';

// Master data lists
// Fetch Lab List from DB
$lab_list = [];
$lab_res = $conn->query("SELECT test_name FROM lab_tests ORDER BY test_name ASC");
while ($lr = $lab_res->fetch_assoc()) {
    $lab_list[] = $lr['test_name'];
}
if (empty($lab_list)) {
    // Fallback if table empty (though setup script should have filled it)
    $lab_list = ['CBC', 'FBS', 'HBA1C', 'Urea', 'Creatinine', 'SGOT', 'SGPT', 'Lipid Profile', 'TSH', 'Vitamin D', 'CRP', 'Total Protein', 'Albumin', 'Bilirubin', 'Urine R/E', 'Stool R/E', 'Widal Test', 'H. Pylori', 'PSA', 'Troponin'];
}
$rad_list = ['X-Ray Chest', 'X-Ray Knee', 'X-Ray Spine', 'U/S Abdomen', 'U/S Pelvis', 'U/S Pregnancy', 'CT Brain', 'CT Chest', 'MRI Brain', 'MRI Spine'];
?>

<div class="row g-4">
    <div class="col-md-3">
        <!-- Patient Info Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body text-center">
                <div class="avatar-circle py-3 mb-2 bg-primary text-white mx-auto"
                    style="width:80px; height:80px; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-user fa-3x"></i>
                </div>
                <h5 class="fw-bold mb-0"><?php echo $data['full_name_ar']; ?></h5>
                <small class="text-muted d-block mb-3"><?php echo $data['file_number']; ?></small>

                <div class="text-start bg-light p-3 rounded">
                    <p class="mb-1 small"><strong>العمر:</strong> <?php echo $data['date_of_birth']; ?></p>
                    <p class="mb-1 small"><strong>الضغط:</strong> <span
                            class="text-danger"><?php echo $data['blood_pressure']; ?></span></p>
                    <p class="mb-0 small"><strong>الحرارة:</strong> <span
                            class="text-warning"><?php echo $data['temperature']; ?>°</span></p>
                </div>

                <hr>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#historyModal"><i
                            class="fas fa-history"></i> السجل السابق</button>
                    <a href="patient_file?id=<?php echo $patient_id; ?>" class="btn btn-primary btn-sm"><i
                            class="fas fa-file-pdf"></i> الملف الطبي الكامل</a>
                </div>
            </div>
        </div>

        <!-- Quick Results Tooltip (Labs) -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold small text-info"><i class="fas fa-flask"></i> نتائج المختبر
                (الحالية)</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush small">
                    <?php
                    $curr_labs = $conn->query("SELECT * FROM lab_requests WHERE appointment_id = $appt_id");
                    while ($cl = $curr_labs->fetch_assoc()): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-truncate" style="max-width: 120px;"><?php echo $cl['test_type']; ?></span>
                            <?php if ($cl['status'] == 'completed'): ?>
                                <span class="badge bg-success shadow-sm" title="<?php echo $cl['result']; ?>">جاهز</span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">انتظار</span>
                            <?php endif; ?>
                        </li>
                    <?php endwhile; ?>
                    <?php if ($curr_labs->num_rows == 0): ?>
                        <li class="list-group-item text-center text-muted py-2" style="font-size: 0.65rem;">لا توجد تحاليل
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Quick Results Tooltip (Radiology) -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold small text-secondary"><i class="fas fa-x-ray"></i> نتائج الأشعة
                (الحالية)</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush small">
                    <?php
                    $curr_rads = $conn->query("SELECT * FROM radiology_requests WHERE appointment_id = $appt_id");
                    while ($cr = $curr_rads->fetch_assoc()): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-truncate" style="max-width: 100px;"><?php echo $cr['scan_type']; ?></span>
                            <?php if ($cr['status'] == 'completed'): ?>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-success p-0 px-2 shadow-sm"
                                        title="<?php echo $cr['report']; ?>">جاهز</button>
                                    <?php if (!empty($cr['image_path'])): ?>
                                        <a href="<?php echo $cr['image_path']; ?>" target="_blank"
                                            class="btn btn-secondary p-0 px-2" title="فتح الملف"><i
                                                class="fas fa-file-download"></i></a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">انتظار</span>
                            <?php endif; ?>
                        </li>
                    <?php endwhile; ?>
                    <?php if ($curr_rads->num_rows == 0): ?>
                        <li class="list-group-item text-center text-muted py-2" style="font-size: 0.65rem;">لا توجد طلبات
                            أشعة</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- MAIN DASHBOARD -->
    <div class="col-md-9">
        <div class="card border-0 shadow-lg mb-4">
            <div class="card-header bg-white border-0 py-3">
                <ul class="nav nav-pills" id="doctorTab" role="tablist">
                    <li class="nav-item"><button class="nav-link active fw-bold px-4 me-2" data-bs-toggle="pill"
                            data-bs-target="#exam-panel"><i class="fas fa-stethoscope me-1"></i> المعاينة</button></li>
                    <li class="nav-item"><button class="nav-link fw-bold px-4 me-2" data-bs-toggle="pill"
                            data-bs-target="#lab-panel"><i class="fas fa-flask me-1"></i> المختبر</button></li>
                    <li class="nav-item"><button class="nav-link fw-bold px-4 me-2" data-bs-toggle="pill"
                            data-bs-target="#rad-panel"><i class="fas fa-x-ray me-1"></i> الأشعة</button></li>
                    <li class="nav-item"><button class="nav-link fw-bold px-4" data-bs-toggle="pill"
                            data-bs-target="#ref-panel"><i class="fas fa-share-square me-1"></i> إحالة</button></li>
                </ul>
            </div>

            <div class="card-body p-4 tab-content">
                <!-- EXAM PANEL -->
                <div class="tab-pane fade show active" id="exam-panel">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">الشكوى والملاحظات السريرية</label>
                            <textarea name="notes" class="form-control" rows="5"
                                placeholder="اكتب شكوى المريض وكشف الطبيب هنا..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">التشخيص (Assessment)</label>
                            <input type="text" name="assessment" class="form-control form-control-lg border-primary"
                                placeholder="أدخل التشخيص النهائي..." required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">الوصفة الطبية (Rx)</label>
                            <textarea name="rx" class="form-control" rows="3"
                                placeholder="أدخل العلاجات والجرعات..."></textarea>
                        </div>
                        <div class="d-grid"><button type="submit" name="finish_visit"
                                class="btn btn-success btn-lg shadow fw-bold">حفظ وإنهاء الزيارة بالكامل</button></div>
                    </form>
                </div>

                <!-- LAB PANEL (50+ Multi) -->
                <div class="tab-pane fade" id="lab-panel">
                    <h5 class="fw-bold mb-3 border-bottom pb-2 text-info">طلب تحاليل مختبرية</h5>
                    <form method="POST">
                        <div class="row g-2 overflow-auto" style="max-height: 400px;">
                            <?php foreach ($lab_list as $l): ?>
                                <div class="col-md-3">
                                    <div class="form-check p-2 border rounded hover-bg">
                                        <input class="form-check-input ms-0 me-2" type="checkbox" name="selected_tests[]"
                                            value="<?php echo $l; ?>" id="lab-<?php echo $l; ?>">
                                        <label class="form-check-label w-100"
                                            for="lab-<?php echo $l; ?>"><?php echo $l; ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-grid mt-4"><button type="submit" name="send_labs"
                                class="btn btn-info text-white fw-bold">إرسال الفحوصات المختارة للمحاسبة</button></div>
                    </form>
                </div>

                <!-- RAD PANEL -->
                <div class="tab-pane fade" id="rad-panel">
                    <h5 class="fw-bold mb-3 border-bottom pb-2 text-secondary">طلب فحوصات إشعاعية</h5>
                    <form method="POST">
                        <div class="row g-2">
                            <?php foreach ($rad_list as $r): ?>
                                <div class="col-md-4">
                                    <div class="form-check p-2 border rounded hover-bg">
                                        <input class="form-check-input ms-0 me-2" type="checkbox" name="selected_scans[]"
                                            value="<?php echo $r; ?>" id="rad-<?php echo $r; ?>">
                                        <label class="form-check-label w-100"
                                            for="rad-<?php echo $r; ?>"><?php echo $r; ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-grid mt-4"><button type="submit" name="send_rads"
                                class="btn btn-secondary fw-bold">إرسال طلبات الأشعة للمحاسبة</button></div>
                    </form>
                </div>

                <!-- REFERRAL PANEL -->
                <div class="tab-pane fade" id="ref-panel">
                    <h5 class="fw-bold mb-3 border-bottom pb-2 text-warning">تحويل (إحالة) المريض</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">إحالة إلى عيادة:</label>
                            <select name="to_dept" class="form-select">
                                <?php
                                $depts = $conn->query("SELECT * FROM departments WHERE department_type = 'medical'");
                                while ($d = $depts->fetch_assoc())
                                    echo "<option value='" . $d['department_id'] . "'>" . $d['department_name_ar'] . "</option>";
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">سبب الإحالة والملاحظات</label>
                            <textarea name="reason" class="form-control" rows="3"
                                placeholder="لماذا يتم تحويل المريض؟"></textarea>
                        </div>
                        <div class="d-grid"><button type="submit" name="send_ref" class="btn btn-warning fw-bold">إتمام
                                طلب الإحالة</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HISTORY MODAL -->
<div class="modal fade" id="historyModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">السجل الطبي السابق للمريض</h5><button type="button"
                    class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body overflow-auto" style="max-height: 70vh;">
                <?php
                $h_sql = "SELECT c.*, u.full_name_ar as doc_name FROM consultations c JOIN users u ON c.doctor_id = u.user_id WHERE c.patient_id = $patient_id ORDER BY c.created_at DESC";
                $h_res = $conn->query($h_sql);
                while ($h = $h_res->fetch_assoc()): ?>
                    <div class="card mb-3 border-0 shadow-sm border-start border-primary border-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2 small text-muted">
                                <span><?php echo $h['created_at']; ?></span><span>د. <?php echo $h['doc_name']; ?></span>
                            </div>
                            <h6 class="fw-bold text-danger">التشخيص: <?php echo $h['assessment']; ?></h6>
                            <p class="mb-0 small"><?php echo nl2br($h['subjective']); ?></p>
                        </div>
                    </div>
                <?php endwhile;
                if ($h_res->num_rows == 0)
                    echo '<p class="text-center text-muted py-5">لا يوجد سجل تاريخي متاح</p>'; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .nav-pills .nav-link {
        border: 1px solid #eee;
        border-radius: 10px;
        color: #666;
        transition: all 0.3s;
    }

    .nav-pills .nav-link.active {
        background: #3498db;
        color: white;
        border-color: #3498db;
        box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
    }

    .hover-bg:hover {
        background: #f8f9fa;
        cursor: pointer;
        border-color: #3498db;
    }
</style>

<?php include 'footer.php'; ?>