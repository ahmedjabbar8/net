<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login");
    exit();
}

// 1. Fetch Currency & System Settings
$settings_res = $conn->query("SELECT * FROM system_settings");
$sys_settings = [];
while ($row = $settings_res->fetch_assoc()) {
    $sys_settings[$row['setting_key']] = $row['setting_value'];
}

// 2. Handle Update
if (isset($_POST['update_prices'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $clean_key = $conn->real_escape_string($key);
        $clean_value = $conn->real_escape_string($value);
        $conn->query("UPDATE system_settings SET setting_value = '$clean_value' WHERE setting_key = '$clean_key'");
    }
    $_SESSION['msg'] = "تم تحديث الأسعار وإعدادات العملة بنجاح";
    $_SESSION['msg_type'] = "success";
    header("Location: price_control");
    exit();
}

include 'header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="apple-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                    <h4 class="fw-bold mb-0 text-primary"><i class="fas fa-tags me-2"></i> مركز التحكم في الأسعار
                        والعملة</h4>
                    <a href="settings" class="btn btn-light btn-sm rounded-pill px-3">رجوع للإعدادات</a>
                </div>

                <form method="POST">
                    <div class="row g-4">
                        <!-- Currency Section -->
                        <div class="col-12">
                            <h6 class="fw-bold text-muted mb-3">إعدادات العملة</h6>
                            <div class="p-3 bg-light rounded-4 border">
                                <label class="form-label small fw-bold">رمز العملة (مثلاً: د.ع، IQD، $)</label>
                                <input type="text" name="settings[currency_label]" class="form-control"
                                    value="<?php echo htmlspecialchars($sys_settings['currency_label'] ?? 'د.ع'); ?>"
                                    required>
                            </div>
                        </div>

                        <!-- System Prices -->
                        <div class="col-12">
                            <h6 class="fw-bold text-muted mb-3">الأسعار الافتراضية للخدمات</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-4">
                                        <label class="form-label small fw-bold">سعر الكشفية (Consultation)</label>
                                        <div class="input-group">
                                            <input type="number" name="settings[price_consultation]"
                                                class="form-control"
                                                value="<?php echo $sys_settings['price_consultation'] ?? 25000; ?>"
                                                required>
                                            <span class="input-group-text bg-white">
                                                <?php echo $sys_settings['currency_label'] ?? 'د.ع'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-4">
                                        <label class="form-label small fw-bold">سعر التحليل الافتراضي (Lab)</label>
                                        <div class="input-group">
                                            <input type="number" name="settings[price_lab_default]" class="form-control"
                                                value="<?php echo $sys_settings['price_lab_default'] ?? 15000; ?>"
                                                required>
                                            <span class="input-group-text bg-white">
                                                <?php echo $sys_settings['currency_label'] ?? 'د.ع'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-4">
                                        <label class="form-label small fw-bold">سعر الأشعة الافتراضي (Radiology)</label>
                                        <div class="input-group">
                                            <input type="number" name="settings[price_rad_default]" class="form-control"
                                                value="<?php echo $sys_settings['price_rad_default'] ?? 30000; ?>"
                                                required>
                                            <span class="input-group-text bg-white">
                                                <?php echo $sys_settings['currency_label'] ?? 'د.ع'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-4">
                                        <label class="form-label small fw-bold">سعر العلاج/الوصفة (Prescription)</label>
                                        <div class="input-group">
                                            <input type="number" name="settings[price_rx_default]" class="form-control"
                                                value="<?php echo $sys_settings['price_rx_default'] ?? 5000; ?>"
                                                required>
                                            <span class="input-group-text bg-white">
                                                <?php echo $sys_settings['currency_label'] ?? 'د.ع'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mt-5">
                        <button type="submit" name="update_prices"
                            class="btn btn-primary fw-bold py-3 rounded-pill shadow-sm">
                            <i class="fas fa-check-circle me-2"></i> حفظ كافة الإعدادات المالية
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>