<?php
include 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $verified = false;

        // 1. Check Hash
        if (password_verify($pass, $row['password_hash'])) {
            $verified = true;
        }
        // 2. Check Legacy Plain Text (Migration)
        elseif ($pass == $row['password_hash']) {
            $verified = true;
            $newHash = password_hash($pass, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password_hash='$newHash' WHERE user_id={$row['user_id']}");
        }

        if ($verified) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['full_name'] = $row['full_name_ar'];

            // Decode permissions
            $perms = json_decode($row['permissions'] ?? '[]', true);
            if (!is_array($perms))
                $perms = [];
            $_SESSION['permissions'] = $perms;

            header("Location: index");
            exit();
        } else {
            $error = "كلمة المرور غير صحيحة";
        }
    } else {
        $error = "اسم المستخدم غير موجود";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthPro OS - Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="apple_ui.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            overflow: hidden;
        }

        .login-box {
            width: 400px;
            animation: fadeInScale 0.6s ease-out;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>

<body>
    <div class="login-box">
        <div class="apple-card shadow-lg py-5 px-4">
            <div class="mb-4">
                <i class="fas fa-hand-holding-medical text-primary fa-4x mb-3"></i>
                <h2 class="fw-bold">HealthPro <span class="text-primary">OS</span></h2>
                <p class="text-muted small">سجل الدخول للمتابعة</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert bg-danger-subtle text-danger border-0 small mb-4 rounded-4">
                    <i class="fas fa-exclamation-triangle me-1"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3 text-start">
                    <label class="form-label ms-2 small fw-bold">اسم المستخدم</label>
                    <input type="text" name="username" class="form-control form-control-apple" placeholder="Username"
                        required autofocus>
                </div>
                <div class="mb-4 text-start">
                    <label class="form-label ms-2 small fw-bold">كلمة المرور</label>
                    <input type="password" name="password" class="form-control form-control-apple"
                        placeholder="••••••••" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-apple py-3 fs-5">دخول النظام <i
                            class="fas fa-chevron-left ms-2"></i></button>
                </div>
            </form>

            <div class="mt-5 text-muted small" style="letter-spacing: 1px;">
                APPLE SYSTEM v5.0 SECURED
            </div>
        </div>
    </div>
</body>

</html>