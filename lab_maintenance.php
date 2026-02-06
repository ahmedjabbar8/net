<?php
include 'config.php';
include 'header.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login';</script>";
    exit();
}

// Handle Actions
$msg = "";
$msg_type = "";

// ADD
if (isset($_POST['add_test'])) {
    $name = $conn->real_escape_string($_POST['test_name']);
    $price = (float) $_POST['test_price'];
    $min = isset($_POST['min_value']) ? (float) $_POST['min_value'] : null;
    $max = isset($_POST['max_value']) ? (float) $_POST['max_value'] : null;
    $unit = $conn->real_escape_string($_POST['unit'] ?? '');
    $gender = $conn->real_escape_string($_POST['gender'] ?? 'both');
    $reference = $conn->real_escape_string($_POST['reference_range'] ?? '');
    $profile = $conn->real_escape_string($_POST['profile_components'] ?? '');
    $active = 1;

    // Check duplicate
    $check = $conn->query("SELECT * FROM lab_tests WHERE test_name = '$name'");
    if ($check->num_rows > 0) {
        $msg = "هذا الفحص موجود مسبقاً!";
        $msg_type = "danger";
    } else {
        $sql = "INSERT INTO lab_tests (test_name, price, min_value, max_value, unit, gender, reference_range, profile_components, is_active) VALUES ('$name', $price, ".($min === null ? 'NULL' : $min).", ".($max === null ? 'NULL' : $max).", '$unit', '$gender', '$reference', '$profile', $active)";
        if ($conn->query($sql)) {
            $msg = "تم إضافة الفحص بنجاح";
            $msg_type = "success";
        } else {
            $msg = "خطأ في الإضافة: " . $conn->error;
            $msg_type = "danger";
        }
    }
}

// EDIT
if (isset($_POST['edit_test'])) {
    $id = (int) $_POST['test_id'];
    $name = $conn->real_escape_string($_POST['test_name']);
    $price = (float) $_POST['test_price'];
    $min = isset($_POST['min_value']) ? (float) $_POST['min_value'] : null;
    $max = isset($_POST['max_value']) ? (float) $_POST['max_value'] : null;
    $unit = $conn->real_escape_string($_POST['unit'] ?? '');
    $gender = $conn->real_escape_string($_POST['gender'] ?? 'both');
    $reference = $conn->real_escape_string($_POST['reference_range'] ?? '');
    $profile = $conn->real_escape_string($_POST['profile_components'] ?? '');
    $sql = "UPDATE lab_tests SET test_name='$name', price=$price, min_value=".($min === null ? 'NULL' : $min).", max_value=".($max === null ? 'NULL' : $max).", unit='$unit', gender='$gender', reference_range='$reference', profile_components='$profile' WHERE test_id=$id";
    if ($conn->query($sql)) {
        $msg = "تم تعديل الفحص بنجاح";
        $msg_type = "success";
    } else {
        $msg = "خطأ في التعديل: " . $conn->error;
        $msg_type = "danger";
    }
}

// تفعيل/تعطيل
if (isset($_POST['toggle_active'])) {
    $id = (int) $_POST['test_id'];
    $active = (int) $_POST['active'];
    $sql = "UPDATE lab_tests SET is_active=$active WHERE test_id=$id";
    if ($conn->query($sql)) {
        $msg = "تم تحديث حالة التفعيل";
        $msg_type = "success";
    } else {
        $msg = "خطأ في التحديث: " . $conn->error;
        $msg_type = "danger";
    }
}

// DELETE
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $conn->query("DELETE FROM lab_tests WHERE test_id=$id");
    echo "<script>window.location.href='lab_maintenance.php';</script>";
    exit();
}

