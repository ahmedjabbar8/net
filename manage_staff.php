<?php
include 'config.php';

// --- Security Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login");
    exit();
}

// --- Self-Healing Schema ---
// 1. Ensure departments table exists with correct charset and column names
$conn->query("CREATE TABLE IF NOT EXISTS departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name_ar VARCHAR(100) NOT NULL,
    department_name_en VARCHAR(100) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// 2. Fix existing charset if table existed previously
$conn->query("ALTER TABLE departments CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// 3. Clean up empty/corrupt departments (Disabled to prevent confusion)
// $conn->query("DELETE FROM departments WHERE department_name = '' OR department_name IS NULL");

// 4. Ensure users table has necessary columns (compatible check)
$col = $conn->query("SHOW COLUMNS FROM users LIKE 'permissions'");
if ($col->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN permissions TEXT");
}
$col = $conn->query("SHOW COLUMNS FROM users LIKE 'department_id'");
if ($col->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN department_id INT DEFAULT 0");
}

// 5. Ensure department_id exists before querying
// (Already covered by Step 4)

// --- Handle Form Submissions ---
if (isset($_POST['add_dept'])) {
    $dept_name = $conn->real_escape_string($_POST['dept_name']);
    if (!empty($dept_name)) {
        $conn->query("INSERT INTO departments (department_name_ar) VALUES ('$dept_name')");
        $_SESSION['msg'] = "تم إضافة القسم بنجاح";
        $_SESSION['msg_type'] = "success";
    }
    header("Location: manage_staff.php");
    exit();
}

if (isset($_GET['del_dept'])) {
    $id = intval($_GET['del_dept']);
    $conn->query("DELETE FROM departments WHERE department_id = $id");
    $_SESSION['msg'] = "تم حذف القسم";
    $_SESSION['msg_type'] = "warning";
    header("Location: manage_staff.php");
    exit();
}

if (isset($_POST['save_employee'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $role = $conn->real_escape_string($_POST['role']);
    $dept_id = intval($_POST['department_id']);
    $perms = isset($_POST['permissions']) ? json_encode($_POST['permissions']) : '[]';
    $perms = $conn->real_escape_string($perms);
    $email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
    $full_name_en = isset($_POST['full_name_en']) ? $conn->real_escape_string($_POST['full_name_en']) : '';

    if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
        $uid = intval($_POST['user_id']);
        $pass_sql = "";
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $pass_sql = ", password_hash='$hashed'";
        }
        $sql = "UPDATE users SET username='$username', full_name_ar='$full_name', full_name_en='$full_name_en', email='$email', role='$role', department_id=$dept_id, permissions='$perms' $pass_sql WHERE user_id=$uid";
    } else {
        $check = $conn->query("SELECT user_id FROM users WHERE username='$username'");
        if ($check->num_rows > 0) {
            $_SESSION['msg'] = "اسم المستخدم موجود مسبقاً";
            $_SESSION['msg_type'] = "danger";
            header("Location: manage_staff.php");
            exit();
        }
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password_hash, full_name_ar, full_name_en, email, role, department_id, permissions) VALUES ('$username', '$hashed', '$full_name', '$full_name_en', '$email', '$role', $dept_id, '$perms')";
    }
    if ($conn->query($sql)) {
        $_SESSION['msg'] = "تم حفظ بيانات الموظف بنجاح";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "حدث خطأ: " . $conn->error;
        $_SESSION['msg_type'] = "danger";
    }
    header("Location: manage_staff.php");
    exit();
}

if (isset($_GET['del_user'])) {
    $id = intval($_GET['del_user']);
    if ($id != $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE user_id = $id");
        $_SESSION['msg'] = "تم حذف الموظف";
        $_SESSION['msg_type'] = "warning";
    }
    header("Location: manage_staff.php");
    exit();
}

include 'header.php';
?>

<div class="container-fluid py-4">

    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-4 shadow-sm">
        <div class="d-flex align-items-center">
            <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                <i class="fas fa-users-cog fa-2x text-primary"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-1 text-dark">إدارة الأقسام والموظفين</h4>
                <p class="text-muted small mb-0">تحكم كامل في هيكلية العمل وصلاحيات المستخدمين</p>
            </div>
        </div>
        <div>
            <a href="dashboard" class="btn btn-outline-secondary rounded-pill fw-bold">
                <i class="fas fa-arrow-left me-2"></i> رجوع
            </a>
        </div>
    </div>

    <div class="row g-4">

        <!-- Sidebar / Stats -->
        <div class="col-lg-3">
            <div class="row g-3">
                <div class="col-12">
                    <div
                        class="card border-0 shadow-sm rounded-4 bg-primary text-white overflow-hidden position-relative">
                        <div class="card-body p-4 position-relative z-1">
                            <h2 class="display-5 fw-bold mb-0">
                                <?php echo $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0]; ?>
                            </h2>
                            <p class="mb-0 opacity-75 fw-bold">إجمالي الموظفين</p>
                        </div>
                        <i class="fas fa-users position-absolute end-0 bottom-0 fa-6x opacity-25 me-n3 mb-n3"></i>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 bg-white">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-muted mb-3">الأقسام الحالية</h6>
                            <ul class="list-group list-group-flush">
                                <?php
                                $depts = $conn->query("SELECT * FROM departments");
                                if ($depts->num_rows > 0):
                                    while ($d = $depts->fetch_assoc()):
                                        ?>
                                        <li
                                            class="list-group-item px-0 d-flex justify-content-between align-items-center border-bottom-dashed">
                                            <span class="fw-bold"
                                                style="color: #000 !important; font-weight: 900 !important; font-size: 1.1rem;"><i
                                                    class="fas fa-layer-group text-secondary me-2"></i><?php echo $d['department_name_ar']; ?></span>
                                            <a href="?del_dept=<?php echo $d['department_id']; ?>"
                                                class="text-danger small opacity-50 hover-opacity-100"
                                                onclick="return confirm('حذف القسم؟')"><i class="fas fa-trash"></i></a>
                                        </li>
                                    <?php endwhile;
                                else: ?>
                                    <li class="text-muted small text-center py-2">لا توجد أقسام مضافة</li>
                                <?php endif; ?>
                            </ul>
                            <button class="btn btn-light w-100 rounded-pill mt-3 fw-bold text-primary"
                                data-bs-toggle="modal" data-bs-target="#addDeptModal">
                                <i class="fas fa-plus me-2"></i> إضافة قسم
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employees List -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-id-card-alt text-primary me-2"></i> لائحة
                        الموظفين</h5>
                    <button class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" onclick="openEmployeeModal()">
                        <i class="fas fa-plus me-2"></i> موظف جديد
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary small fw-bold text-uppercase">الموظف</th>
                                    <th class="py-3 text-secondary small fw-bold text-uppercase">بيانات الدخول</th>
                                    <th class="py-3 text-secondary small fw-bold text-uppercase">الصلاحية / القسم</th>
                                    <th class="pe-4 py-3 text-end text-secondary small fw-bold text-uppercase">إجراءات
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $users = $conn->query("SELECT u.*, d.department_name_ar AS department_name FROM users u LEFT JOIN departments d ON u.department_id = d.department_id ORDER BY u.user_id DESC");
                                while ($u = $users->fetch_assoc()):
                                    $perms = json_decode($u['permissions'] ?? '[]', true);
                                    $permCount = is_array($perms) ? count($perms) : 0;
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm"
                                                    style="width: 45px; height: 45px; font-size: 1.2rem;">
                                                    <i class="fas fa-user-tie"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold text-dark"><?php echo $u['full_name_ar']; ?>
                                                    </h6>
                                                    <span
                                                        class="text-muted small"><?php echo $u['role'] == 'admin' ? 'مدير عام' : ($u['department_name'] ?? 'بدون قسم'); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold text-dark small"><i
                                                        class="fas fa-user mb-1 me-1 text-muted"></i>
                                                    <?php echo $u['username']; ?></span>
                                                <span class="text-muted small" style="font-size: 0.75rem;">******</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                                                <i class="fas fa-briefcase me-1 text-muted"></i> <?php echo $u['role']; ?>
                                            </span>
                                            <span
                                                class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill ms-1"
                                                style="font-size: 0.7rem;" title="عدد الصلاحيات">
                                                <i class="fas fa-lock-open me-1"></i> <?php echo $permCount; ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button
                                                class="btn btn-sm btn-light border shadow-sm rounded-circle me-1 text-primary hover-scale"
                                                onclick='openEmployeeModal(<?php echo json_encode($u); ?>)' title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                                <a href="?del_user=<?php echo $u['user_id']; ?>"
                                                    class="btn btn-sm btn-light border shadow-sm rounded-circle text-danger hover-scale"
                                                    onclick="return confirm('حذف هذا الموظف نهائياً؟')" title="حذف">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Department Modal -->
