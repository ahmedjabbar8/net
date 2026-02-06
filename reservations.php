<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// Filter Logic
$filter_date = $_GET['date'] ?? ''; // YYYY-MM-DD or empty
$search_q = $_GET['q'] ?? '';

$sql = "SELECT a.*, p.full_name_ar, p.file_number, p.phone1, d.department_name_ar, u.full_name_ar as doctor_name 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.patient_id 
        JOIN departments d ON a.department_id = d.department_id 
        LEFT JOIN users u ON a.doctor_id = u.user_id 
        WHERE a.status != 'cancelled_hidden'"; // Show everything except hard-deleted

if ($filter_date == 'today') {
    $sql .= " AND DATE(a.appointment_date) = CURDATE()";
} elseif ($filter_date == 'tomorrow') {
    $sql .= " AND DATE(a.appointment_date) = CURDATE() + INTERVAL 1 DAY";
} elseif ($filter_date == 'week') {
    $sql .= " AND YEARWEEK(a.appointment_date, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter_date == 'month') {
    $sql .= " AND MONTH(a.appointment_date) = MONTH(CURDATE()) AND YEAR(a.appointment_date) = YEAR(CURDATE())";
} elseif ($filter_date == 'upcoming') {
    $sql .= " AND a.appointment_date >= CURDATE()";
} elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_date)) {
    // Specific Date
    $sql .= " AND DATE(a.appointment_date) = '$filter_date'";
}
if (!empty($search_q)) {
    $sql .= " AND (p.full_name_ar LIKE '%$search_q%' OR p.file_number LIKE '%$search_q%')";
}

$sql .= " ORDER BY a.appointment_date DESC, a.created_at DESC LIMIT 100";

$result = $conn->query($sql);

// Fetch available dates for dropdown
$dates_sql = "SELECT DISTINCT DATE(appointment_date) as date_val FROM appointments ORDER BY date_val DESC LIMIT 30";
$dates_result = $conn->query($dates_sql);
$available_dates = [];
if ($dates_result) {
    while ($d = $dates_result->fetch_assoc()) {
        $available_dates[] = $d['date_val'];
    }
}

include 'header.php';
?>


