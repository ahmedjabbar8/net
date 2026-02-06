<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$patient_id = $_GET['id'] ?? null;
if (!$patient_id) {
    header("Location: patients");
    exit();
}

$p = $conn->query("SELECT * FROM patients WHERE patient_id = $patient_id")->fetch_assoc();

include 'header.php';

// --- Handle Archive Upload ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_archive'])) {
    $file_desc = $conn->real_escape_string($_POST['file_name']);
    $upload_dir = 'uploads/archive/';
    if (!file_exists($upload_dir))
        mkdir($upload_dir, 0777, true);

    $file_ext = strtolower(pathinfo($_FILES['archive_file']['name'], PATHINFO_EXTENSION));
    $new_name = uniqid('arch_') . '.' . $file_ext;
    $target_file = $upload_dir . $new_name;

    // Default system user if session not set (for safety)
    $doc_id = $_SESSION['user_id'] ?? 1;

    if (move_uploaded_file($_FILES['archive_file']['tmp_name'], $target_file)) {
        $conn->query("INSERT INTO radiology_requests (patient_id, doctor_id, scan_type, image_path, status, created_at) VALUES ($patient_id, $doc_id, '$file_desc', '$target_file', 'completed', NOW())");
        echo "<script>alert('تم أرشفة الملف بنجاح'); window.location.href='patient_file?id=$patient_id';</script>";
    } else {
        echo "<script>alert('حدث خطأ أثناء رفع الملف');</script>";
    }
}
?>

