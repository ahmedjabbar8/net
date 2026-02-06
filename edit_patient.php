<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header("Location: patients");
    exit();
}

$p = $conn->query("SELECT * FROM patients WHERE patient_id = $id")->fetch_assoc();
if (!$p) {
    header("Location: patients");
    exit();
}

if (isset($_POST['update'])) {
    $name = $conn->real_escape_string($_POST['full_name_ar']);
    $phone = $conn->real_escape_string($_POST['phone1']);
    $address = $conn->real_escape_string($_POST['address']);
    $nat_id = $conn->real_escape_string($_POST['national_id']);

    // Handle Photo Upload
    $photo_sql = "";
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $upload_dir_name = 'uploads/patients/';
        $target_dir = __DIR__ . '/' . $upload_dir_name;

        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_ext = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
        $file_name = "photo_" . time() . "." . $file_ext;
        $target_file = $target_dir . $file_name;
        $db_path = $upload_dir_name . $file_name;

        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            $photo_sql = ", photo = '$db_path'";
        }
    }

    // Update data
    $update_query = "UPDATE patients SET full_name_ar = '$name', phone1 = '$phone', address = '$address', national_id = '$nat_id' $photo_sql WHERE patient_id = $id";
    $conn->query($update_query);

    $_SESSION['msg'] = "تم تحديث بيانات المريض بنجاح";
    $_SESSION['msg_type'] = "success";
    header("Location: patients");
    exit();
}

include 'header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="apple-card p-4">
                <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                    <?php if (!empty($p['photo']) && file_exists(__DIR__ . '/' . $p['photo'])): ?>
                        <img src="<?php echo $p['photo']; ?>" class="rounded-circle shadow-sm border me-3"
                            style="width: 70px; height: 70px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border me-3"
                            style="width: 70px; height: 70px;">
                            <i class="fas fa-user text-muted fa-2x"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h4 class="fw-bold mb-0">تعديل بيانات المريض</h4>
                        <small class="text-muted">رقم الملف: <?php echo $p['file_number']; ?></small>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold small">الاسم الكامل (عربي)</label>
                            <input type="text" name="full_name_ar" class="form-control"
                                value="<?php echo $p['full_name_ar']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">رقم الهوية</label>
                            <input type="text" name="national_id" class="form-control"
                                value="<?php echo $p['national_id']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">رقم الهاتف</label>
                            <input type="text" name="phone1" class="form-control" value="<?php echo $p['phone1']; ?>"
                                required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small">العنوان</label>
                            <textarea name="address" class="form-control"
                                rows="2"><?php echo $p['address']; ?></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small">تحديث الصورة الشخصية</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-4 pt-3">
                        <button type="submit" name="update"
                            class="btn btn-primary fw-bold py-2 rounded-pill shadow-sm">حفظ التعديلات</button>
                        <a href="patients" class="btn btn-light fw-bold py-2 rounded-pill">إلغاء والعودة</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>