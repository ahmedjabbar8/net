<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// Update current task
$conn->query("UPDATE users SET current_task = 'إدارة ملفات المرضى' WHERE user_id = " . intval($_SESSION['user_id']));

// --- Handle Deletion ---
if (isset($_GET['delete_patient']) && $_SESSION['role'] === 'admin') {
    $pid = intval($_GET['delete_patient']);
    $conn->query("DELETE FROM lab_requests WHERE patient_id = $pid");
    $conn->query("DELETE FROM radiology_requests WHERE patient_id = $pid");
    $conn->query("DELETE FROM prescriptions WHERE patient_id = $pid");
    $conn->query("DELETE FROM triage WHERE appointment_id IN (SELECT appointment_id FROM appointments WHERE patient_id = $pid)");
    $conn->query("DELETE FROM consultations WHERE patient_id = $pid");
    $conn->query("DELETE FROM invoices WHERE patient_id = $pid");
    $conn->query("DELETE FROM appointments WHERE patient_id = $pid");
    $conn->query("DELETE FROM patients WHERE patient_id = $pid");

    $_SESSION['msg'] = "تم حذف سجل المريض بنجاح";
    $_SESSION['msg_type'] = "danger";
    header("Location: patients");
    exit();
}

// --- Handle Cancellation ---
if (isset($_GET['cancel_appt'])) {
    $aid = intval($_GET['cancel_appt']);
    $conn->query("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = $aid AND status NOT IN ('completed', 'cancelled')");
    $_SESSION['msg'] = "تم إلغاء الحجز بنجاح";
    $_SESSION['msg_type'] = "warning";
    header("Location: patients");
    exit();
}

$search = $_GET['q'] ?? '';
$result = null;

if (!empty($search)) {
    $search_param = "%$search%";
    $sql = "SELECT p.*, (SELECT COUNT(*) FROM appointments WHERE patient_id = p.patient_id) as visit_count FROM patients p 
            WHERE (p.full_name_ar LIKE ? OR p.file_number LIKE ? OR p.national_id LIKE ?) 
            AND NOT EXISTS (
                SELECT 1 FROM appointments a 
                WHERE a.patient_id = p.patient_id 
                AND a.appointment_date = CURDATE() 
                AND a.status != 'cancelled'
            ) 
            ORDER BY p.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Hide default list (User Request: Only show on search)
    $result = null;
}

