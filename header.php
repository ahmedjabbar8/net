<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthPro Premier OS</title>
    <!-- Dependencies -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="apple_ui.css">
</head>

<body>
    <!-- Live Animated Background -->
    <div class="mesh-bg">
        <div class="blob1"></div>
        <div class="blob2"></div>
        <div class="blob3"></div>
    </div>

    <!-- Top Minimal Bar -->
    <div class="apple-nav no-print shadow-sm d-flex align-items-center px-3 justify-content-between">
        <div class="d-flex align-items-center">
            <!-- Distinctive Back Button -->
            <a href="javascript:history.back()"
                class="btn btn-light rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center me-3 hover-scale border-0"
                style="width: 40px; height: 40px; background: rgba(255,255,255,0.8); backdrop-filter: blur(10px);"
                title="عودة">
                <i class="fas fa-chevron-right text-primary"></i>
            </a>
            <a href="dashboard" class="text-decoration-none d-flex align-items-center">
                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2"
                    style="width:30px; height:30px;">
                    <i class="fas fa-plus text-white small"></i>
                </div>
                <span class="fw-bold text-dark" style="color: var(--text-color) !important;">Premier <span
                        class="text-primary">OS</span></span>
            </a>
        </div>



        <div class="d-none d-lg-flex flex-column text-center px-4">
            <?php
            // SSR Arabic Date & Time to prevent flickering
            function toArabicNum($str)
            {
                $e = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
                $a = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
                return str_replace($e, $a, $str);
            }

            $days = ['Sat' => 'السبت', 'Sun' => 'الأحد', 'Mon' => 'الاثنين', 'Tue' => 'الثلاثاء', 'Wed' => 'الأربعاء', 'Thu' => 'الخميس', 'Fri' => 'الجمعة'];
            $months = ['Jan' => 'يناير', 'Feb' => 'فبراير', 'Mar' => 'مارس', 'Apr' => 'أبريل', 'May' => 'مايو', 'Jun' => 'يونيو', 'Jul' => 'يوليو', 'Aug' => 'أغسطس', 'Sep' => 'سبتمبر', 'Oct' => 'أكتوبر', 'Nov' => 'نوفمبر', 'Dec' => 'ديسمبر'];

            $curr_day = $days[date('D')];
            $curr_month = $months[date('M')];
            $curr_day_num = toArabicNum(date('j'));
            $curr_date_str = $curr_day . '، ' . $curr_day_num . ' ' . $curr_month;

            $time_num = toArabicNum(date('h:i'));
            $ampm = date('A') == 'AM' ? 'ص' : 'م';
            $curr_time_str = $time_num . ' ' . $ampm;
            ?>
            <div class="fw-bold small mb-0" id="current-date"><?php echo $curr_date_str; ?></div>
            <div class="text-muted" style="font-size: 0.65rem;" id="current-time"><?php echo $curr_time_str; ?></div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- Theme Toggle -->
            <div onclick="toggleTheme()" style="cursor:pointer;" class="p-2" id="theme-btn">
                <i class="fas fa-moon"></i>
            </div>

            <!-- User Info (Minimal) -->
            <div class="text-end d-none d-md-block border-end pe-3 me-1">
                <div class="fw-bold small mb-0"><?php echo $_SESSION['full_name']; ?></div>
                <div class="badge bg-primary rounded-pill" style="font-size: 0.5rem;">
                    <?php echo strtoupper($_SESSION['role']); ?>
                </div>
            </div>

            <!-- Settings & Logout -->
            <?php if (($_SESSION['role'] ?? '') === 'admin' || in_array('settings', $_SESSION['permissions'] ?? [])): ?>
                <a href="settings" class="p-2 text-dark" style="color: var(--text-color) !important;" title="الإعدادات">
                    <i class="fas fa-cog"></i>
                </a>
            <?php endif; ?>
            <a href="logout" class="p-2 text-danger" title="خروج">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>

    <script>
        // Theme Engine
        function toggleTheme() {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme');
            const next = current === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            updateThemeIcon(next);
        }

        function updateThemeIcon(theme) {
            const btn = document.getElementById('theme-btn');
            btn.innerHTML = theme === 'dark' ? '<i class="fas fa-sun text-warning"></i>' : '<i class="fas fa-moon"></i>';
        }

        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            updateThemeIcon('dark');
        }

        // Live Clock (Synced with PHP Format)
        function toArabicDigits(str) {
            return str.replace(/\d/g, d => '٠١٢٣٤٥٦٧٨٩'[d]);
        }

        const daysAr = { 0: 'الأحد', 1: 'الاثنين', 2: 'الثلاثاء', 3: 'الأربعاء', 4: 'الخميس', 5: 'الجمعة', 6: 'السبت' };
        const monthsAr = { 0: 'يناير', 1: 'فبراير', 2: 'مارس', 3: 'أبريل', 4: 'مايو', 5: 'يونيو', 6: 'يوليو', 7: 'أغسطس', 8: 'سبتمبر', 9: 'أكتوبر', 10: 'نوفمبر', 11: 'ديسمبر' };

        setInterval(() => {
            const now = new Date();

            // Format Date: الخميس، ٥ فبراير
            const dayName = daysAr[now.getDay()];
            const dayNum = toArabicDigits(now.getDate().toString());
            const monthName = monthsAr[now.getMonth()];
            document.getElementById('current-date').innerText = `${dayName}، ${dayNum} ${monthName}`;

            // Format Time: ٠٦:٢٠ م
            let hours = now.getHours();
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'م' : 'ص';
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'
            const timeStr = toArabicDigits(`${hours}:${minutes}`);
            document.getElementById('current-time').innerText = `${timeStr} ${ampm}`;
        }, 1000);
    </script>

    <div class="container mt-4 pb-5">
        <?php if (isset($_SESSION['msg'])): ?>
            <div class="alert bg-white shadow-sm border-0 rounded-4 text-center mb-4">
                <span class="text-<?php echo $_SESSION['msg_type']; ?> fw-bold"><?php echo $_SESSION['msg']; ?></span>
                <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
            </div>
        <?php endif; ?>