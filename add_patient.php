<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

// --- SELF HEALING: Add category column if not exists ---
$conn->query("ALTER TABLE patients ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT 'normal'");
$conn->query("ALTER TABLE patients ADD COLUMN IF NOT EXISTS full_name_en VARCHAR(255) DEFAULT ''");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $file_num = "P-" . rand(10000, 99999);
    $name = $_POST['full_name'];
    $name_en = $_POST['full_name_en'];
    $nat_id = $_POST['national_id'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $category = $_POST['category'] ?? 'normal';

    // Handle Photo Upload
    $photo_path = null;
    $upload_dir_name = 'uploads/patients/';
    $target_dir = __DIR__ . '/' . $upload_dir_name;

    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $file_ext = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
        $file_name = "photo_" . time() . "." . $file_ext;
        $target_file = $target_dir . $file_name;
        $db_path = $upload_dir_name . $file_name;

        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            $photo_path = $db_path; // Store relative path for web access
        }
    } elseif (!empty($_POST['photo_base64'])) {
        $data = $_POST['photo_base64'];

        // Extract type and data
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $type = strtolower($type[1]);
            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                $type = 'png';
            }
        } else {
            $type = 'png';
            // Cleanup standard base64 issues
            $data = str_replace('data:image/png;base64,', '', $data);
            $data = str_replace(' ', '+', $data);
        }

        $data = base64_decode($data);
        if ($data !== false) {
            $file_name = "cam_" . time() . "." . $type;
            $target_file = $target_dir . $file_name;
            $db_path = $upload_dir_name . $file_name;

            if (file_put_contents($target_file, $data)) {
                $photo_path = $db_path; // Store relative path
            }
        }
    }

    // Check for duplicate National ID
    $check_sql = "SELECT patient_id FROM patients WHERE national_id = ? AND national_id != ''";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $nat_id);
    $check_stmt->execute();
    $res = $check_stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $error = "عذراً، هذا رقم الهوية ( $nat_id ) مسجل مسبقاً لمريض آخر في النظام.";
    } else {
        $sql = "INSERT INTO patients (file_number, full_name_ar, full_name_en, national_id, date_of_birth, gender, phone1, address, category, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssss", $file_num, $name, $name_en, $nat_id, $dob, $gender, $phone, $address, $category, $photo_path);

        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            // Redirect to the same page with a success flag to show the ID card
            header("Location: add_patient?success_id=$new_id");
            exit();
        } else {
            $error = "خطأ في التسجيل: " . $conn->error;
        }
    }
}

$success_id = $_GET['success_id'] ?? null;
$new_patient = null;
if ($success_id) {
    $new_patient = $conn->query("SELECT * FROM patients WHERE patient_id = $success_id")->fetch_assoc();
}

include 'header.php';
?>

