CREATE DATABASE IF NOT EXISTS HospitalSystem CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE HospitalSystem;

CREATE TABLE IF NOT EXISTS departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name_ar VARCHAR(255) NOT NULL,
    department_name_en VARCHAR(255) DEFAULT '',
    department_type VARCHAR(50) DEFAULT 'medical',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'receptionist',
    department_id INT DEFAULT NULL,
    full_name_ar VARCHAR(255) DEFAULT '',
    full_name_en VARCHAR(255) DEFAULT '',
    email VARCHAR(255) DEFAULT NULL,
    last_activity DATETIME DEFAULT NULL,
    current_task VARCHAR(255) DEFAULT 'متواجد',
    active_patient_name VARCHAR(255) DEFAULT NULL,
    permissions TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patients (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    file_number VARCHAR(100) DEFAULT NULL,
    full_name_ar VARCHAR(255) NOT NULL,
    full_name_en VARCHAR(255) DEFAULT '',
    national_id VARCHAR(100) DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    gender VARCHAR(20) DEFAULT 'male',
    phone1 VARCHAR(50) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    category VARCHAR(50) DEFAULT 'normal',
    photo VARCHAR(255) DEFAULT NULL,
    birth_date DATE DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    doctor_id INT DEFAULT NULL,
    department_id INT DEFAULT NULL,
    appointment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'scheduled',
    call_status VARCHAR(50) DEFAULT NULL,
    is_free TINYINT(1) DEFAULT 0,
    is_urgent TINYINT(1) DEFAULT 0,
    completed_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS triage (
    triage_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT,
    patient_id INT,
    nurse_id INT DEFAULT NULL,
    weight FLOAT DEFAULT NULL,
    height FLOAT DEFAULT NULL,
    temperature FLOAT DEFAULT NULL,
    blood_pressure VARCHAR(50) DEFAULT NULL,
    pulse INT DEFAULT NULL,
    oxygen FLOAT DEFAULT NULL,
    nurse_notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (nurse_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS consultations (
    consultation_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    doctor_id INT DEFAULT NULL,
    appointment_id INT DEFAULT NULL,
    subjective TEXT DEFAULT NULL,
    assessment TEXT DEFAULT NULL,
    plan TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lab_tests (
    test_id INT AUTO_INCREMENT PRIMARY KEY,
    test_name VARCHAR(100) NOT NULL UNIQUE,
    price FLOAT DEFAULT 15000,
    min_value FLOAT DEFAULT NULL,
    max_value FLOAT DEFAULT NULL,
    unit VARCHAR(32) DEFAULT NULL,
    gender ENUM('male','female','both') DEFAULT 'both',
    reference_range VARCHAR(128) DEFAULT NULL,
    profile_components TEXT DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lab_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT,
    patient_id INT,
    doctor_id INT DEFAULT NULL,
    test_type VARCHAR(255) DEFAULT NULL,
    result TEXT DEFAULT NULL,
    price FLOAT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'pending',
    estimated_time_minutes INT DEFAULT 30,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS radiology_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT,
    patient_id INT,
    doctor_id INT DEFAULT NULL,
    scan_type VARCHAR(255) DEFAULT NULL,
    report TEXT DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    price FLOAT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'pending',
    estimated_time_minutes INT DEFAULT 45,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS prescriptions (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT,
    patient_id INT,
    doctor_id INT DEFAULT NULL,
    medicine_name VARCHAR(255) DEFAULT NULL,
    price FLOAT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoices (
    invoice_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT,
    patient_id INT,
    amount FLOAT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'unpaid',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS referrals (
    referral_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT,
    patient_id INT,
    from_doctor_id INT,
    to_department_id INT,
    reason TEXT,
    priority ENUM('normal', 'urgent') DEFAULT 'normal',
    status VARCHAR(50) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id),
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    FOREIGN KEY (from_doctor_id) REFERENCES users(user_id),
    FOREIGN KEY (to_department_id) REFERENCES departments(department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT,
    receiver_id INT,
    message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO departments (department_name_ar, department_type) VALUES
('معلومات الاستقبال', 'medical'),
('عيادة الطبيب العام', 'medical'),
('المختبر', 'medical'),
('الأشعة', 'medical'),
('الصيدلية', 'medical'),
('المحاسبة', 'administrative'),
('الإدارة العامة', 'administrative');

INSERT INTO users (username, password_hash, role, department_id, full_name_ar, email) VALUES
('admin', '123456', 'admin', 7, 'المدير العام', 'admin@healthpro.com'),
('doctor', '123456', 'doctor', 2, 'د. محمد علي', 'doctor@healthpro.com'),
('reception', '123456', 'receptionist', 1, 'أحمد الاستقبال', 'reception@healthpro.com'),
('nurse', '123456', 'nurse', 2, 'ملاك التمريض', 'nurse@healthpro.com'),
('lab', '123456', 'lab_tech', 3, 'يوسف المختبر', 'lab@healthpro.com'),
('radio', '123456', 'radiologist', 4, 'سارة الأشعة', 'radio@healthpro.com'),
('pharmacy', '123456', 'pharmacist', 5, 'علي الصيدلية', 'pharmacy@healthpro.com'),
('accountant', '123456', 'accountant', 6, 'كمال المحاسب', 'accountant@healthpro.com');
