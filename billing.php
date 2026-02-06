<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// Update current task
$conn->query("UPDATE users SET current_task = 'إدارة الصندوق المالي' WHERE user_id = " . intval($_SESSION['user_id']));

// --- Handle Multi-Payment & Discount ---
if (isset($_POST['process_payment'])) {
    $patient_id = intval($_POST['patient_id']);
    $appt_id = intval($_POST['appointment_id']);
    $discount = floatval($_POST['discount_amount'] ?? 0);
    $total_original = floatval($_POST['total_original']);
    $final_amount = $total_original - $discount;

    if (isset($_POST['pay_appt'])) {
        $conn->query("UPDATE appointments SET status = 'pending_triage' WHERE appointment_id = $appt_id");
    }

    if (!empty($_POST['pay_labs'])) {
        foreach ($_POST['pay_labs'] as $lid) {
            $conn->query("UPDATE lab_requests SET status = 'pending' WHERE request_id = " . intval($lid));
        }
    }

    if (!empty($_POST['pay_rads'])) {
        foreach ($_POST['pay_rads'] as $rid) {
            $conn->query("UPDATE radiology_requests SET status = 'pending' WHERE request_id = " . intval($rid));
        }
    }

    if (!empty($_POST['pay_prescs'])) {
        foreach ($_POST['pay_prescs'] as $pxid) {
            $conn->query("UPDATE prescriptions SET status = 'pending' WHERE prescription_id = " . intval($pxid));
        }
    }

    $conn->query("INSERT INTO invoices (appointment_id, patient_id, amount, status) VALUES ($appt_id, $patient_id, $final_amount, 'paid')");

    $_SESSION['msg'] = "تم استلام المبلغ بنجاح: " . number_format($final_amount);
    $_SESSION['msg_type'] = "success";
    header("Location: billing");
    exit();
}

include 'header.php';

// Fetch patients with pending payments
$sql_patients = "
    SELECT DISTINCT p.patient_id, p.full_name_ar, p.file_number, p.category, a.appointment_id, a.is_free 
    FROM patients p 
    JOIN appointments a ON p.patient_id = a.patient_id
    LEFT JOIN lab_requests l ON a.appointment_id = l.appointment_id AND l.status = 'pending_payment'
    LEFT JOIN radiology_requests r ON a.appointment_id = r.appointment_id AND r.status = 'pending_payment'
    LEFT JOIN prescriptions pr ON a.appointment_id = pr.appointment_id AND pr.status = 'pending_payment'
    WHERE DATE(a.appointment_date) = CURDATE()
       AND (
           (a.status = 'scheduled')
           OR (l.status = 'pending_payment')
           OR (r.status = 'pending_payment')
           OR (pr.status = 'pending_payment')
       )
    ORDER BY a.appointment_date DESC
";
$patients_res = $conn->query($sql_patients);

$prices_res = $conn->query("SELECT * FROM system_settings");
$prices = [];
while ($pr = $prices_res->fetch_assoc())
    $prices[$pr['setting_key']] = $pr['setting_value'];

$price_consult = (float) ($prices['price_consultation'] ?? 25000);
$currency = $prices['currency_label'] ?? 'د.ع';

$discount_rates = ['normal' => 0, 'senior' => 20, 'martyr' => 25, 'special' => 30];
$category_names = ['normal' => 'عادي', 'senior' => 'كبار السن', 'martyr' => 'عائلات الشهداء', 'special' => 'ذوي الاحتياجات الخاصة'];
?>