<!-- Include Barcode Library -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<div class="container-fluid py-4 no-print">
    <!-- Header: Patient Info & Barcode -->
    <div class="glass-card rounded-4 p-4 mb-4 position-relative overflow-hidden">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4">
            <div class="d-flex align-items-center gap-4">
                <div class="avatar-xl shadow-sm rounded-circle overflow-hidden border border-3 border-white"
                    style="width: 100px; height: 100px;">
                    <?php if (!empty($p['photo']) && file_exists(__DIR__ . '/' . $p['photo'])): ?>
                        <img src="<?php echo $p['photo']; ?>" class="w-100 h-100" style="object-fit: cover;">
                    <?php else: ?>
                        <div class="w-100 h-100 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center"
                            style="font-size: 2.5rem;">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <h2 class="fw-bold mb-1"><?php echo $p['full_name_ar']; ?></h2>
                    <div class="d-flex flex-wrap gap-3 text-muted">
                        <span class="badge bg-light text-dark border"><i class="fas fa-venus-mars me-1"></i>
                            <?php echo $p['gender'] == 'male' ? 'ذكر' : 'أنثى'; ?></span>
                        <span><i class="fas fa-birthday-cake me-1"></i> <?php echo $p['date_of_birth']; ?></span>
                        <span><i class="fas fa-phone me-1"></i> <?php echo $p['phone1']; ?></span>
                        <span><i class="fas fa-id-card me-1"></i> <?php echo $p['national_id']; ?></span>
                    </div>
                </div>
            </div>

            <div class="text-center bg-white p-2 rounded-3 shadow-sm border">
                <svg id="barcode"></svg>
            </div>
        </div>
    </div>

    <!-- User Request: 5 Specific Options -->
    <div class="row g-3 mb-5">
        <!-- Patient File Button Hidden As Requested -->

        <div class="col">
            <a href="edit_patient?id=<?php echo $patient_id; ?>" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm hover-scale text-center py-4 bg-white text-dark">
                    <i class="fas fa-user-edit fa-2x mb-2 text-primary"></i>
                    <h6 class="fw-bold m-0">تعديل المعلومات</h6>
                </div>
            </a>
        </div>
        <div class="col">
            <div onclick="showSection('reports-section')"
                class="card h-100 border-0 shadow-sm hover-scale text-center py-4 bg-white text-dark">
                <i class="fas fa-notes-medical fa-2x mb-2 text-success"></i>
                <h6 class="fw-bold m-0">التقارير الطبية</h6>
            </div>
        </div>
        <div class="col">
            <div onclick="showSection('archive-section')"
                class="card h-100 border-0 shadow-sm hover-scale text-center py-4 bg-white text-dark">
                <i class="fas fa-folder-open fa-2x mb-2 text-warning"></i>
                <h6 class="fw-bold m-0">أرشفة ملفات</h6>
            </div>
        </div>
        <div class="col">
            <a href="book?id=<?php echo $patient_id; ?>" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm hover-scale text-center py-4 bg-danger text-white">
                    <i class="fas fa-calendar-plus fa-2x mb-2 opacity-50"></i>
                    <h6 class="fw-bold m-0">حجز جديد</h6>
                </div>
            </a>
        </div>
    </div>

    <!-- Dynamic Content Sections -->
    <div id="content-display">
        <!-- Main Medical History (Patient File) -->
        <div class="section-content text-start animate__animated animate__fadeIn" id="history-section"
            style="display: none;">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white fw-bold py-3"><i class="fas fa-notes-medical me-2"></i>
                    السجل الطبي والمعاينات (ملف المريض)</div>
                <div class="card-body">
                    <?php
                    $history = $conn->query("SELECT c.*, u.full_name_ar as doc_name FROM consultations c JOIN users u ON c.doctor_id = u.user_id WHERE c.patient_id = $patient_id ORDER BY c.created_at DESC");
                    if ($history->num_rows > 0):
                        while ($h = $history->fetch_assoc()): ?>
                            <div class="border-bottom mb-4 pb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-light text-dark border">
                                        <?php echo date('Y-m-d H:i', strtotime($h['created_at'])); ?>
                                    </span>
                                    <span class="small text-muted">د. <?php echo $h['doc_name']; ?></span>
                                </div>
                                <h6 class="fw-bold text-danger">التشخيص: <?php echo $h['assessment']; ?></h6>
                                <p class="mb-1 small text-muted"><?php echo $h['subjective']; ?></p>
                                <div class="bg-light p-2 rounded small mt-2">
                                    <strong>الخطة والعلاج:</strong> <?php echo $h['plan']; ?>
                                </div>
                            </div>
                        <?php endwhile;
                    else: ?>
                        <div class="text-center text-muted py-5">لا يوجد سجل تاريخ مرضي مسجل</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Medical Reports (Pharmacy & Lab) -->
        <div class="section-content animate__animated animate__fadeIn" id="reports-section" style="display: none;">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-success text-white fw-bold"><i class="fas fa-pills me-2"></i> سجل
                            الأدوية والوصفات</div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>العلاج</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $prescs = $conn->query("SELECT * FROM prescriptions WHERE patient_id = $patient_id ORDER BY created_at DESC LIMIT 10");
                                    while ($pr = $prescs->fetch_assoc()): ?>
                                        <tr>
                                            <td><small><?php echo date('Y-m-d', strtotime($pr['created_at'])); ?></small>
                                            </td>
                                            <td><?php echo nl2br($pr['medicine_name']); ?></td>
                                            <td>
                                                <span
                                                    class="badge bg-<?php echo ($pr['status'] == 'dispensed' ? 'success' : 'warning text-dark'); ?>">
                                                    <?php echo ($pr['status'] == 'dispensed' ? 'تم الصرف' : 'قيد الانتظار'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-info text-white fw-bold"><i class="fas fa-microscope me-2"></i> سجل
                            التحاليل والمختبر</div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>التحليل</th>
                                        <th>النتيجة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $labs = $conn->query("SELECT * FROM lab_requests WHERE patient_id = $patient_id ORDER BY created_at DESC LIMIT 10");
                                    while ($l = $labs->fetch_assoc()): ?>
                                        <tr>
                                            <td><small><?php echo date('Y-m-d', strtotime($l['created_at'])); ?></small>
                                            </td>
                                            <td><?php echo $l['test_type']; ?></td>
                                            <td><?php echo $l['result'] ?: '<span class="text-muted">---</span>'; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Archive (Radiology & Files) -->
        <div class="section-content animate__animated animate__fadeIn" id="archive-section" style="display: none;">
            <div class="card border-0 shadow-sm">
                <div
                    class="card-header bg-secondary text-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-folder-open me-2"></i> أرشيف الملفات والأشعة</span>
                </div>
                <div class="card-body">
                    <!-- Upload Form -->
                    <form method="POST" enctype="multipart/form-data"
                        class="mb-4 p-3 bg-light rounded border border-secondary border-opacity-25">
                        <h6 class="fw-bold text-secondary mb-3"><i class="fas fa-cloud-upload-alt me-2"></i> إضافة ملف
                            جديد للأرشيف</h6>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="small text-muted mb-1">اسم الملف / الوصف</label>
                                <input type="text" name="file_name" class="form-control" required
                                    placeholder="مثال: أشعة صدر، تقرير خروج...">
                            </div>
                            <div class="col-md-5">
                                <label class="small text-muted mb-1">الملف (صورة أو PDF)</label>
                                <input type="file" name="archive_file" class="form-control" accept="image/*,.pdf"
                                    required>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" name="upload_archive" class="btn btn-secondary w-100"><i
                                        class="fas fa-save"></i> حفظ</button>
                            </div>
                        </div>
                    </form>

                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>التاريخ</th>
                                <th>نوع الملف / الفحص</th>
                                <th>عرض الملف</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rads = $conn->query("SELECT * FROM radiology_requests WHERE patient_id = $patient_id ORDER BY created_at DESC");
                            if ($rads->num_rows > 0):
                                while ($r = $rads->fetch_assoc()): ?>
                                    <tr>
                                        <td><small><?php echo date('Y-m-d', strtotime($r['created_at'])); ?></small></td>
                                        <td><?php echo $r['scan_type']; ?></td>
                                        <td>
                                            <?php if (!empty($r['image_path'])): ?>
                                                <a href="<?php echo $r['image_path']; ?>" target="_blank"
                                                    class="btn btn-sm btn-outline-dark rounded-pill px-3">
                                                    <i class="fas fa-eye me-1"></i> معاينة
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">لا يوجد ملف</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">لا توجد ملفات مؤرشفة</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize Barcode
    JsBarcode("#barcode", "<?php echo $p['file_number']; ?>", {
        format: "CODE128",
        lineColor: "#000",
        width: 2,
        height: 60,
        displayValue: true
    });
</script>

<style>
    .hover-scale {
        transition: transform 0.2s;
        cursor: pointer;
    }

    .hover-scale:hover {
        transform: translateY(-5px);
    }
</style>

<?php include 'footer.php'; ?>