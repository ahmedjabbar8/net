<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// 1. Fetch Specific Lab Test Requests (Item Level - ONLY PAID)
$requests = $conn->query("SELECT l.*, p.full_name_ar as p_name, p.file_number 
                         FROM lab_requests l 
                         JOIN patients p ON l.patient_id = p.patient_id 
                         WHERE l.status = 'pending' AND DATE(l.created_at) = CURDATE()");

// --- 2. Action: Save specific result ---
if (isset($_POST['save_result'])) {
    if (isset($_SESSION['user_id'])) {
        $conn->query("UPDATE users SET current_task = 'إجراء فحص مختبري' WHERE user_id = " . $_SESSION['user_id']);
    }
    $id = intval($_POST['req_id']);
    $res = $conn->real_escape_string($_POST['result']);

    // Get appointment ID first
    $req_info = $conn->query("SELECT appointment_id FROM lab_requests WHERE request_id = $id")->fetch_assoc();
    $appt_id = $req_info['appointment_id'];

    $conn->query("UPDATE lab_requests SET result = '$res', status = 'completed' WHERE request_id = $id");

    // Logic: Return to Triage after ALL items are done
    $rem = $conn->query("SELECT COUNT(*) FROM lab_requests WHERE appointment_id = $appt_id AND status = 'pending'")->fetch_row()[0];

    if ($rem == 0) {
        $conn->query("UPDATE appointments SET status = 'waiting_doctor' WHERE appointment_id = $appt_id");
        $_SESSION['msg'] = "تم إكمال كافة التحاليل. المريض عاد لقائمة الطبيب مباشرة للمراجعة.";
    } else {
        $_SESSION['msg'] = "تم حفظ النتيجة بنجاح";
    }

    $_SESSION['msg_type'] = "success";
    header("Location: lab");
    exit();
}

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-info"><i class="fas fa-flask"></i> قسم المختبر (Laboratory)</h2>
</div>

<!-- Main Lab Requests Section -->
<div class="row">
    <div class="col-12">
        <h5 class="fw-bold mb-4 px-2"><i class="fas fa-list-ul me-2"></i> قائمة التحاليل المطلوبة (المدفوعة)</h5>
        <div class="row">
            <?php while ($r = $requests->fetch_assoc()): ?>
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm border-top border-info border-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="fw-bold mb-1"><?php echo $r['p_name']; ?></h6>
                                    <small class="text-muted"><?php echo $r['file_number']; ?></small>
                                </div>
                                <span
                                    class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2 rounded-pill"><?php echo $r['test_type']; ?></span>
                            </div>
                            <hr class="my-3 opacity-50">
                            <form method="POST">
                                <input type="hidden" name="req_id" value="<?php echo $r['request_id']; ?>">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">النتيجة المخبرية:</label>
                                    <textarea name="result" class="form-control form-control-sm" rows="3"
                                        placeholder="أدخل نتائج التحليل هنا.." required></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="save_result"
                                        class="btn btn-info text-white btn-sm flex-grow-1 fw-bold rounded-pill">
                                        <i class="fas fa-save me-1"></i> حفظ النتيجة
                                    </button>
                                    <a href="print_lab?id=<?php echo $r['request_id']; ?>"
                                        class="btn btn-outline-dark btn-sm rounded-circle"
                                        style="width:34px; height:34px; display:flex; align-items:center; justify-content:center;">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            <?php if ($requests->num_rows == 0): ?>
                <div class="col-12">
                    <div class="alert alert-light text-center py-5 border-dashed border-2 rounded-4">
                        <i class="fas fa-vial fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0 text-center">لا توجد طلبات تحاليل مدفوعة بانتظار التنفيذ حالياً</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>