?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-vials text-warning"></i> صيانة وادارة فحوصات المختبر</h2>
        <button class="btn btn-primary fw-bold rounded-pill px-4 py-2 d-flex align-items-center gap-2 shadow-sm" style="background: #1877f2; border: none; font-size: 1.1rem;" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i>
            إضافة فحص جديد
        </button>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>اسم الفحص</th>
                            <th>السعر (د.ع)</th>
                            <th>إجراءات</th>
                            <th>تفعيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $res = $conn->query("SELECT * FROM lab_tests ORDER BY test_name ASC");
                        $i = 1;
                        while ($row = $res->fetch_assoc()):
                            ?>
                            <tr>
                                <td>
                                    <?php echo $i++; ?>
                                </td>
                                <td class="fw-bold">
                                    <?php echo $row['test_name']; ?>
                                </td>
                                <td>
                                    <?php echo number_format($row['price']); ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal"
                                        data-bs-target="#editModal<?php echo $row['test_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $row['test_id']; ?>" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('هل أنت متأكد من حذف هذا الفحص؟');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="test_id" value="<?php echo $row['test_id']; ?>">
                                        <input type="hidden" name="toggle_active" value="1">
                                        <input type="hidden" name="active" value="<?php echo ($row['is_active'] ?? 1) ? 0 : 1; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo ($row['is_active'] ?? 1) ? 'btn-success' : 'btn-secondary'; ?>">
                                            <?php echo ($row['is_active'] ?? 1) ? 'مفعل' : 'غير مفعل'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $row['test_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content premium-card border-0 shadow-lg rounded-4 p-3" style="background: linear-gradient(135deg, #f8fafc 80%, #e0e7ef 100%);">
                                        <form method="POST">
                                            <div class="modal-header border-0">
                                                <h5 class="modal-title fw-bold text-primary"><i class="fas fa-flask-vial me-2"></i> تعديل فحص</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="test_id" value="<?php echo $row['test_id']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold text-secondary">اسم الفحص</label>
                                                    <input type="text" name="test_name" class="form-control rounded-pill border-2" value="<?php echo $row['test_name']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold text-secondary">السعر</label>
                                                    <input type="number" name="test_price" class="form-control rounded-pill border-2" value="<?php echo $row['price']; ?>" required>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label fw-semibold text-secondary">الحد الأدنى</label>
                                                        <input type="number" step="any" name="min_value" class="form-control rounded-pill border-2" value="<?php echo $row['min_value']; ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label fw-semibold text-secondary">الحد الأقصى</label>
                                                        <input type="number" step="any" name="max_value" class="form-control rounded-pill border-2" value="<?php echo $row['max_value']; ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label fw-semibold text-secondary">الوحدة</label>
                                                        <input type="text" name="unit" class="form-control rounded-pill border-2" value="<?php echo $row['unit']; ?>">
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold text-secondary">الجنس</label>
                                                    <select name="gender" class="form-select rounded-pill border-2">
                                                        <option value="both" <?php if($row['gender']==='both') echo 'selected'; ?>>كلا الجنسين</option>
                                                        <option value="male" <?php if($row['gender']==='male') echo 'selected'; ?>>ذكر</option>
                                                        <option value="female" <?php if($row['gender']==='female') echo 'selected'; ?>>أنثى</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold text-secondary">نطاق المرجعية</label>
                                                    <input type="text" name="reference_range" class="form-control rounded-pill border-2" value="<?php echo $row['reference_range']; ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold text-secondary">مكونات البروفايل</label>
                                                    <div class="d-flex flex-wrap gap-2 mb-2">
                                                        <?php
                                                        $components = array_filter(array_map('trim', explode(',', $row['profile_components'] ?? '')));
                                                        foreach ($components as $comp): ?>
                                                            <span class="badge bg-info text-dark px-3 py-2 rounded-pill"> <?php echo htmlspecialchars($comp); ?> </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <div class="input-group mb-2">
                                                        <input type="text" id="new_component_<?php echo $row['test_id']; ?>" class="form-control rounded-pill border-2" placeholder="أضف مكون جديد">
                                                        <button type="button" class="btn btn-outline-primary rounded-pill px-3" onclick="addComponent_<?php echo $row['test_id']; ?>()">إضافة</button>
                                                    </div>
                                                    <textarea name="profile_components" class="form-control border-2 rounded-3" rows="2" id="profile_components_<?php echo $row['test_id']; ?>"><?php echo $row['profile_components']; ?></textarea>
                                                </div>
                                                <script>
                                                function addComponent_<?php echo $row['test_id']; ?>() {
                                                    var input = document.getElementById('new_component_<?php echo $row['test_id']; ?>');
                                                    var textarea = document.getElementById('profile_components_<?php echo $row['test_id']; ?>');
                                                    var val = input.value.trim();
                                                    if (val) {
                                                        if (textarea.value.trim()) {
                                                            textarea.value += ',' + val;
                                                        } else {
                                                            textarea.value = val;
                                                        }
                                                        input.value = '';
                                                    }
                                                }
                                                </script>
                                            </div>
                                            <div class="modal-footer border-0 d-flex justify-content-end gap-2">
                                                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                                                <button type="submit" name="edit_test" class="btn btn-primary rounded-pill px-4 fw-bold">حفظ التعديلات</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content premium-card border-0 shadow-lg rounded-4 p-3" style="background: linear-gradient(135deg, #f8fafc 80%, #e0e7ef 100%);">
            <form method="POST">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-primary"><i class="fas fa-flask-vial me-2"></i> إضافة فحص جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">اسم الفحص</label>
                        <input type="text" name="test_name" class="form-control rounded-pill border-2" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">السعر الافتراضي</label>
                        <input type="number" name="test_price" class="form-control rounded-pill border-2" value="15000" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary">الحد الأدنى</label>
                            <input type="number" step="any" name="min_value" class="form-control rounded-pill border-2">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary">الحد الأقصى</label>
                            <input type="number" step="any" name="max_value" class="form-control rounded-pill border-2">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary">الوحدة</label>
                            <input type="text" name="unit" class="form-control rounded-pill border-2">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">الجنس</label>
                        <select name="gender" class="form-select rounded-pill border-2">
                            <option value="both">كلا الجنسين</option>
                            <option value="male">ذكر</option>
                            <option value="female">أنثى</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">نطاق المرجعية</label>
                        <input type="text" name="reference_range" class="form-control rounded-pill border-2">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">مكونات البروفايل</label>
                        <textarea name="profile_components" class="form-control border-2 rounded-3" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" name="add_test" class="btn btn-primary rounded-pill px-4 fw-bold">إضافة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>