<div class="billing-redesign py-4">
    <div class="mb-5 text-center">
        <h1 class="fw-bold display-6 text-success"><i class="fas fa-coins me-2"></i> مركز المحاسبة الذكي</h1>
        <p class="text-muted">إدارة المدفوعات، الخصومات، وإصدار الفواتير الفورية</p>
    </div>

    <?php if ($patients_res->num_rows == 0): ?>
        <div class="text-center py-5 glass-card rounded-5 mb-5 mx-auto" style="max-width: 600px;">
            <i class="fas fa-check-double fa-4x text-success mb-3 opacity-50"></i>
            <h4 class="fw-bold">كافة الحسابات مصفاة بالكامل</h4>
            <p class="text-muted">لا يوجد مرضى بانتظار الدفع في الوقت الحالي.</p>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php while ($p = $patients_res->fetch_assoc()):
            $pid = $p['patient_id'];
            $aid = $p['appointment_id'];
            $is_free = (intval($p['is_free']) === 1);
            $items = [];
            $total = 0;

            $chk_appt = $conn->query("SELECT * FROM appointments WHERE appointment_id = $aid AND status = 'scheduled'")->fetch_assoc();
            if ($chk_appt) {
                $actual_price = $is_free ? 0 : $price_consult;
                $items[] = ['type' => ($is_free ? 'مراجعة مجانية' : 'كشف طبي للعيادة'), 'id' => $aid, 'price' => $actual_price, 'db_type' => 'appt'];
                $total += $actual_price;
            }

            $p_labs = $conn->query("SELECT * FROM lab_requests WHERE appointment_id = $aid AND status = 'pending_payment'");
            while ($l = $p_labs->fetch_assoc()) {
                $items[] = ['type' => 'مختبر: ' . $l['test_type'], 'id' => $l['request_id'], 'price' => $l['price'], 'db_type' => 'lab'];
                $total += $l['price'];
            }

            $p_rads = $conn->query("SELECT * FROM radiology_requests WHERE appointment_id = $aid AND status = 'pending_payment'");
            while ($r = $p_rads->fetch_assoc()) {
                $items[] = ['type' => 'أشعة: ' . $r['scan_type'], 'id' => $r['request_id'], 'price' => $r['price'], 'db_type' => 'rad'];
                $total += $r['price'];
            }

            $p_prescs = $conn->query("SELECT * FROM prescriptions WHERE appointment_id = $aid AND status = 'pending_payment'");
            while ($px = $p_prescs->fetch_assoc()) {
                $items[] = ['type' => 'صيدلية: ' . $px['medicine_name'], 'id' => $px['prescription_id'], 'price' => $px['price'], 'db_type' => 'px'];
                $total += $px['price'];
            }

            $p_category = $p['category'] ?? 'normal';
            $rate = $discount_rates[$p_category] ?? 0;
            $auto_discount = ($total * $rate) / 100;
            ?>
            <div class="col-12 col-xl-6">
                <div class="glass-card shadow-sm border-0 rounded-4 p-4 h-100 position-relative overflow-hidden bg-white">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h4 class="fw-bold text-dark mb-1"><?php echo $p['full_name_ar']; ?></h4>
                            <span class="badge bg-secondary bg-opacity-10 text-dark small rounded-pill px-3">ملف:
                                <?php echo $p['file_number']; ?></span>
                        </div>
                        <div class="text-end">
                            <div class="h3 fw-bold text-success mb-0"><?php echo number_format($total - $auto_discount); ?>
                                <small style="font-size: 0.6em;"><?php echo $currency; ?></small>
                            </div>
                            <small class="text-muted text-decoration-line-through">أصلي:
                                <?php echo number_format($total); ?></small>
                        </div>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="patient_id" value="<?php echo $pid; ?>">
                        <input type="hidden" name="appointment_id" value="<?php echo $aid; ?>">
                        <input type="hidden" name="total_original" value="<?php echo $total; ?>">

                        <div class="bg-light bg-opacity-50 rounded-4 p-3 mb-4">
                            <h6 class="small fw-bold text-muted text-uppercase mb-3"><i
                                    class="fas fa-file-invoice me-2"></i> تفاصيل الخدمات</h6>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($items as $it): ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small text-dark opacity-75"><?php echo $it['type']; ?></span>
                                        <span class="small fw-bold"><?php echo number_format($it['price']); ?></span>
                                        <?php if ($it['db_type'] == 'lab'): ?>
                                            <input type="hidden" name="pay_labs[]" value="<?php echo $it['id']; ?>">
                                        <?php elseif ($it['db_type'] == 'rad'): ?>
                                            <input type="hidden" name="pay_rads[]" value="<?php echo $it['id']; ?>">
                                        <?php elseif ($it['db_type'] == 'px'): ?>
                                            <input type="hidden" name="pay_prescs[]" value="<?php echo $it['id']; ?>">
                                        <?php elseif ($it['db_type'] == 'appt'): ?>
                                            <input type="hidden" name="pay_appt" value="1">
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="row g-3 align-items-center mb-4">
                            <div class="col-md-6">
                                <div class="p-2 border rounded-4 d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-circle me-3">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">فئة المريض</div>
                                        <div class="fw-bold"><?php echo $category_names[$p_category]; ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <label class="small text-muted mb-1">تعديل قيمة الخصم</label>
                                <div class="input-group">
                                    <input type="number" name="discount_amount"
                                        class="form-control apple-form-control text-center fw-bold text-danger border-danger"
                                        value="<?php echo $auto_discount; ?>">
                                    <span
                                        class="input-group-text bg-danger text-white border-danger small py-1 px-2"><?php echo $currency; ?></span>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="process_payment"
                            class="btn btn-success w-100 rounded-pill py-3 fw-bold shadow-sm">
                            <i class="fas fa-receipt me-2"></i> دفع وإصدار فاتورة نهائية
                        </button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.8) !important;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
        transition: transform 0.3s ease;
    }

    .glass-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08) !important;
    }

    .apple-form-control:focus {
        box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.1);
        border-color: #198754;
    }
</style>

<?php include 'footer.php'; ?>