<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}
$role = $_SESSION['role'] ?? '';

// --- Live Activity Counts ---
$q_scheduled = $conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'scheduled' AND DATE(appointment_date) = CURDATE()")->fetch_row()[0];
$q_triage = $conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending_triage' AND DATE(appointment_date) = CURDATE()")->fetch_row()[0];
$q_doctor = $conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'waiting_doctor' AND DATE(appointment_date) = CURDATE()")->fetch_row()[0];
$q_labs_items = $conn->query("SELECT COUNT(*) FROM lab_requests WHERE status = 'pending' AND DATE(created_at) = CURDATE()")->fetch_row()[0];
$q_rads_items = $conn->query("SELECT COUNT(*) FROM radiology_requests WHERE status = 'pending' AND DATE(created_at) = CURDATE()")->fetch_row()[0];
$q_pharmacy = $conn->query("SELECT COUNT(*) FROM prescriptions WHERE status IN ('pending', 'pending_payment') AND DATE(created_at) = CURDATE()")->fetch_row()[0];
$q_done = $conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'completed' AND DATE(appointment_date) = CURDATE()")->fetch_row()[0];

include 'header.php';
?>

<div class="row pt-2 mb-4 text-center">
    <div class="col-12">
        <h2 class="fw-bold mb-0">HealthPro Intelligence</h2>
        <p class="text-muted small">نظام المسار الذكي لتتبع المرضى لحظياً</p>
    </div>
</div>

<!-- Tiny Neo-Tiles Grid (Clean Revert) -->
<?php
$perms = $_SESSION['permissions'] ?? [];
$isAdmin = ($role === 'admin');

function can_access($p) {
    global $perms, $isAdmin, $role;
    if ($isAdmin) return true; // Admin sees all
    if (in_array($p, $perms)) return true; // Check specific permission

    // Legacy Fallback for existing users
    $role_map = [
        'registration' => ['receptionist'],
        'triage' => ['nurse'],
        'doctor' => ['doctor'],
        'lab' => ['lab_tech', 'lab'],
        'radiology' => ['radiologist', 'rad'],
        'pharmacy' => ['pharmacist', 'pharmacy'],
        'invoices' => ['accountant'],
        'settings' => [] 
    ];

    if (isset($role_map[$p]) && in_array($role, $role_map[$p])) {
        return true;
    }
    
    return false;
}
?>

