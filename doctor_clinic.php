<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$doctor_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['role'] == 'admin');

if ($is_admin) {
    $sql = "SELECT a.*, p.full_name_ar as p_name, p.file_number, p.gender, t.blood_pressure, t.temperature, t.pulse 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.patient_id 
            LEFT JOIN triage t ON a.appointment_id = t.appointment_id
            WHERE a.status = 'waiting_doctor' AND DATE(a.appointment_date) = CURDATE()";
} else {
    $sql = "SELECT a.*, p.full_name_ar as p_name, p.file_number, p.gender, t.blood_pressure, t.temperature, t.pulse 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.patient_id 
            LEFT JOIN triage t ON a.appointment_id = t.appointment_id
            WHERE a.status = 'waiting_doctor' AND a.doctor_id = $doctor_id AND DATE(a.appointment_date) = CURDATE()";
}

$waiting = $conn->query($sql);

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold"><i class="fas fa-stethoscope text-primary"></i> العيادة - قائمة الانتظار</h2>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-primary text-white">
                    <tr>
                        <th class="ps-4">#</th>
                        <th>المريض</th>
                        <th>العلامات الحيوية</th>
                        <th>وصول</th>
                        <th class="pe-4">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
                    while ($r = $waiting->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4">
                                <?php echo $i++; ?>
                            </td>
                            <td>
                                <strong>
                                    <?php echo $r['p_name']; ?>
                                </strong><br>
                                <small class="text-muted">
                                    <?php echo $r['file_number']; ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($r['blood_pressure']): ?>
                                    <span class="badge bg-light text-dark border">BP:
                                        <?php echo $r['blood_pressure']; ?>
                                    </span>
                                    <span class="badge bg-light text-dark border">T:
                                        <?php echo $r['temperature']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">بانتظار الفرز</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo date('H:i', strtotime($r['created_at'])); ?>
                            </td>
                            <td class="pe-4">
                                <a href="consultation?id=<?php echo $r['appointment_id']; ?>"
                                    class="btn btn-primary btn-sm rounded-pill px-3">بدء المعاينة</a>
                            </td>
                        </tr>
                    <?php endwhile;
                    if ($waiting->num_rows == 0)
                        echo '<tr><td colspan="5" class="text-center py-5">لا يوجد مرضى بانتظار الكشف</td></tr>'; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<?php include 'footer.php'; ?>
```