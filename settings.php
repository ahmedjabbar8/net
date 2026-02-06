<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}
$perms = $_SESSION['permissions'] ?? [];
if ($_SESSION['role'] !== 'admin' && !in_array('settings', $perms)) {
    header("Location: dashboard");
    exit();
}

// --- 2. Fetch Currency & System Settings ---
$settings_res = $conn->query("SELECT * FROM system_settings");
$sys_settings = [];
while ($row = $settings_res->fetch_assoc()) {
    $sys_settings[$row['setting_key']] = $row['setting_value'];
}
$currency = $sys_settings['currency_label'] ?? 'د.ع';

// --- 3. Total Stats (The Global Inventory) ---
$total_patients = $conn->query("SELECT COUNT(*) FROM patients")->fetch_row()[0];
$total_revenue = $conn->query("SELECT SUM(amount) FROM invoices WHERE status = 'paid'")->fetch_row()[0] ?? 0;
$today_revenue = $conn->query("SELECT SUM(amount) FROM invoices WHERE status = 'paid' AND DATE(created_at) = CURDATE()")->fetch_row()[0] ?? 0;
$total_labs = $conn->query("SELECT COUNT(*) FROM lab_requests WHERE status = 'completed'")->fetch_row()[0];
$total_visits = $conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'completed'")->fetch_row()[0];

// --- 2. Module Toggle State (Mock persistence) ---
// In a real app, these would be in a settings table. We'll use simulation for now.
$discount_enabled = isset($_SESSION['discount_enabled']) ? $_SESSION['discount_enabled'] : true;

if (isset($_POST['toggle_discount'])) {
    $_SESSION['discount_enabled'] = !($_SESSION['discount_enabled'] ?? true);
    header("Location: settings");
    exit();
}

include 'header.php';
?>

<div class="container-fluid py-4">


    <!-- Admin Controls -->
    <div class="row g-4">
        <h5 class="fw-bold mb-4 ps-2 border-start border-4 border-primary"><i class="fas fa-cog me-2 text-primary"></i>
            إعدادات النظام</h5>

        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-3">

            <!-- 1. Discount System -->
            <div class="col">
                <form method="POST" class="h-100">
                    <button type="submit" name="toggle_discount"
                        class="btn w-100 h-100 p-4 border-0 rounded-4 shadow-sm bg-white d-flex flex-column align-items-center justify-content-center hover-scale">
                        <i
                            class="fas fa-percent fa-2x mb-3 text-<?php echo $discount_enabled ? 'success' : 'secondary'; ?>"></i>
                        <span class="fw-bold text-dark small">نظام التخفيض</span>
                        <span
                            class="badge bg-<?php echo $discount_enabled ? 'success' : 'secondary'; ?>-subtle text-<?php echo $discount_enabled ? 'success' : 'secondary'; ?> mt-2 rounded-pill px-2"
                            style="font-size: 0.6rem;">
                            <?php echo $discount_enabled ? 'نشط' : 'متوقف'; ?>
                        </span>
                    </button>
                </form>
            </div>

            <!-- 2. Price Control -->
            <div class="col">
                <a href="price_control" class="text-decoration-none">
                    <div
                        class="card h-100 border-0 rounded-4 shadow-sm p-4 d-flex flex-column align-items-center justify-content-center hover-scale">
                        <i class="fas fa-tags fa-2x mb-3 text-primary"></i>
                        <span class="fw-bold text-dark small">ادارة الأسعار</span>
                    </div>
                </a>
            </div>

            <!-- 3. Staff & Departments -->
            <div class="col">
                <a href="manage_staff.php" class="text-decoration-none">
                    <div
                        class="card h-100 border-0 rounded-4 shadow-sm p-4 d-flex flex-column align-items-center justify-content-center hover-scale">
                        <i class="fas fa-users-cog fa-2x mb-3 text-dark"></i>
                        <span class="fw-bold text-dark small">الأقسام والموظفين</span>
                    </div>
                </a>
            </div>

            <!-- 4. Registration Settings -->
            <div class="col">
                <a href="registration_settings" class="text-decoration-none">
                    <div
                        class="card h-100 border-0 rounded-4 shadow-sm p-4 d-flex flex-column align-items-center justify-content-center hover-scale">
                        <i class="fas fa-address-card fa-2x mb-3 text-info"></i>
                        <span class="fw-bold text-dark small">التسجيل</span>
                    </div>
                </a>
            </div>

            <!-- 5. Database Maintenance -->
            <div class="col">
                <button
                    class="btn w-100 h-100 p-4 border-0 rounded-4 shadow-sm bg-white d-flex flex-column align-items-center justify-content-center hover-scale">
                    <i class="fas fa-database fa-2x mb-3 text-secondary"></i>
                    <span class="fw-bold text-dark small">البيانات</span>
                </button>
            </div>

            <!-- 6. Lab Lock -->
            <div class="col">
                <button
                    class="btn w-100 h-100 p-4 border-0 rounded-4 shadow-sm bg-white d-flex flex-column align-items-center justify-content-center hover-scale">
                    <i class="fas fa-lock fa-2x mb-3 text-danger"></i>
                    <span class="fw-bold text-dark small">قفل المختبر</span>
                </button>
            </div>

            <!-- 7. Lab Maintenance -->
            <div class="col">
                <a href="lab_maintenance.php" class="text-decoration-none">
                    <div
                        class="card h-100 border-0 rounded-4 shadow-sm p-4 d-flex flex-column align-items-center justify-content-center hover-scale">
                        <i class="fas fa-vials fa-2x mb-3 text-warning"></i>
                        <span class="fw-bold text-dark small">صيانة المختبر</span>
                    </div>
                </a>
            </div>

            <!-- 8. Radiology Maintenance -->
            <div class="col">
                <button
                    class="btn w-100 h-100 p-4 border-0 rounded-4 shadow-sm bg-white d-flex flex-column align-items-center justify-content-center hover-scale">
                    <i class="fas fa-radiation fa-2x mb-3 text-info"></i>
                    <span class="fw-bold text-dark small">صيانة الاشعه</span>
                </button>
            </div>

        </div>
    </div>


</div>
</div>



<style>
    .bg-success-subtle {
        background-color: #d1e7dd;
    }

    .bg-info-subtle {
        background-color: #cff4fc;
    }

    .bg-primary-subtle {
        background-color: #cfe2ff;
    }

    .bg-warning-subtle {
        background-color: #fff3cd;
    }

    .border-dashed {
        border: 2px dashed rgba(0, 0, 0, 0.1) !important;
    }
</style>

<?php include 'footer.php'; ?>