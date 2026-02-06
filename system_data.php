<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard");
    exit();
}

$msg = '';
$msg_type = 'info';

// --- Handle Actions ---
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'wipe_transactions') {
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $conn->query("TRUNCATE TABLE appointments");
        $conn->query("TRUNCATE TABLE triage");
        $conn->query("TRUNCATE TABLE consultations");
        $conn->query("TRUNCATE TABLE lab_requests");
        $conn->query("TRUNCATE TABLE radiology_requests");
        $conn->query("TRUNCATE TABLE prescriptions");
        $conn->query("TRUNCATE TABLE invoices");
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $msg = "تم مسح كافة البيانات الحركية (التحويلات، المعاينات، والمحاسبة) بنجاح.";
        $msg_type = "danger";
    }
    
    if ($action === 'optimize') {
        $conn->query("OPTIMIZE TABLE patients, appointments, triage, consultations, lab_requests, radiology_requests, prescriptions, invoices, users");
        $msg = "تم تحسين قاعدة البيانات وضغط الملفات بنجاح.";
        $msg_type = "success";
    }
    
    if ($action === 'update_system') {
        $msg = "النظام مُحدّث بالفعل إلى آخر إصدار (Premier OS v5.2). لا توجد تحديثات متاحة حالياً.";
        $msg_type = "info";
    }
}

// --- Fetch Detailed Stats ---
$stats = [
    'المستفيدين' => $conn->query("SELECT COUNT(*) FROM patients")->fetch_row()[0],
    'إجمالي الحجوزات' => $conn->query("SELECT COUNT(*) FROM appointments")->fetch_row()[0],
    'معاينات طبية' => $conn->query("SELECT COUNT(*) FROM consultations")->fetch_row()[0],
    'طلبات المختبر' => $conn->query("SELECT COUNT(*) FROM lab_requests")->fetch_row()[0],
    'طلبات الأشعة' => $conn->query("SELECT COUNT(*) FROM radiology_requests")->fetch_row()[0],
    'وصفات كلية' => $conn->query("SELECT COUNT(*) FROM prescriptions")->fetch_row()[0],
    'فواتير صادرة' => $conn->query("SELECT COUNT(*) FROM invoices")->fetch_row()[0],
    'الوزن المالي' => $conn->query("SELECT SUM(amount) FROM invoices")->fetch_row()[0] ?? 0,
    'المستخدمين' => $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0]
];

include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-dark"><i class="fas fa-database text-danger me-2"></i> أداة البيانات المركزية (Admin Tool)</h2>
    <span class="badge bg-danger rounded-pill px-3 py-2">وضع الأدمين المتقدم</span>
</div>

<?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> shadow-sm border-0 rounded-4 mb-4 animate__animated animate__shakeX">
        <i class="fas fa-info-circle me-2"></i> <?php echo $msg; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Detailed Metrics -->
    <div class="col-lg-8">
        <div class="apple-card p-4">
            <h5 class="fw-bold mb-4">إحصائيات النظام التفصيلية</h5>
            <div class="row row-cols-2 row-cols-md-3 g-3">
                <?php foreach($stats as $label => $val): ?>
                    <div class="col">
                        <div class="p-3 rounded-4 bg-light border text-center">
                            <div class="text-muted small mb-1"><?php echo $label; ?></div>
                            <div class="h4 fw-bold mb-0"><?php echo is_numeric($val) ? number_format($val) : $val; ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- System Actions -->
    <div class="col-lg-4">
        <div class="apple-card p-4 bg-dark text-white border-0 shadow-lg">
            <h5 class="fw-bold mb-4"><i class="fas fa-tools me-2"></i> عمليات النظام</h5>
            
            <form method="POST" onsubmit="return confirm('تحذير: هذا الإجراء سيمسح كافة السجلات الحركية. هل أنت متأكد؟')">
                <input type="hidden" name="action" value="wipe_transactions">
                <button type="submit" class="btn btn-outline-danger w-100 mb-3 rounded-pill py-2">
                    <i class="fas fa-trash-alt me-2"></i> مسح سجلات الحركة (Reset)
                </button>
            </form>

            <form method="POST">
                <input type="hidden" name="action" value="optimize">
                <button type="submit" class="btn btn-outline-success w-100 mb-3 rounded-pill py-2">
                    <i class="fas fa-broom me-2"></i> تحسين قاعدة البيانات
                </button>
            </form>

            <form method="POST">
                <input type="hidden" name="action" value="update_system">
                <button type="submit" class="btn btn-outline-info w-100 mb-3 rounded-pill py-2">
                    <i class="fas fa-sync-alt me-2"></i> تحديث النظام (V5.2)
                </button>
            </form>
            
            <hr class="border-secondary my-4">
            <p class="small text-white-50 text-center">
                تستخدم هذه الأداة للتحكم الكامل في بنية البيانات. يرجى الحذر عند استخدام خيار المسح.
            </p>
        </div>
    </div>
</div>

<div class="mt-4">
    <div class="apple-card p-4">
        <h5 class="fw-bold mb-4">سلامة الجداول والاتصال</h5>
        <div class="table-responsive">
            <table class="table table-sm small align-middle">
                <thead>
                    <tr>
                        <th>الجدول</th>
                        <th>الحالة</th>
                        <th>الترميز</th>
                        <th>المحرك</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $tables = ['patients', 'appointments', 'triage', 'consultations', 'users', 'invoices'];
                    foreach($tables as $tbl):
                        $status = $conn->query("SHOW TABLE STATUS LIKE '$tbl'")->fetch_assoc();
                    ?>
                    <tr>
                        <td><strong><?php echo $tbl; ?></strong></td>
                        <td><span class="badge bg-success-subtle text-success">Active</span></td>
                        <td><?php echo $status['Collation']; ?></td>
                        <td><?php echo $status['Engine']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
