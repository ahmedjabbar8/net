<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}
$my_id = $_SESSION['user_id'];
$conn->query("UPDATE users SET current_task = 'متاح للاتصال', active_patient_name = NULL WHERE user_id = $my_id");

// Fetch active users for search
$users_res = $conn->query("SELECT u.*, d.department_name_ar 
                          FROM users u 
                          LEFT JOIN departments d ON u.department_id = d.department_id 
                          WHERE u.user_id != $my_id AND u.is_active = 1
                          ORDER BY u.full_name_ar ASC");

$all_users = [];
while ($u = $users_res->fetch_assoc()) {
    $u['search_term'] = $u['full_name_ar'] . ' ' . ($u['department_name_ar'] ?? '');
    $all_users[] = $u;
}

include 'header.php';
?>

<div class="connect-app container-fluid bg-white rounded-5 shadow-sm mt-3 overflow-hidden"
    style="height: calc(100vh - 120px); border: 1px solid #eee;">
    <div class="row h-100">
        <!-- Search & Contacts Sidebar -->
        <div class="col-md-3 border-end h-100 d-flex flex-column p-0 bg-light bg-opacity-50">
            <div class="p-4 bg-white border-bottom shadow-sm">
                <h4 class="fw-bold text-dark mb-3"><i class="fas fa-search me-2 text-primary"></i> دليل البحث</h4>
                <div class="position-relative">
                    <input type="text" id="directorySearch"
                        class="form-control form-control-lg border-0 bg-light rounded-pill ps-5"
                        placeholder="بحث بالاسم أو القسم..." onkeyup="filterDirectory()">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                </div>
            </div>

            <div class="flex-grow-1 overflow-auto p-3" id="directoryList">
                <!-- Javascript will populate -->
            </div>
        </div>

        <!-- Main Status & Instruction Area -->
        <div class="col-md-9 d-flex flex-column align-items-center justify-content-center text-center p-5">
            <div id="default-view" class="animate__animated animate__fadeIn">
                <div class="mb-4 d-inline-block p-5 bg-primary bg-opacity-5 rounded-circle">
                    <i class="fas fa-phone-alt fa-5x text-primary opacity-25"></i>
                </div>
                <h2 class="fw-bold">نظام الاتصال الفوري الموحد</h2>
                <p class="text-muted fs-5">يمكنك الاتصال بأي زميل مباشرة من هنا،<br>وسيظهر له الاتصال فوراً أينما كان في
                    النظام.</p>
                <div class="mt-4 p-3 bg-light rounded-4 border border-white">
                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i> الاتصال يعمل في كافة صفحات النظام
                        تلقائياً</small>
                </div>
            </div>

            <!-- Calling Indicator (Synchronized with Global) -->
            <div id="calling-view" class="d-none text-center">
                <div class="p-5 bg-white shadow-lg rounded-5 border" style="width: 380px;">
                    <div class="avatar-box bg-primary text-white mx-auto mb-4 d-flex align-items-center justify-content-center fw-bold"
                        id="calling-avatar" style="width: 100px; height: 100px; border-radius: 30px; font-size: 3rem;">?
                    </div>
                    <h2 id="calling-name" class="fw-bold mb-1">...</h2>
                    <div id="calling-status" class="text-primary fw-bold mb-4">جاري الاتصال...</div>
                    <button class="btn btn-danger btn-lg rounded-circle p-4 shadow-lg" onclick="terminateCallGlobal()">
                        <i class="fas fa-phone-slash fa-2x"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const directoryUsers = <?php echo json_encode($all_users); ?>;

    function renderDirectory(list) {
        const container = document.getElementById('directoryList');
        container.innerHTML = '';

        list.forEach(u => {
            const item = document.createElement('div');
            item.className = "d-flex align-items-center p-3 mb-2 rounded-4 bg-white border border-opacity-10 shadow-sm cursor-pointer contact-card";
            item.onclick = () => dialUser(u);
            item.innerHTML = `
            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center fw-bold text-primary shadow-sm" style="width:50px; height:50px; font-size: 1.2rem;">${u.full_name_ar.charAt(0)}</div>
            <div class="ms-3 flex-grow-1">
                <div class="fw-bold text-dark">${u.full_name_ar}</div>
                <div class="text-muted small">${u.department_name_ar || 'عام'}</div>
            </div>
            <div class="call-btn-box bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                <i class="fas fa-phone"></i>
            </div>
        `;
            container.appendChild(item);
        });
    }

    function filterDirectory() {
        const query = document.getElementById('directorySearch').value.toLowerCase();
        const filtered = directoryUsers.filter(u => u.search_term.toLowerCase().includes(query));
        renderDirectory(filtered);
    }

    async function dialUser(user) {
        // 1. Toggle UI
        document.getElementById('default-view').classList.add('d-none');
        document.getElementById('calling-view').classList.remove('d-none');
        document.getElementById('calling-name').innerText = user.full_name_ar;
        document.getElementById('calling-avatar').innerText = user.full_name_ar.charAt(0);

        // 2. Call using the global function from footer.php
        if (window.makeCall) {
            await window.makeCall(user.user_id, user.full_name_ar);
        }
    }

    // Sync UI with Global State
    setInterval(() => {
        if (window.gCall) {
            document.getElementById('default-view').classList.add('d-none');
            document.getElementById('calling-view').classList.remove('d-none');
        } else {
            document.getElementById('default-view').classList.remove('d-none');
            document.getElementById('calling-view').classList.add('d-none');
        }
    }, 1000);

    renderDirectory(directoryUsers);
</script>

<style>
    .contact-card {
        transition: 0.3s;
        border-left: 5px solid transparent !important;
    }

    .contact-card:hover {
        transform: translateX(-5px);
        border-left-color: #007aff !important;
        background: #f0f7ff !important;
    }

    .call-btn-box {
        opacity: 0.5;
        transition: 0.3s;
    }

    .contact-card:hover .call-btn-box {
        opacity: 1;
        transform: scale(1.1);
    }
</style>

<?php include 'footer.php'; ?>