<!-- Tiny Neo-Tiles Grid (Permission Based) -->
<div class="row row-cols-3 row-cols-md-5 row-cols-lg-6 g-3 justify-content-center mb-5">

        <!-- Registration -->
        <?php if (can_access('registration')): ?>
            <div class="col">
                <a href="patients" class="neo-tile">
                    <i class="fas fa-user-plus text-primary"></i>
                    <span>التسجيل</span>
                </a>
            </div>
        <?php endif; ?>

        <!-- Billing -->
        <?php if (can_access('invoices')): ?>
            <div class="col">
                <a href="billing" class="neo-tile">
                    <?php if ($q_scheduled > 0): ?>
                        <span class="tile-count bg-danger text-white"><?php echo $q_scheduled; ?></span>
                    <?php endif; ?>
                    <i class="fas fa-cash-register text-success"></i>
                    <span>الحسابات</span>
                </a>
            </div>
        <?php endif; ?>

        <!-- Triage -->
        <?php if (can_access('triage')): ?>
            <div class="col">
                <a href="triage" class="neo-tile">
                    <?php if ($q_triage > 0): ?>
                        <span class="tile-count bg-warning text-dark"><?php echo $q_triage; ?></span>
                    <?php endif; ?>
                    <i class="fas fa-user-nurse text-danger"></i>
                    <span>الفحص الأولي</span>
                </a>
            </div>
        <?php endif; ?>

        <!-- Doctor -->
        <?php if (can_access('doctor')): ?>
            <div class="col">
                <a href="doctor_clinic" class="neo-tile">
                    <?php if ($q_doctor > 0): ?>
                        <span class="tile-count bg-info text-white"><?php echo $q_doctor; ?></span>
                    <?php endif; ?>
                    <i class="fas fa-stethoscope text-primary"></i>
                    <span>العيادة</span>
                </a>
            </div>
        <?php endif; ?>

        <!-- Lab -->
        <?php if (can_access('lab')): ?>
            <div class="col">
                <a href="lab" class="neo-tile">
                    <?php if ($q_labs_items > 0): ?>
                        <span class="tile-count bg-info text-white"><?php echo $q_labs_items; ?></span>
                    <?php endif; ?>
                    <i class="fas fa-flask text-info"></i>
                    <span>المختبر</span>
                </a>
            </div>
        <?php endif; ?>

        <!-- Radiology -->
        <?php if (can_access('radiology')): ?>
            <div class="col">
                <a href="radiology" class="neo-tile">
                    <?php if ($q_rads_items > 0): ?>
                        <span class="tile-count bg-secondary text-white"><?php echo $q_rads_items; ?></span>
                    <?php endif; ?>
                    <i class="fas fa-x-ray text-secondary"></i>
                    <span>الأشعة</span>
                </a>
            </div>
        <?php endif; ?>

        <!-- Pharmacy -->
        <?php if (can_access('pharmacy')): ?>
            <div class="col">
                <a href="pharmacy" class="neo-tile">
                    <?php if ($q_pharmacy > 0): ?>
                        <span class="tile-count bg-success text-white"><?php echo $q_pharmacy; ?></span>
                    <?php endif; ?>
                    <i class="fas fa-pills text-success"></i>
                    <span>الصيدلية</span>
                </a>
            </div>
        <?php endif; ?>

        <!-- Common Tools -->
        <?php if (can_access('registration') || can_access('doctor') || can_access('invoices')): ?>
            <div class="col">
                <a href="patient_index" class="neo-tile">
                    <i class="fas fa-address-book text-muted"></i>
                    <span>فهرس المرضى</span>
                </a>
            </div>
        <?php endif; ?>

        <div class="col">
            <a href="waiting_list" class="neo-tile">
                <i class="fas fa-desktop text-info"></i>
                <span>المراقب المباشر</span>
            </a>
        </div>

        <?php if ($isAdmin): ?>
            <div class="col">
                <a href="system_data" class="neo-tile border border-danger border-opacity-25">
                    <i class="fas fa-database text-danger"></i>
                    <span>أداة البيانات</span>
                </a>
            </div>
        <?php endif; ?>

        <div class="col">
            <a href="connect" class="neo-tile">
                <i class="fas fa-satellite-dish text-primary"></i>
                <span>مركز الاتصال</span>
            </a>
        </div>

        <!-- Settings -->
        <?php if (can_access('settings')): ?>
            <div class="col">
                <a href="settings" class="neo-tile">
                    <i class="fas fa-cog text-muted"></i>
                    <span>الإعدادات</span>
                </a>
            </div>
        <?php endif; ?>

    </div>

    <!-- Real-time Workflow Dashboard -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="apple-card p-4 shadow-sm bg-white rounded-4 overflow-hidden">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">مسار المريض الحالي</h5>
                    <span class="badge bg-primary-subtle text-primary">تحديث مباشر</span>
                </div>
                <div class="row g-4 mb-4">
                    <!-- Step 1 -->
                    <div class="col-md-2">
                        <div class="p-3 rounded-4 border border-light text-center h-100">
                            <div class="h3 fw-bold text-success"><?php echo $q_scheduled; ?></div>
                            <div class="text-muted small">المحاسبة</div>
                        </div>
                    </div>
                    <!-- Step 2 -->
                    <div class="col-md-2">
                        <div class="p-3 rounded-4 border border-light text-center h-100">
                            <div class="h3 fw-bold text-warning"><?php echo $q_triage; ?></div>
                            <div class="text-muted small">الفحص الأولي</div>
                        </div>
                    </div>
                    <!-- Step 3 -->
                    <div class="col-md-2">
                        <div class="p-3 rounded-4 border border-light text-center h-100">
                            <div class="h3 fw-bold text-primary"><?php echo $q_doctor; ?></div>
                            <div class="text-muted small">الطبيب</div>
                        </div>
                    </div>
                    <!-- Step 4 -->
                    <div class="col-md-2">
                        <div class="p-3 rounded-4 border border-light text-center h-100">
                            <div class="h3 fw-bold text-info"><?php echo $q_labs_items + $q_rads_items; ?></div>
                            <div class="text-muted small">الفحوصات</div>
                        </div>
                    </div>
                    <!-- Step 5 -->
                    <div class="col-md-2">
                        <div class="p-3 rounded-4 border border-light text-center h-100">
                            <div class="h3 fw-bold text-success"><?php echo $q_pharmacy; ?></div>
                            <div class="text-muted small">الصيدلية</div>
                        </div>
                    </div>
                    <!-- Step 6 -->
                    <div class="col-md-2">
                        <div class="p-3 rounded-4 border border-light text-center h-100">
                            <div class="h3 fw-bold text-dark"><?php echo $q_done; ?></div>
                            <div class="text-muted small">تم الخروج</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .text-purple {
            color: #5856d6;
        }
    </style>

    <?php include 'footer.php'; ?>