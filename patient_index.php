<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$search = $_GET['q'] ?? '';
$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM appointments WHERE patient_id = p.patient_id) as visit_count,
        (SELECT MAX(appointment_date) FROM appointments WHERE patient_id = p.patient_id) as last_visit
        FROM patients p ";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " WHERE p.full_name_ar LIKE '%$search%' OR p.file_number LIKE '%$search%' OR p.national_id LIKE '%$search%' ";
}

$sql .= " ORDER BY p.full_name_ar ASC LIMIT 50";
$result = $conn->query($sql);

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-dark"><i class="fas fa-address-book text-primary me-2"></i> فهرس المرضى والمراجعات</h2>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-10">
                <input type="text" name="q" class="form-control apple-form-control" placeholder="ابحث بالاسم، رقم الملف، أو الهوية..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">بحث في الفهرس</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">المريض</th>
                        <th>رقم الملف</th>
                        <th>عدد المراجعات</th>
                        <th>آخر زيارة</th>
                        <th class="pe-4 text-end">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="animate__animated animate__fadeIn">
                            <td class="ps-4">
                                <div class="fw-bold"><?php echo $row['full_name_ar']; ?></div>
                                <div class="small text-muted"><?php echo $row['national_id']; ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?php echo $row['file_number']; ?></span></td>
                            <td>
                                <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-1"><?php echo $row['visit_count']; ?> زيارة</span>
                            </td>
                            <td>
                                <small class="text-muted"><?php echo $row['last_visit'] ? date('Y-m-d', strtotime($row['last_visit'])) : 'لا يوجد'; ?></small>
                            </td>
                            <td class="pe-4 text-end">
                                <a href="patient_file?id=<?php echo $row['patient_id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">ملف المراجعات</a>
                                <a href="book?id=<?php echo $row['patient_id']; ?>" class="btn btn-sm btn-success rounded-pill px-3">حجز جديد</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($result->num_rows == 0): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">لم يتم العثور على أي مريض بالفهرس</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
