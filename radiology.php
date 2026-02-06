<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// 1. Fetch Specific Radiology Requests (Item Level - ONLY PAID)
$requests = $conn->query("SELECT r.*, p.full_name_ar as p_name, p.file_number 
                         FROM radiology_requests r 
                         JOIN patients p ON r.patient_id = p.patient_id 
                         WHERE r.status = 'pending' AND DATE(r.created_at) = CURDATE()");

// --- Actions ---
// Save specific report & Upload File
if (isset($_POST['save_report'])) {
    if (isset($_SESSION['user_id'])) {
        $conn->query("UPDATE users SET current_task = 'تصوير شعاعي' WHERE user_id = " . $_SESSION['user_id']);
    }
    $id = intval($_POST['req_id']);
    $rep = $conn->real_escape_string($_POST['report']);

    // File Upload handling
    $target_file = null;
    if (isset($_FILES['radiology_file']) && $_FILES['radiology_file']['error'] == 0) {
        $target_dir = "uploads/radiology/";
        if (!is_dir($target_dir))
            mkdir($target_dir, 0777, true);

        $file_ext = strtolower(pathinfo($_FILES["radiology_file"]["name"], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'dcm', 'dicom'];

        if (in_array($file_ext, $allowed)) {
            $file_name = "rad_" . $id . "_" . time() . "." . $file_ext;
            $target_file = $target_dir . $file_name;
            move_uploaded_file($_FILES["radiology_file"]["tmp_name"], $target_file);
        }
    }

    // Get appointment ID first
    $req_info = $conn->query("SELECT appointment_id FROM radiology_requests WHERE request_id = $id")->fetch_assoc();
    $appt_id = $req_info['appointment_id'];

    $sql = "UPDATE radiology_requests SET report = '$rep', status = 'completed'";
    if ($target_file) {
        $sql .= ", image_path = '$target_file'";
    }
    $sql .= " WHERE request_id = $id";
    $conn->query($sql);

    // Logic: Return to Doctor Queue after ALL items are done
    $rem = $conn->query("SELECT COUNT(*) FROM radiology_requests WHERE appointment_id = $appt_id AND status = 'pending'")->fetch_row()[0];

    if ($rem == 0) {
        $conn->query("UPDATE appointments SET status = 'waiting_doctor' WHERE appointment_id = $appt_id");
        $_SESSION['msg'] = "تم إكمال التقرير ورفع الملف بنجاح. المريض عاد لقائمة الطبيب للمراجعة.";
    } else {
        $_SESSION['msg'] = "تم حفظ التقرير والملف بنجاح";
    }

    $_SESSION['msg_type'] = "success";
    header("Location: radiology");
    exit();
}

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-secondary"><i class="fas fa-x-ray"></i> قسم الأشعة (Radiology)</h2>
</div>

<!-- Main Radiology Requests Section -->
<div class="row">
    <div class="col-12">
        <h5 class="fw-bold mb-4 px-2"><i class="fas fa-list-ul me-2"></i> قائمة الفحوصات الإشعاعية (المدفوعة)</h5>
        <div class="row">
            <?php while ($r = $requests->fetch_assoc()): ?>
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm border-top border-secondary border-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="fw-bold mb-1"><?php echo $r['p_name']; ?></h6>
                                    <small class="text-muted"><?php echo $r['file_number']; ?></small>
                                </div>
                                <span
                                    class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill"><?php echo $r['scan_type']; ?></span>
                            </div>
                            <hr class="my-3 opacity-50">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="req_id" value="<?php echo $r['request_id']; ?>">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">التقرير الشعاعي:</label>
                                    <textarea name="report" class="form-control form-control-sm border-0 bg-light" rows="3"
                                        placeholder="اكتب وصف التقرير الشعاعي هنا.." required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-primary">رفع صور أو ملفات الأشعة (JPG, PDF,
                                        DICOM):</label>
                                    <div class="input-group input-group-sm">
                                        <input type="file" name="radiology_file" class="form-control border-dashed"
                                            accept=".jpg,.jpeg,.png,.pdf,.dcm,.dicom">
                                    </div>
                                    <div class="form-text" style="font-size: 0.65rem;">يمكنك رفع صورة واحدة أو ملف بصيغة
                                        (PDF/DICOM)</div>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" name="save_report"
                                        class="btn btn-secondary text-white btn-sm fw-bold rounded-pill shadow-sm">
                                        <i class="fas fa-cloud-upload-alt me-1"></i> إرسال التقرير والملف للطبيب
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            <?php if ($requests->num_rows == 0): ?>
                <div class="col-12">
                    <div class="alert alert-light text-center py-5 border-dashed border-2 rounded-4">
                        <i class="fas fa-image fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0 text-center">لا توجد طلبات أشعة بانتظار التنفيذ حالياً</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>