<?php if ($success_id && $new_patient): ?>
    <!-- Success ID Card View -->
    <div class="row justify-content-center animate__animated animate__zoomIn">
        <div class="col-md-5">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white">
                <div class="card-header bg-success text-white text-center py-3 border-0">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-check-circle me-1"></i> تم تسجيل المريض بنجاح</h5>
                </div>
                <div class="card-body p-4 text-center">
                    <div id="id-card-print" class="p-4 border rounded-4 bg-light position-relative overflow-hidden"
                        style="min-height: 300px;">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div class="text-start">
                                <h4 class="fw-bold mb-0" style="color: #1d1d1f;">HealthPro</h4>
                                <small class="text-muted">ID CARD / بطاقة المريض</small>
                            </div>
                            <div class="avatar">
                                <?php if (!empty($new_patient['photo']) && file_exists(__DIR__ . '/' . $new_patient['photo'])): ?>
                                    <img src="<?php echo $new_patient['photo']; ?>" class="rounded-3 shadow-sm"
                                        style="width: 80px; height: 80px; object-fit: cover; border: 3px solid #fff;">
                                <?php else: ?>
                                    <div class="rounded-3 bg-white shadow-sm d-flex align-items-center justify-content-center border"
                                        style="width: 80px; height: 80px;">
                                        <i class="fas fa-user text-muted fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="text-center mb-4">
                            <h4 class="fw-bold mb-1"><?php echo $new_patient['full_name_ar']; ?></h4>
                            <?php if (!empty($new_patient['full_name_en'])): ?>
                                <p class="text-muted mb-1" style="font-family: sans-serif;">
                                    <?php echo $new_patient['full_name_en']; ?>
                                </p>
                            <?php endif; ?>
                            <div class="badge bg-primary-subtle text-primary rounded-pill px-3">رقم الملف:
                                <?php echo $new_patient['file_number']; ?>
                            </div>
                        </div>

                        <!-- Barcode Area -->
                        <div class="barcode-box bg-white p-3 rounded-3 border">
                            <canvas id="newBarcode"></canvas>
                        </div>
                        <p class="mt-2 mb-0 small text-muted">استخدم هذا الباركود للحجز السريع في المراجعات</p>
                    </div>

                    <div class="mt-4 d-grid gap-2">
                        <button onclick="printCard()" class="btn btn-dark rounded-pill py-2 fw-bold">
                            <i class="fas fa-print me-2"></i> طباعة البطاقة التعريفية
                        </button>
                        <a href="book?id=<?php echo $success_id; ?>" class="btn btn-primary rounded-pill py-2 fw-bold">
                            الانتقال لحجز موعد <i class="fas fa-arrow-left ms-2"></i>
                        </a>
                        <a href="add_patient" class="btn btn-link text-muted small">تسجيل مريض آخر</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script>
        JsBarcode("#newBarcode", "<?php echo $new_patient['file_number']; ?>", {
            format: "CODE128",
            width: 2,
            height: 50,
            displayValue: true,
            fontSize: 14,
            background: "#fff"
        });

        function printCard() {
            const printContent = document.getElementById('id-card-print');
            const WinPrint = window.open('', '', 'width=900,height=650');
            WinPrint.document.write('<html><head><title>Print ID Card</title>');
            WinPrint.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');
            WinPrint.document.write('<style>body{font-family:Tajawal,sans-serif; direction:rtl; text-align:center; padding:20px;} .barcode-box canvas{max-width:100%;}</style></head><body>');
            WinPrint.document.write(printContent.innerHTML);
            WinPrint.document.write('</body></html>');
            WinPrint.document.close();
            WinPrint.focus();
            setTimeout(() => {
                WinPrint.print();
                WinPrint.close();
            }, 500);
        }
    </script>

