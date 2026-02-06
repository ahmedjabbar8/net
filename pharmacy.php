<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// 1. Fetch Dynamic Currency & Pharmacy Price
$settings_res = $conn->query("SELECT * FROM system_settings");
$sys_settings = [];
if ($settings_res) {
    while ($row = $settings_res->fetch_assoc()) {
        $sys_settings[$row['setting_key']] = $row['setting_value'];
    }
}
$currency = $sys_settings['currency_label'] ?? 'د.ع';

// 2. Fetch Pending Prescriptions (Wait for Payment or Dispense)
// In this hybrid mode, 'pending_payment' means it's waiting for the pharmacist to collect cash.
// Once paid/confirmed, it becomes 'dispensed'.
$presc_res = $conn->query("SELECT pr.*, p.full_name_ar as p_name, p.file_number, u.full_name_ar as doc_name 
                          FROM prescriptions pr 
                          JOIN patients p ON pr.patient_id = p.patient_id 
                          LEFT JOIN users u ON pr.doctor_id = u.user_id 
                          WHERE pr.status IN ('pending', 'pending_payment', 'pending_triage')
                          AND DATE(pr.created_at) = CURDATE()
                          ORDER BY pr.created_at ASC");

if (!$presc_res) {
    die("Error in prescription query: " . $conn->error);
}

// 3. Handle Dispense & Payment 
if (isset($_POST['dispense_now'])) {
    $id = intval($_POST['prescription_id']);
    $amount = floatval($_POST['price']);
    $pid = intval($_POST['patient_id']);
    $aid = intval($_POST['appointment_id']);

    // Update prescription status
    $conn->query("UPDATE prescriptions SET status = 'dispensed' WHERE prescription_id = $id");

    // Insert into invoices (Pharmacy Revenue)
    $conn->query("INSERT INTO invoices (appointment_id, patient_id, amount, status, created_at) VALUES ($aid, $pid, $amount, 'paid_pharmacy', NOW())");

    $_SESSION['msg'] = "تم استلام المبلغ وصرف العلاج بنجاح";
    $_SESSION['msg_type'] = "success";
    header("Location: pharmacy");
    exit();
}

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-success"><i class="fas fa-pills me-2"></i> قسم الصيدلية (Pharmacy & Cashier)</h2>
    <div class="badge bg-success-subtle text-success border border-success-subtle p-2 px-3 rounded-pill fw-bold">
        صيدلية متكاملة (صرف + بيع)
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="apple-card p-0 overflow-hidden shadow-sm">
            <div class="card-header bg-white border-0 py-3 h5 fw-bold mb-0">قائمة الوصفات الطبية بانتظار الصرف</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">المريض</th>
                            <th>الطبيب</th>
                            <th>العلاج المطلوب</th>
                            <th>السعر</th>
                            <th class="text-center">الإجراء المالي والصرف</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = $presc_res->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?php echo $r['p_name']; ?></div>
                                    <small class="text-muted"><?php echo $r['file_number']; ?></small>
                                </td>
                                <td><small class="text-muted">د. </small><?php echo $r['doc_name']; ?></td>
                                <td>
                                    <div
                                        class="p-2 bg-light rounded-3 small fw-bold text-dark border-start border-success border-3">
                                        <?php echo nl2br($r['medicine_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-success fs-6"><?php echo number_format($r['price']); ?>
                                        <?php echo $currency; ?></span>
                                </td>
                                <td class="text-center pe-4">
                                    <form method="POST" class="d-inline-block">
                                        <input type="hidden" name="prescription_id"
                                            value="<?php echo $r['prescription_id']; ?>">
                                        <input type="hidden" name="patient_id" value="<?php echo $r['patient_id']; ?>">
                                        <input type="hidden" name="appointment_id"
                                            value="<?php echo $r['appointment_id']; ?>">
                                        <input type="hidden" name="price" value="<?php echo $r['price']; ?>">
                                        <button type="submit" name="dispense_now"
                                            class="btn btn-success rounded-pill px-4 fw-bold">
                                            <i class="fas fa-money-bill-wave me-2"></i> استلام وصرف
                                        </button>
                                    </form>
                                    <a href="print_rx?id=<?php echo $r['prescription_id']; ?>"
                                        class="btn btn-light rounded-circle shadow-sm ms-1">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if ($presc_res->num_rows == 0): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">لا توجد وصفات طبية حالية للصرف</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>