$confirmed_today = $conn->query("SELECT a.appointment_id, p.full_name_ar, p.file_number, d.department_name_ar, a.status, a.created_at 
                                FROM appointments a 
                                JOIN patients p ON a.patient_id = p.patient_id 
                                JOIN departments d ON a.department_id = d.department_id
                                WHERE DATE(a.appointment_date) = CURDATE() 
                                AND a.status != 'cancelled'
                                ORDER BY a.created_at DESC");

include 'header.php';
?>

<div class="patients-redesign py-4">
    <!-- Header Title Removed -->

    <!-- Section 1: Quick Access Center (التسجيل، البحث، الحجز) -->
    <div class="row g-4 mb-5">
        <div class="col-12 col-lg-4">
            <div class="premium-card h-100 p-4 shadow-sm border-0 rounded-4 bg-white hover-up"
                style="transition: transform 0.3s ease;">
                <div class="d-flex align-items-center mb-4">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary p-3 rounded-4 me-3">
                        <i class="fas fa-user-plus fa-2x"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0">التسجيل</h4>
                        <small class="text-muted">إضافة مريض جديد للنظام</small>
                    </div>
                </div>
                <p class="text-muted mb-4 small">قم بتسجيل البيانات الأساسية للمريض لفتح ملف طبي إلكتروني موحد.</p>
                <a href="add_patient" class="btn btn-primary w-100 rounded-pill py-3 fw-bold">تسجيل مريض جديد <i
                        class="fas fa-arrow-left ms-2"></i></a>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="premium-card h-100 p-4 shadow-sm border-0 rounded-4 bg-white hover-up"
                style="transition: transform 0.3s ease;">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-box bg-success bg-opacity-10 text-success p-3 rounded-4 me-3">
                        <i class="fas fa-search fa-2x"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0">البحث الذكي</h4>
                        <small class="text-muted">بالاسم أو رقم الملف</small>
                    </div>
                </div>
                <div class="position-relative mb-4">
                    <form action="" method="GET">
                        <input type="text" id="mainSearchInput" name="q"
                            class="form-control apple-form-control border-0 bg-light p-3 rounded-4"
                            placeholder="ابحث هنا..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"
                            class="btn btn-success position-absolute top-50 end-0 translate-middle-y me-2 rounded-circle shadow-sm"
                            style="width: 40px; height: 40px; border: 0;">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    <div id="mainSuggestions"
                        class="list-group position-absolute w-100 shadow-lg d-none mt-2 rounded-4 overflow-hidden"
                        style="z-index: 1000;"></div>
                </div>
                <p class="text-muted small">نتائج البحث تظهر تلقائياً أثناء الكتابة للوصول السريع.</p>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="premium-card h-100 p-4 shadow-sm border-0 rounded-4 bg-white hover-up"
                style="transition: transform 0.3s ease;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <a href="reservations" class="text-decoration-none text-dark d-flex align-items-center">
                        <div class="icon-box bg-dark bg-opacity-10 text-dark p-2 rounded-3 me-2">
                            <i class="fas fa-calendar-check fa-lg"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">الحجوزات</h5>
                            <small class="text-muted">إدارة كافة الحجوزات</small>
                        </div>
                    </a>
                    <button class="btn btn-sm btn-dark rounded-circle shadow-sm" onclick="startScannerRedesign()"
                        title="مسح باركود للحجز" style="width:32px;height:32px;"><i class="fas fa-qrcode"></i></button>
                </div>

                <!-- Quick Stats Dashboard -->
                <?php
                $s_today = $conn->query("SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = CURDATE() AND status != 'cancelled'")->fetch_row()[0];
                $s_tom = $conn->query("SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = CURDATE() + INTERVAL 1 DAY AND status != 'cancelled'")->fetch_row()[0];
                $s_week = $conn->query("SELECT COUNT(*) FROM appointments WHERE YEARWEEK(appointment_date, 1) = YEARWEEK(CURDATE(), 1) AND status != 'cancelled'")->fetch_row()[0];
                $s_month = $conn->query("SELECT COUNT(*) FROM appointments WHERE MONTH(appointment_date) = MONTH(CURDATE()) AND YEAR(appointment_date) = YEAR(CURDATE()) AND status != 'cancelled'")->fetch_row()[0];
                ?>
                <div class="row g-2 mb-3">
                     <div class="col-3">
                        <div class="p-2 rounded-3 bg-primary bg-opacity-10 text-center">
                             <div class="fw-bold text-primary" style="font-size: 0.9rem;"><?php echo $s_today; ?></div>
                             <div class="text-muted" style="font-size: 0.55rem;">اليوم</div>
                        </div>
                     </div>
                     <div class="col-3">
                        <div class="p-2 rounded-3 bg-warning bg-opacity-10 text-center">
                             <div class="fw-bold text-warning" style="font-size: 0.9rem;"><?php echo $s_tom; ?></div>
                             <div class="text-muted" style="font-size: 0.55rem;">غداً</div>
                        </div>
                     </div>
                     <div class="col-3">
                        <div class="p-2 rounded-3 bg-info bg-opacity-10 text-center">
                             <div class="fw-bold text-info" style="font-size: 0.9rem;"><?php echo $s_week; ?></div>
                             <div class="text-muted" style="font-size: 0.55rem;">الأسبوع</div>
                        </div>
                     </div>
                     <div class="col-3">
                        <div class="p-2 rounded-3 bg-success bg-opacity-10 text-center">
                             <div class="fw-bold text-success" style="font-size: 0.9rem;"><?php echo $s_month; ?></div>
                             <div class="text-muted" style="font-size: 0.55rem;">الشهر</div>
                        </div>
                     </div>
                </div>

                <!-- Bookings List -->
                <div class="overflow-auto" style="max-height: 250px;">
                    <?php
                    $today_apps = $conn->query("SELECT a.*, p.full_name_ar, p.file_number, d.department_name_ar 
                                              FROM appointments a 
                                              JOIN patients p ON a.patient_id = p.patient_id 
                                              JOIN departments d ON a.department_id = d.department_id 
                                              WHERE DATE(a.appointment_date) = CURDATE() AND a.status != 'cancelled'
                                              ORDER BY a.created_at DESC");
                    if ($today_apps->num_rows > 0):
                        while ($app = $today_apps->fetch_assoc()):
                            $status_color = match ($app['status']) {
                                'scheduled' => 'warning',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'secondary'
                            };
                            $status_text = match ($app['status']) {
                                'scheduled' => 'مجدول',
                                'completed' => 'مكتمل',
                                'cancelled' => 'ملغى',
                                'waiting_doctor' => 'انتظار طبيب',
                                'pending_triage' => 'انتظار فرز',
                                default => $app['status']
                            };
                            ?>
                            <div class="d-flex align-items-center p-2 mb-2 bg-light rounded-3 border border-light">
                                <div class="flex-shrink-0 text-center ms-2">
                                    <span class="badge bg-white text-dark border shadow-sm"
                                        style="min-width: 50px;"><?php echo date('H:i', strtotime($app['created_at'])); ?></span>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <a href="patient_file?id=<?php echo $app['patient_id']; ?>"
                                        class="text-decoration-none text-dark">
                                        <div class="fw-bold text-truncate small"><?php echo $app['full_name_ar']; ?></div>
                                    </a>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill"
                                            style="font-size:0.65rem"><?php echo $app['department_name_ar']; ?></span>
                                        <span
                                            class="badge bg-<?php echo $status_color; ?> bg-opacity-10 text-<?php echo $status_color; ?> rounded-pill"
                                            style="font-size:0.65rem"><?php echo $status_text; ?></span>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 me-2">
                                    <button onclick="cancelAppt(<?php echo $app['appointment_id']; ?>, this)"
                                        class="btn btn-sm btn-white text-danger border shadow-sm rounded-circle d-flex align-items-center justify-content-center hover-scale"
                                        style="width: 32px; height: 32px;" title="إلغاء الحجز">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; else: ?>
                        
                    <?php endif; ?>
                </div>

                <div class="mt-3 pt-3 border-top text-center">
                    <a href="reservations" class="text-decoration-none small fw-bold text-dark w-100 d-block">عرض الجدول
                        الكامل <i class="fas fa-arrow-left ms-1"></i></a>
                </div>
            </div>
        </div>

        <script>
            function cancelAppt(id, btn) {
                if (!confirm('هل أنت متأكد من حذف هذا الحجز؟')) return;

                // Visual feedback
                const item = btn.closest('.d-flex');
                item.style.opacity = '0.5';

                const formData = new FormData();
                formData.append('id', id);

                fetch('api_cancel_appointment.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            item.classList.add('animate__animated', 'animate__fadeOutRight');
                            setTimeout(() => item.remove(), 500);
                        } else {
                            alert('تعذر الحذف: ' + (data.message || 'خطأ غير معروف'));
                            item.style.opacity = '1';
                        }
                    })
                    .catch(e => {
                        alert('حدث خطأ في الاتصال');
                        item.style.opacity = '1';
                    });
            }
        </script>
    </div>