<?php else: ?>
    <!-- Original Registration Form -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h4 class="mb-0 fw-bold"><i class="fas fa-user-plus text-primary me-2"></i>تسجيل مريض جديد</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger rounded-4 border-0 shadow-sm">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">الاسم الكامل (عربي)</label>
                                <input type="text" name="full_name" id="nameAr"
                                    class="form-control rounded-3 bg-light border-0"
                                    placeholder="أدخل اسم المريض الرباعي..." required 
                                    oninput="transliterateName(); detectGender()">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">الاسم بالإنجليزية (English Name)</label>
                                <div class="input-group">
                                    <input type="text" name="full_name_en" id="nameEn"
                                        class="form-control rounded-start-3 bg-light border-0"
                                        placeholder="Full Name (English)">
                                    <button type="button" class="btn btn-secondary rounded-end-3"
                                        onclick="transliterateName()">
                                        <i class="fas fa-language me-1"></i> ترجمة
                                    </button>
                                </div>
                            </div>
                            <!-- Helper Script for Offline Transliteration & Gender Detection -->
                            <script>
                                function transliterateName() {
                                    const arName = document.getElementById('nameAr').value;
                                    const map = {
                                        'ا': 'a', 'أ': 'a', 'إ': 'e', 'آ': 'a', 'ب': 'b', 'ت': 't', 'ث': 'th', 'ج': 'j', 'ح': 'h', 'خ': 'kh', 'د': 'd', 'ذ': 'dh', 'ر': 'r', 'ز': 'z', 'س': 's', 'ش': 'sh', 'ص': 's', 'ض': 'd', 'ط': 't', 'ظ': 'z', 'ع': 'a', 'غ': 'gh', 'ف': 'f', 'ق': 'q', 'ك': 'k', 'ل': 'l', 'م': 'm', 'ن': 'n', 'ه': 'h', 'و': 'w', 'ي': 'y', 'ى': 'a', 'ة': 'a', 'ء': '', 'ئ': 'e', 'ؤ': 'o', ' ': ' ',
                                        'َ': 'a', 'ُ': 'u', 'ِ': 'i'
                                    };
                                    let enName = '';
                                    for (let i = 0; i < arName.length; i++) {
                                        const char = arName[i];
                                        enName += map[char] || char;
                                    }
                                    enName = enName.replace(/\b\w/g, l => l.toUpperCase());
                                    document.getElementById('nameEn').value = enName;
                                }

                                function detectGender() {
                                    const fullName = document.getElementById('nameAr').value.trim();
                                    if (!fullName) return;
                                    const firstName = fullName.split(' ')[0];
                                    
                                    const maleExceptions = ['حمزة', 'طلحة', 'عبيدة', 'عكرمة', 'قتادة', 'أسامة', 'معاوية', 'حذيفة', 'ميسرة', 'عروة'];
                                    const femaleNames = ['زينب', 'مريم', 'هند', 'سعاد', 'نور', 'هدى', 'منى', 'ضحى', 'سجى', 'تقى', 'لمى', 'حوراء', 'زهراء', 'فاطمة', 'خديجة', 'عائشة', 'سارة', 'نورا', 'ليلى', 'سلوى']; 

                                    let isFemale = false;
                                    
                                    // Rule 1: Starts with 'Abd' -> Male
                                    if (firstName.startsWith('عبد') || firstName.startsWith('العبد')) {
                                        isFemale = false;
                                    } 
                                    // Rule 2: Explicit Female Names
                                    else if (femaleNames.includes(firstName)) {
                                        isFemale = true;
                                    } 
                                    // Rule 3: Ends with Taa Marbuta (and not exception)
                                    else if (firstName.endsWith('ة')) {
                                        if (!maleExceptions.includes(firstName)) {
                                            isFemale = true;
                                        }
                                    }

                                    // Apply
                                    if (isFemale) {
                                        document.getElementById('g2').checked = true;
                                    } else {
                                        // Default to Male if not detected as female? 
                                        // Or only switch if detected?
                                        // User said: "If male name, choose male. If female, choose female."
                                        // It implies auto-selecting.
                                        document.getElementById('g1').checked = true;
                                    }
                                }
                            </script>


                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">رقم الهاتف</label>
                                <input type="text" name="phone" class="form-control rounded-3 bg-light border-0"
                                    placeholder="07XXXXXXXXX" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold small text-muted">العمر</label>
                                <input type="number" id="ageInput" class="form-control rounded-3 bg-light border-0"
                                    placeholder="سنة" oninput="calculateBirthYear(this.value)">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">تاريخ الميلاد</label>
                                <input type="date" name="dob" id="dobInput" class="form-control rounded-3 bg-light border-0"
                                    required oninput="calculateAge(this.value)">
                            </div>
                            <script>
                                function calculateBirthYear(age) {
                                    if (age && age > 0) {
                                        const year = new Date().getFullYear() - age;
                                        // Default to Jan 1st
                                        document.getElementById('dobInput').value = `${year}-01-01`;
                                    }
                                }

                                function calculateAge(dobStr) {
                                    if (!dobStr) return;
                                    const dob = new Date(dobStr);
                                    const today = new Date();
                                    let age = today.getFullYear() - dob.getFullYear();
                                    const m = today.getMonth() - dob.getMonth();
                                    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                                        age--;
                                    }
                                    document.getElementById('ageInput').value = Math.max(0, age);
                                }
                            </script>

                            <?php if (($_SESSION['discount_enabled'] ?? true) && ($_SESSION['patient_classification_enabled'] ?? true)): ?>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small text-muted">تصنيف المريض (للتخفيض
                                        التلقائي)</label>
                                    <select name="category" class="form-select rounded-3 bg-light border-0" required>
                                        <option value="normal">مريض عادي</option>
                                        <option value="senior">كبار السن (فوق 55 سنة)</option>
                                        <option value="martyr">عوائل الشهداء الأبرار</option>
                                        <option value="special">ذوي الاحتياجات الخاصة</option>
                                    </select>
                                </div>
                            <?php else: ?>
                                <input type="hidden" name="category" value="normal">
                            <?php endif; ?>
                            <div class="col-6">
                                <label class="form-label fw-bold small text-muted">المحافظة</label>
                                <select id="governorateSelect" class="form-select rounded-3 bg-light border-0"
                                    onchange="updateDistricts()">
                                    <option value="">اختر المحافظة...</option>
                                    <!-- Populated by JS -->
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold small text-muted">القضاء / الناحية</label>
                                <select id="districtSelect" class="form-select rounded-3 bg-light border-0"
                                    onchange="updateAddress()">
                                    <option value="">اختر المنطقة...</option>
                                    <!-- Populated by JS -->
                                </select>
                            </div>
                            <input type="hidden" name="address" id="fullAddress">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">الجنس</label>
                                <div class="d-flex gap-3 mt-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" value="male" id="g1"
                                            checked>
                                        <label class="form-check-label" for="g1">ذكر</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" value="female" id="g2">
                                        <label class="form-check-label" for="g2">أنثى</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">صورة المريض</label>
                                <div class="input-group">
                                    <input type="file" name="photo" class="form-control rounded-start-3 bg-light border-0"
                                        accept="image/*">
                                    <button type="button" class="btn btn-outline-primary rounded-end-3"
                                        onclick="openCameraModal()">
                                        <i class="fas fa-camera"></i> التقاط
                                    </button>
                                </div>
                                <input type="hidden" name="photo_base64" id="photo_base64">
                                <div id="photo_preview" class="mt-2 d-none text-center">
                                    <img src="" id="captured_image" class="img-fluid rounded-3 shadow-sm"
                                        style="max-height: 150px;">
                                    <br>
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-1" onclick="clearPhoto()">
                                        <i class="fas fa-trash"></i> حذف الصورة
                                    </button>
                                </div>
                            </div>

                            <script>
                                const iraqLocations = {
                                    "ذي قار": ["الناصرية", "الرفاعي", "الشطرة", "الغراف", "سوق الشيوخ", "الجبايش", "قلعة سكر", "الدواية", "الإصلاح", "سيد دخيل", "البطحاء", "الفضليـة", "العكيكة", "كرمة بني سعيد", "الطار", "المنار", "الفجر", "أور", "النصر", "الفهود", "الحمار", "الخميسية", "حي الحبوبي", "حي السراي", "حي الصالحية", "حي النصر القديم", "حي الثورة", "حي الزهراء", "حي الفداء", "حي الشهداء القديمة", "حي الشرقية", "حي الغربية", "حي الصدور", "حي الشرطة", "حي المتنزه", "حي الحسين (ع)", "حي الرافدين", "حي الغدير", "حي التضامن", "حي أور", "حي سومر", "حي أريدو", "حي الزهور", "حي المهندسين", "حي الشموخ", "حي النصر الجديد", "حي الزهراء الجديدة", "حي الشهداء الجديدة", "حي أور الجديدة", "حي سومر الجديدة", "حي الغدير الثاني", "حي الجامعة", "حي العلماء", "حي الإسكان الصناعي", "حي الإسكان القديم", "حي المنصورية", "حي العروبة", "حي المطار", "حي السلام", "حي الفرات", "حي الرافدين الجديدة", "حي التضامن الثاني", "حي الصدور الجديدة", "حي الحبوبي الجديد", "حي الزهور الثاني", "حي أريدو الثاني", "حي الشموخ الثانية"],
                                    "بغداد": ["الكرخ", "رصافة", "الكاظمية", "الأعظمية", "المنصور", "الكرادة", "الدورة", "مدينة الصدر", "الغزالية", "العامرية", "الزعفرانية", "ببغداد الجديدة", "الشعلة", "حي العامل"],
                                    "البصرة": ["البصرة (المركز)", "الزبير", "القرنة", "شط العرب", "أبو الخصيب", "الفاو", "المدينة"],
                                    "أربيل": ["أربيل (المركز)", "سوران", "كويسنجق", "شقلاوة", "ميركسور"],
                                    "نينوى": ["الموصل", "تلعفر", "سنجار", "الحمدانية", "الشيخان", "برطلة"],
                                    "النجف": ["النجف (المركز)", "الكوفة", "المناذرة", "المشخاب"],
                                    "كربلاء": ["كربلاء (المركز)", "الهندية (طويريج)", "عين التمر", "الحسينية"],
                                    "كركوك": ["كركوك (المركز)", "الحويجة", "داقوق", "الدبس"],
                                    "ديالى": ["بعقوبة", "المقدادية", "الخالص", "خانقين", "بلد روز"],
                                    "الأنبار": ["الرمادي", "الفلوجة", "هيت", "القائم", "الرطبة", "حديثة"],
                                    "بابل": ["الحلة", "المسيب", "المحاويل", "الهاشمية"],
                                    "واسط": ["الكوت", "الصويرة", "العزيزية", "الحي", "النعمانية"],
                                    "القادسية": ["الديوانية", "الشامية", "عفك", "الحمزة"],
                                    "المثنى": ["السماوة", "الرميثة", "الخضر", "الوركاء"],
                                    "ميسان": ["العمارة", "الميمونة", "المجر الكبير", "علي الغربي", "الكحلاء"],
                                    "صلاح الدين": ["تكريت", "سامراء", "بيجي", "بلد", "الشرقاط", "الدجيل"],
                                    "دهوك": ["دهوك (المركز)", "زاخو", "سميل", "العمادية"],
                                    "السليمانية": ["السليمانية (المركز)", "رانية", "دوكان", "حلبجة", "كلار"]
                                };

                                const govSelect = document.getElementById('governorateSelect');
                                const distSelect = document.getElementById('districtSelect');
                                const fullAddress = document.getElementById('fullAddress');

                                // Populate Governorates
                                for (const gov in iraqLocations) {
                                    let option = document.createElement('option');
                                    option.value = gov;
                                    option.text = gov;
                                    govSelect.add(option);
                                }

                                function updateDistricts() {
                                    const selectedGov = govSelect.value;
                                    distSelect.innerHTML = '<option value="">اختر المنطقة...</option>'; // Reset

                                    if (selectedGov && iraqLocations[selectedGov]) {
                                        iraqLocations[selectedGov].forEach(dist => {
                                            let option = document.createElement('option');
                                            option.value = dist;
                                            option.text = dist;
                                            distSelect.add(option);
                                        });
                                    }
                                    updateAddress();
                                }

                                function updateAddress() {
                                    const gov = govSelect.value;
                                    const dist = distSelect.value;
                                    if (gov && dist) {
                                        fullAddress.value = gov + " - " + dist;
                                    } else if (gov) {
                                        fullAddress.value = gov;
                                    } else {
                                        fullAddress.value = "";
                                    }
                                }
                            </script>
                        </div>
                        <div class="mt-4 pt-3 border-top d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-5 rounded-pill fw-bold">حفظ بيانات
                                المريض</button>
                            <a href="patients" class="btn btn-light px-4 rounded-pill">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Camera Modal -->
