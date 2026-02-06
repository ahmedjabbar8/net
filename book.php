<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$patient_id = $_GET['id'] ?? null;
if (!$patient_id) {
    header("Location: patients");
    exit();
}

// Fetch Patient Info
$p_sql = "SELECT * FROM patients WHERE patient_id = ?";
$p_stmt = $conn->prepare($p_sql);
$p_stmt->bind_param("i", $patient_id);
$p_stmt->execute();
$patient = $p_stmt->get_result()->fetch_assoc();

// Fetch Doctors and Departments
$doctors = $conn->query("SELECT * FROM users WHERE role = 'doctor'");
$depts = $conn->query("SELECT * FROM departments WHERE department_type = 'medical'");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_id = $_POST['doctor_id'];
    $dept_id = $_POST['dept_id'];
    $date = $_POST['date'];

    $sql = "INSERT INTO appointments (patient_id, doctor_id, department_id, appointment_date, status) VALUES (?, ?, ?, ?, 'scheduled')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $patient_id, $doctor_id, $dept_id, $date);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "تم حجز الموعد بنجاح. المريض الآن في قائمة انتظار المحاسبة.";
        $_SESSION['msg_type'] = "success";
        header("Location: billing");
        exit();
    }
}

include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0">حجز موعد جديد للمريض:
                    <?php echo $patient['full_name_ar']; ?>
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">العيادة / القسم</label>
                        <select name="dept_id" class="form-select" required>
                            <?php while ($d = $depts->fetch_assoc()): ?>
                                <option value="<?php echo $d['department_id']; ?>">
                                    <?php echo $d['department_name_ar']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الطبيب المعالج</label>
                        <select name="doctor_id" class="form-select" required>
                            <?php while ($doc = $doctors->fetch_assoc()): ?>
                                <option value="<?php echo $doc['user_id']; ?>">د.
                                    <?php echo $doc['full_name_ar']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاريخ الموعد</label>
                        <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>"
                            required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 py-2 mt-3">تأكيد الحجز وتحويل للمحاسبة</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>