<div class="modal fade" id="addDeptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-body p-4 text-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex p-3 mb-3">
                    <i class="fas fa-layer-group fa-2x"></i>
                </div>
                <h5 class="fw-bold mb-3">إضافة قسم جديد</h5>
                <form method="POST">
                    <input type="text" name="dept_name"
                        class="form-control rounded-pill text-center mb-3 bg-light border-0 py-2 fw-bold"
                        placeholder="اسم القسم" required>
                    <button type="submit" name="add_dept" class="btn btn-primary w-100 rounded-pill fw-bold">حفظ
                        القسم</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Employee Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-user-cog me-2 text-primary"></i> <span
                        id="empModalTitle">إضافة موظف جديد</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="empUserId">
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <!-- Personal Info -->
                        <div class="col-md-6">
                            <h6 class="fw-bold text-muted small mb-3 border-bottom pb-2">البيانات الشخصية</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">الاسم الكامل</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0 rounded-start-3"><i
                                            class="fas fa-user"></i></span>
                                    <input type="text" name="full_name" id="empName"
                                        class="form-control bg-light border-0 rounded-end-3" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">القسم الوظيفي</label>
                                <select name="department_id" id="empDept"
                                    class="form-select bg-light border-0 rounded-3">
                                    <option value="0">-- اختر القسم --</option>
                                    <?php
                                    $d_res = $conn->query("SELECT * FROM departments");
                                    while ($row = $d_res->fetch_assoc()) {
                                        echo "<option value='{$row['department_id']}'>{$row['department_name_ar']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">الدور (Role)</label>
                                <select name="role" id="empRole" class="form-select bg-light border-0 rounded-3"
                                    required>
                                    <option value="doctor">طبيب (Doctor)</option>
                                    <option value="nurse">ممرض/ترياج (Nurse)</option>
                                    <option value="reception">استقبال (Reception)</option>
                                    <option value="pharmacy">صيدلي (Pharmacy)</option>
                                    <option value="lab">مختبر (Lab)</option>
                                    <option value="rad">أشعة (Radiology)</option>
                                    <option value="admin">مدير نظام (Admin)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Login Info -->
                        <div class="col-md-6">
                            <h6 class="fw-bold text-muted small mb-3 border-bottom pb-2">بيانات الدخول</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">اسم المستخدم</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0 rounded-start-3"><i
                                            class="fas fa-at"></i></span>
                                    <input type="text" name="username" id="empUsername"
                                        class="form-control bg-light border-0 rounded-end-3" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">كلمة المرور</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0 rounded-start-3"><i
                                            class="fas fa-lock"></i></span>
                                    <input type="password" name="password" id="empPassword"
                                        class="form-control bg-light border-0 rounded-end-3"
                                        placeholder="اتركه فارغاً للتعديل">
                                </div>
                            </div>
                        </div>

                        <!-- Permissions -->
                        <div class="col-12">
                            <h6 class="fw-bold text-muted small mb-3 border-bottom pb-2">صلاحيات الوصول المسموحة</h6>
                            <div class="row row-cols-2 row-cols-md-4 g-3">
                                <?php
                                $modules = [
                                    'registration' => ['icon' => 'fa-address-card', 'label' => 'التسجيل والحجوزات', 'color' => 'info'],
                                    'triage' => ['icon' => 'fa-stethoscope', 'label' => 'الفحص الأولي', 'color' => 'danger'],
                                    'doctor' => ['icon' => 'fa-user-md', 'label' => 'عيادة الطبيب', 'color' => 'primary'],
                                    'lab' => ['icon' => 'fa-flask', 'label' => 'المختبر', 'color' => 'warning'],
                                    'radiology' => ['icon' => 'fa-x-ray', 'label' => 'الأشعة', 'color' => 'dark'],
                                    'pharmacy' => ['icon' => 'fa-pills', 'label' => 'الصيدلية', 'color' => 'success'],
                                    'invoices' => ['icon' => 'fa-file-invoice-dollar', 'label' => 'الحسابات', 'color' => 'secondary'],
                                    'settings' => ['icon' => 'fa-cogs', 'label' => 'الإعدادات', 'color' => 'dark']
                                ];
                                foreach ($modules as $key => $mod):
                                    ?>
                                    <div class="col">
                                        <label
                                            class="cursor-pointer card h-100 border bg-light shadow-none permission-card position-relative overflow-hidden"
                                            style="cursor: pointer;">
                                            <input class="form-check-input position-absolute top-0 end-0 m-2 perm-check"
                                                type="checkbox" name="permissions[]" value="<?php echo $key; ?>"
                                                id="perm_<?php echo $key; ?>">
                                            <div
                                                class="card-body text-center p-3 d-flex flex-column align-items-center justify-content-center">
                                                <i
                                                    class="fas <?php echo $mod['icon']; ?> fa-lg text-<?php echo $mod['color']; ?> mb-2"></i>
                                                <span class="small fw-bold text-dark"><?php echo $mod['label']; ?></span>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold"
                        data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" name="save_employee"
                        class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .permission-card input:checked+.card-body {
        background-color: #f0f8ff;
        /* AliceBlue */
        border-color: var(--bs-primary) !important;
    }

    .permission-card input:checked+.card-body i {
        transform: scale(1.1);
    }

    .hover-scale {
        transition: transform 0.2s;
    }

    .hover-scale:hover {
        transform: scale(1.1);
    }
</style>

<!-- الوضع الداكن تم تعطيله بناءً على طلب المستخدم -->
<?php include 'footer.php'; ?>
<script>
    // Visual selection script
    document.querySelectorAll('.permission-card').forEach(card => {
        card.addEventListener('click', function (e) {
            if (e.target.tagName !== 'INPUT') {
                const chk = this.querySelector('input');
                chk.checked = !chk.checked;
            }
        });
    });

    function openEmployeeModal(data = null) {
        const modal = new bootstrap.Modal(document.getElementById('employeeModal'));

        // Reset form
        document.getElementById('empUserId').value = '';
        document.getElementById('empName').value = '';
        document.getElementById('empUsername').value = '';
        document.getElementById('empPassword').value = '';
        document.getElementById('empRole').value = 'reception';
        document.getElementById('empDept').value = '0';
        document.getElementById('empModalTitle').innerText = 'إضافة موظف جديد';

        // Uncheck all permissions
        document.querySelectorAll('.perm-check').forEach(c => c.checked = false);

        if (data) {
            // Edit Mode
            document.getElementById('empModalTitle').innerText = 'تعديل بيانات الموظف';
            document.getElementById('empUserId').value = data.user_id;
            document.getElementById('empName').value = data.full_name_ar;
            document.getElementById('empUsername').value = data.username;
            document.getElementById('empRole').value = data.role;
            document.getElementById('empDept').value = data.department_id || 0;

            // Set Permissions
            if (data.permissions) {
                try {
                    const perms = JSON.parse(data.permissions);
                    perms.forEach(p => {
                        const check = document.getElementById('perm_' + p);
                        if (check) check.checked = true;
                    });
                } catch (e) { }
            }
        }
        modal.show();
    }

    // Auto-select permissions based on Role
    document.getElementById('empRole').addEventListener('change', function () {
        const role = this.value;
        const map = {
            'doctor': ['doctor'],
            'nurse': ['triage'],
            'reception': ['registration'],
            'pharmacy': ['pharmacy'],
            'lab': ['lab'],
            'rad': ['radiology'],
            'admin': ['settings', 'invoices', 'registration', 'triage', 'doctor', 'lab', 'radiology', 'pharmacy']
        };

        // Uncheck all first
        document.querySelectorAll('.perm-check').forEach(c => c.checked = false);

        // Check mapped
        if (map[role]) {
            map[role].forEach(p => {
                const el = document.getElementById('perm_' + p);
                if (el) el.checked = true;
            });
        }
    });
</script>