<div class="modal fade" id="cameraModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">التقاط صورة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <video id="video" width="100%" height="auto" autoplay playsinline class="bg-dark rounded-3"></video>
                <canvas id="canvas" class="d-none"></canvas>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary rounded-pill px-4" onclick="capturePhoto()"><i
                        class="fas fa-camera me-1"></i> التقاط</button>
            </div>
        </div>
    </div>
</div>

<script>
    let videoStream = null;
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const photoBase64 = document.getElementById('photo_base64');
    const photoPreview = document.getElementById('photo_preview');
    const capturedImage = document.getElementById('captured_image');

    function openCameraModal() {
        const modal = new bootstrap.Modal(document.getElementById('cameraModal'));
        modal.show();
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                videoStream = stream;
                video.srcObject = stream;
            })
            .catch(err => {
                console.error("Error accessing camera: ", err);
                alert("تعذر الوصول إلى الكاميرا. يرجى التأكد من السماح بالوصول.");
            });
    }

    // Stop camera when modal is closed
    document.getElementById('cameraModal').addEventListener('hidden.bs.modal', function () {
        if (videoStream) {
            videoStream.getTracks().forEach(track => track.stop());
            videoStream = null;
        }
    });

    function capturePhoto() {
        const context = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        const dataURL = canvas.toDataURL('image/png');
        photoBase64.value = dataURL;
        capturedImage.src = dataURL;
        photoPreview.classList.remove('d-none');

        // Close modal
        const modalEl = document.getElementById('cameraModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal.hide();
    }

    function clearPhoto() {
        photoBase64.value = '';
        capturedImage.src = '';
        photoPreview.classList.add('d-none');
    }
</script>

<?php include 'footer.php'; ?>