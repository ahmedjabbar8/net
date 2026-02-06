<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$search = $_GET['search'] ?? '';

$patients = null;
if (!empty($search)) {
    // Query to get patients and check for any unpaid items (Debt)
    $sql = "
        SELECT p.*, 
        (
            SELECT COUNT(*) FROM appointments WHERE patient_id = p.patient_id AND status = 'scheduled'
        ) +
        (
            SELECT COUNT(*) FROM lab_requests WHERE patient_id = p.patient_id AND status = 'pending_payment'
        ) +
        (
            SELECT COUNT(*) FROM radiology_requests WHERE patient_id = p.patient_id AND status = 'pending_payment'
        ) +
        (
            SELECT COUNT(*) FROM prescriptions WHERE patient_id = p.patient_id AND status = 'pending_payment'
        ) as debt_count
        FROM patients p
        WHERE p.full_name_ar LIKE '%$search%' OR p.file_number LIKE '%$search%' OR p.national_id LIKE '%$search%'
        ORDER BY p.created_at DESC
    ";
    $patients = $conn->query($sql);
}

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-dark"><i class="fas fa-archive me-2"></i> أرشيف المرضى الإلكتروني</h2>
    <a href="add_patient" class="btn btn-primary"><i class="fas fa-user-plus me-1"></i> إضافة مريض جديد</a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control" placeholder="ابحث باسم المريض أو رقم الملف..."
                    value="<?php echo $search; ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-dark w-100">بحث بالأرشيف</button>
            </div>
        </form>
    </div>
</div>

<div class="row row-cols-1 row-cols-md-3 g-4">
    <?php if ($patients && $patients->num_rows > 0): ?>
        <?php while ($p = $patients->fetch_assoc()):
            $has_debt = $p['debt_count'] > 0;
            ?>
            <div class="col">
                <div class="card h-100 border-0 shadow-sm transition-hover" style="background: var(--card);">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-file-medical fa-3x text-<?php echo $has_debt ? 'danger' : 'success'; ?>"></i>
                        </div>
                        <h5 class="fw-bold mb-1">
                            <?php echo $p['full_name_ar']; ?>
                        </h5>
                        <p class="text-muted small mb-3">
                            <?php echo $p['file_number']; ?>
                        </p>

                        <div
                            class="p-2 rounded mb-3 <?php echo $has_debt ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'; ?>">
                            <small class="fw-bold">
                                <i class="fas <?php echo $has_debt ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i>
                                <?php echo $has_debt ? 'توجد ذمة مالية معلقة' : 'لا توجد ذمة مالية - الحساب صافي'; ?>
                            </small>
                        </div>

                        <div class="d-grid gap-2">
                            <?php if (!$has_debt): ?>
                                <a href="patient_file?id=<?php echo $p['patient_id']; ?>"
                                    class="btn btn-outline-dark btn-sm rounded-pill">عرض السجل الطبي</a>
                                <a href="edit_patient?id=<?php echo $p['patient_id']; ?>"
                                    class="btn btn-outline-primary btn-sm rounded-pill">تعديل البيانات</a>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm rounded-pill" disabled
                                    title="لا يمكن التعديل أو الفتح لوجود مطالبات مالية">مغلق (وجود ذمة مالية)</button>
                                <a href="billing" class="btn btn-link btn-sm text-danger">انتقال لتسوية الحسابات</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php elseif (!empty($search)): ?>
        <div class="col-12 text-center py-5">
            <i class="fas fa-search-minus fa-3x text-muted mb-3"></i>
            <h4>لم يتم العثور على مريض بهذا الاسم أو الرقم</h4>
            <p class="text-muted">تأكد من كتابة الاسم بشكل صحيح أو جرب رقم الملف</p>
        </div>
    <?php else: ?>
        <div class="col-12 text-center py-5">
            <div class="p-5 apple-card shadow-none border-dashed" style="border: 2px dashed rgba(0,0,0,0.1);">
                <i class="fas fa-search fa-3x text-primary mb-3"></i>
                <h4>أرشيف المستشفى متاح للبحث الآن</h4>
                <p class="text-muted">أدخل بيانات المريض في الخانة أعلاه للبدء في استعراض السجلات الطبية المؤرشفة</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .transition-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        transition: all 0.3s;
    }

    .bg-danger-subtle {
        background-color: #fceaea;
    }

    .bg-success-subtle {
        background-color: #eafaf1;
    }
</style>

<?php include 'footer.php'; ?>