</div>

<!-- Section 2: Patients List (Pending Confirmation) -->
<?php if ($result && $result->num_rows > 0): ?>
    <div class="section-container mb-5">


        <div class="glass-card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-borderless table-hover align-middle mb-0">
                    <!-- Table Header Removed -->
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3"
                                                style="width: 45px; height: 45px;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <a href="patient_file?id=<?php echo $row['patient_id']; ?>"
                                                    class="fw-bold text-dark text-decoration-none stretched-link"><?php echo $row['full_name_ar']; ?></a>
                                                <div class="small text-muted">هوية: <?php echo $row['national_id'] ?: '---'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span
                                            class="badge bg-light text-dark border rounded-pill px-3"><?php echo $row['file_number']; ?></span>
                                    </td>
                                    <td><i class="fas fa-phone text-muted me-1"></i> <?php echo $row['phone1']; ?></td>
                                    <td class="text-center"><span
                                            class="badge bg-white text-primary border border-primary px-3 rounded-pill"><?php echo $row['visit_count']; ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <i class="fas fa-chevron-left text-muted"></i>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Section 3 Removed -->
</div>

<!-- Scanner Modal -->
<div class="modal fade" id="scannerModalRedesign" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-5 overflow-hidden">
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-qrcode me-2"></i> مسح الهوية الذكية</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    onclick="stopScannerRedesign()"></button>
            </div>
            <div class="modal-body p-0 bg-light">
                <div id="readerRedesign" style="width: 100%; min-height: 350px;"></div>
                <div class="p-4 text-center">
                    <p class="text-muted mb-0">يرجى توجيه كاميرا الجهاز نحو الباركود الموجود في بطاقة المريض المطبوعة.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    let html5QrCodeRedesign;

    function startScannerRedesign() {
        const modal = new bootstrap.Modal(document.getElementById('scannerModalRedesign'));
        modal.show();

        html5QrCodeRedesign = new Html5Qrcode("readerRedesign");
        const config = { fps: 15, qrbox: { width: 300, height: 200 } };

        html5QrCodeRedesign.start({ facingMode: "environment" }, config, (decodedText) => {
            stopScannerRedesign();
            bootstrap.Modal.getInstance(document.getElementById('scannerModalRedesign')).hide();
            processBarcodeRedesign(decodedText);
        });
    }

    function stopScannerRedesign() {
        if (html5QrCodeRedesign) {
            html5QrCodeRedesign.stop().then(() => html5QrCodeRedesign.clear()).catch(e => console.error(e));
        }
    }

    async function processBarcodeRedesign(code) {
        try {
            const resp = await fetch('api_barcode_book?barcode=' + encodeURIComponent(code));
            const data = await resp.json();
            if (data.success) {
                location.reload();
            } else {
                alert("خطأ: " + data.message);
            }
        } catch (e) {
            alert("فشل الاتصال بخادم الباركود");
        }
    }

    // Live Search Suggestions
    const searchInput = document.getElementById('mainSearchInput');
    const suggestionsBox = document.getElementById('mainSuggestions');

    searchInput.addEventListener('input', function () {
        const q = this.value.trim();
        if (q.length < 2) {
            suggestionsBox.classList.add('d-none');
            return;
        }

        fetch('api_patient_search?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                suggestionsBox.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(item => {
                        const row = document.createElement('div');
                        row.className = 'list-group-item p-3 border-0 d-flex justify-content-between align-items-center animate__animated animate__fadeIn';
                        row.style.background = 'white';
                        row.style.borderBottom = '1px solid #f0f0f0';
                        row.innerHTML = `
                            <a href="patient_file?id=${item.patient_id}" class="text-decoration-none text-dark">
                                <div class="fw-bold">${item.full_name_ar}</div>
                                <div class="small text-muted">ملف: ${item.file_number}</div>
                            </a>
                            <div class="d-flex gap-1">
                                <a href="patient_file?id=${item.patient_id}" class="btn btn-outline-dark btn-sm rounded-pill px-2" title="البروفايل"><i class="fas fa-id-card-alt"></i></a>
                                <a href="book?id=${item.patient_id}" class="btn btn-primary btn-sm rounded-pill px-2" title="حجز"><i class="fas fa-plus"></i></a>
                                <a href="edit_patient?id=${item.patient_id}" class="btn btn-light btn-sm rounded-pill px-2 border" title="تعديل"><i class="fas fa-pen"></i></a>
                            </div>
                        `;
                        suggestionsBox.appendChild(row);
                    });
                    suggestionsBox.classList.remove('d-none');
                } else {
                    suggestionsBox.innerHTML = '<div class="list-group-item p-3 text-muted small">لا توجد نتائج مطابقة</div>';
                    suggestionsBox.classList.remove('d-none');
                }
            });
    });

    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.classList.add('d-none');
        }
    });

</script>

<style>
    .hover-up:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1) !important;
    }

    .premium-card {
        border: 1px solid rgba(0, 0, 0, 0.02) !important;
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.7) !important;
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
    }

    .truncate-text {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .avatar-sm {
        flex-shrink: 0;
    }

    .status-indicator {
        width: 65px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<?php include 'footer.php'; ?>