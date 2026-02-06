<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// Toggle Patient Classification
if (isset($_POST['toggle_classification'])) {
    $_SESSION['patient_classification_enabled'] = !($_SESSION['patient_classification_enabled'] ?? true);
    header("Location: registration_settings");
    exit();
}

$classification_enabled = $_SESSION['patient_classification_enabled'] ?? true;

include 'header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-info text-white py-3 border-0">
                    <h4 class="mb-0 fw-bold"><i class="fas fa-address-card me-2"></i> إعدادات التسجيل (Registration
                        Settings)</h4>
                </div>
                <div class="card-body p-4 bg-white">
                    <h5 class="fw-bold mb-4 text-primary border-bottom pb-2">التحكم في حقول التسجيل</h5>

                    <!-- Patient Classification Toggle -->
                    <div class="d-flex justify-content-between align-items-center p-3 rounded-4 bg-light mb-3">
                        <div>
                            <h6 class="fw-bold mb-1"><i class="fas fa-layer-group me-2 text-primary"></i> تصنيف المريض
                                (Patient Classification)</h6>
                            <p class="small text-muted mb-0">إظهار حقل تصنيف المريض (VIP، موظف، عادي) للحصول على خصومات
                                تلقائية.</p>
                        </div>
                        <form method="POST">
                            <button type="submit" name="toggle_classification"
                                class="btn btn-<?php echo $classification_enabled ? 'success' : 'secondary'; ?> rounded-pill px-4 shadow-sm fw-bold">
                                <?php echo $classification_enabled ? 'مفعـل (Enabled)' : 'معطـل (Disabled)'; ?>
                            </button>
                        </form>
                    </div>

                    <div class="mt-4 text-center">
                        <a href="settings" class="btn btn-outline-primary rounded-pill px-5 fw-bold"><i
                                class="fas fa-arrow-right me-2"></i> العودة للإعدادات</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>