<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary"><i class="fas fa-calendar-alt me-2"></i> إدارة الحجوزات</h3>
            <p class="text-muted small mb-0">لوحة تحكم شاملة لكافة المواعيد والحجوزات</p>
        </div>
    </div>

    <!-- Controls -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-3">
                <!-- Search Input -->
                <div class="col-md-5">
                    <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden bg-light border">
                        <span class="input-group-text bg-transparent border-0 pe-2"><i
                                class="fas fa-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control bg-transparent border-0 ps-0"
                            placeholder="بحث باسم المريض أو رقم الملف..."
                            value="<?php echo htmlspecialchars($search_q); ?>">
                    </div>
                </div>

                <!-- Date Input (Custom Trigger Design) -->
                <div class="col-md-5">
                    <div class="position-relative bg-white shadow-sm rounded-pill border d-flex align-items-center px-4 py-2"
                        style="height: 50px;">
                        <i class="fas fa-calendar-alt text-primary"></i>
                        <span class="fw-bold text-dark flex-grow-1 text-center users-select-none">
                            <?php
                            if (!empty($filter_date)) {
                                echo date('Y/m/d', strtotime($filter_date));
                            } else {
                                echo "تحديد التاريخ";
                            }
                            ?>
                        </span>
                        <i class="fas fa-chevron-down text-muted small"></i>

                        <!-- Invisible actual date input covering the whole component -->
                        <input type="date" name="date" class="position-absolute top-0 start-0 w-100 h-100"
                            style="opacity: 0; cursor: pointer; z-index: 10;"
                            value="<?php echo htmlspecialchars($filter_date); ?>" onchange="this.form.submit()"
                            onclick="try{this.showPicker()}catch(e){}">
                    </div>
                </div>

                <!-- Filter Button -->
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill shadow-sm fw-bold h-100">
                        تصفية
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Appointments Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="bg-light text-muted">
                    <tr>
                        <th class="py-3">المريض</th>
                        <th class="py-3">القسم / الطبيب</th>
                        <th class="py-3">الموعد</th>
                        <th class="py-3">الحالة</th>
                        <th class="py-3">تحكم</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()):
                            $status_color = match ($row['status']) {
                                'scheduled' => 'warning',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'secondary'
                            };
                            $status_text = match ($row['status']) {
                                'scheduled' => 'مجدول',
                                'completed' => 'مكتمل',
                                'cancelled' => 'ملغى',
                                'waiting_doctor' => 'انتظار طبيب',
                                'pending_triage' => 'انتظار فرز',
                                default => $row['status']
                            };
                            ?>
                            <tr id="appt-<?php echo $row['appointment_id']; ?>">
                                <td class="text-start ps-4">
                                    <div class="fw-bold">
                                        <?php echo $row['full_name_ar']; ?>
                                    </div>
                                    <div class="small text-muted">ملف:
                                        <?php echo $row['file_number']; ?> | ت:
                                        <?php echo $row['phone1']; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary">
                                        <?php echo $row['department_name_ar']; ?>
                                    </div>
                                    <div class="small text-muted">
                                        <?php echo $row['doctor_name'] ? 'د. ' . $row['doctor_name'] : '---'; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold">
                                        <?php echo date('Y-m-d', strtotime($row['appointment_date'])); ?>
                                    </div>
                                    <div class="small text-muted">
                                        <?php echo date('h:i A', strtotime($row['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <span
                                        class="badge bg-<?php echo $status_color; ?> bg-opacity-10 text-<?php echo $status_color; ?> px-3 py-2 rounded-pill">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <button onclick="editApptFull(<?php echo $row['appointment_id']; ?>)"
                                            class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="fas fa-pen me-1"></i> تعديل
                                        </button>
                                        <?php if ($row['status'] != 'completed' && $row['status'] != 'cancelled'): ?>
                                            <button onclick="cancelApptFull(<?php echo $row['appointment_id']; ?>)"
                                                class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                <i class="fas fa-times me-1"></i> حذف
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-5 text-muted">لا توجد حجوزات مطابقة للبحث</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editApptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">تعديل الحجز</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editApptForm">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="small text-muted mb-1">تاريخ الموعد</label>
                        <input type="date" name="date" id="edit_date" class="form-control rounded-3">
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted mb-1">الحالة الحالية</label>
                        <select name="status" id="edit_status" class="form-select rounded-3">
                            <option value="scheduled">مجدول</option>
                            <option value="waiting_doctor">انتظار طبيب</option>
                            <option value="pending_triage">انتظار فرز</option>
                            <option value="completed">مكتمل</option>
                            <option value="cancelled">ملغى</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" onclick="saveApptChanges()" class="btn btn-primary rounded-pill px-4">حفظ
                    التغييرات</button>
            </div>
        </div>
    </div>
</div>

<script>
    function editApptFull(id) {
        fetch('api_get_appointment.php?id=' + id)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('edit_id').value = data.data.appointment_id;
                    document.getElementById('edit_date').value = data.data.appointment_date.split(' ')[0];
                    document.getElementById('edit_status').value = data.data.status;
                    new bootstrap.Modal(document.getElementById('editApptModal')).show();
                }
            });
    }

    function saveApptChanges() {
        const form = document.getElementById('editApptForm');
        const formData = new FormData(form);

        fetch('api_update_appointment.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('خطأ أثناء الحفظ');
                }
            });
    }

    function cancelApptFull(id) {
        if (!confirm('هل أنت متأكد من حذف وإلغاء هذا الحجز نهائياً؟')) return;

        const row = document.getElementById('appt-' + id);
        row.style.opacity = '0.5';

        const formData = new FormData();
        formData.append('id', id);

        fetch('api_cancel_appointment.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Update UI to show Cancelled
                    location.reload(); // Reload to reflect status change or remove
                } else {
                    alert('خطأ: ' + data.message);
                    row.style.opacity = '1';
                }
            })
            .catch(e => {
                alert('خطأ في الاتصال');
                row.style.opacity = '1';
            });
    }
</script>

<?php include 'footer.php'; ?>