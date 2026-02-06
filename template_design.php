<?php
include 'config.php';
// This is a showcase template for the requested Budgeted/Premium UI
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthPro Ultimate - Premium Template</title>
    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="modern_ui.css">
</head>

<body>

    <div class="container py-5">
        <!-- Header Section -->
        <div class="text-center mb-5">
            <h1 class="fw-bold display-4">لوحة تحكم <span style="color: #3498db;">المستقبل</span></h1>
            <p class="text-muted fs-5">تصميم متقدم يعتمد على الذكاء البصري والحركة التفاعلية</p>
        </div>

        <!-- Dynamic Grid -->
        <div class="row g-4 justify-content-center">

            <!-- Section 1: Doctor Dashboard -->
            <div class="col-md-4">
                <div class="dynamic-card text-center">
                    <i class="fas fa-user-md card-icon"></i>
                    <h3 class="card-title">بوابة الطبيب</h3>
                    <p class="card-desc">إدارة المعاينات، الوصفات الطبية، والتقارير بمظهر داكن عصري.</p>
                    <button class="hover-action-btn no-print">
                        <i class="fas fa-sign-in-alt me-2"></i> دخول العيادة
                    </button>
                </div>
            </div>

            <!-- Section 2: Laboratory -->
            <div class="col-md-4">
                <div class="dynamic-card text-center">
                    <i class="fas fa-flask card-icon"></i>
                    <h3 class="card-title">المختبر الذكي</h3>
                    <p class="card-desc">متابعة دقيقة للتحاليل المخبرية مع تواقيت زمنية آلية.</p>
                    <button class="hover-action-btn no-print">
                        <i class="fas fa-microscope me-2"></i> عرض الطلبات
                    </button>
                </div>
            </div>

            <!-- Section 3: Pharmacy -->
            <div class="col-md-4">
                <div class="dynamic-card text-center">
                    <i class="fas fa-prescription-bottle-medical card-icon"></i>
                    <h3 class="card-title">الصيدلية المركزية</h3>
                    <p class="card-desc">صرف الأدوية وإدارة المخزون بواجهة سريعة الاستجابة.</p>
                    <button class="hover-action-btn no-print">
                        <i class="fas fa-pills me-2"></i> صرف الأدوية
                    </button>
                </div>
            </div>

            <!-- Section 4: Radiology -->
            <div class="col-md-4">
                <div class="dynamic-card text-center">
                    <i class="fas fa-x-ray card-icon"></i>
                    <h3 class="card-title">قسم الأشعة</h3>
                    <p class="card-desc">تقارير الأشعة الرقمية والتشخيص البصري المتقدم.</p>
                    <button class="hover-action-btn no-print">
                        <i class="fas fa-eye me-2"></i> مراجعة الصور
                    </button>
                </div>
            </div>

            <!-- Section 5: Financials -->
            <div class="col-md-4">
                <div class="dynamic-card text-center">
                    <i class="fas fa-vault card-icon"></i>
                    <h3 class="card-title">الإدارة المالية</h3>
                    <p class="card-desc">نظام محاسبي موحد يدعم الخصومات والفواتير الذكية.</p>
                    <button class="hover-action-btn no-print">
                        <i class="fas fa-coins me-2"></i> عرض الحسابات
                    </button>
                </div>
            </div>

        </div>

        <!-- Footer Banner -->
        <div class="mt-5 text-center py-5 border-top border-secondary">
            <p class="mb-0 text-muted">HealthPro Ultimate V5.0 - جميع الحقوق محفوظة</p>
            <div class="d-flex justify-content-center gap-3 mt-3">
                <i class="fab fa-facebook text-secondary fs-4"></i>
                <i class="fab fa-twitter text-secondary fs-4"></i>
                <i class="fab fa-linkedin text-secondary fs-4"></i>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>