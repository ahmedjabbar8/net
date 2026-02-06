CREATE DATABASE IF NOT EXISTS HospitalSystem;
USE HospitalSystem;

-- --- 1. Departments ---
CREATE TABLE IF NOT EXISTS departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name_ar VARCHAR(100) NOT NULL,
    department_name_en VARCHAR(100),
    department_type ENUM('medical', 'administrative', 'support', 'specialized') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- --- 2. Users ---
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name_ar VARCHAR(100) NOT NULL,
    full_name_en VARCHAR(100),
    role VARCHAR(50) NOT NULL,
    department_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
);

-- --- 3. Patients ---
CREATE TABLE IF NOT EXISTS patients (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    file_number VARCHAR(50) UNIQUE NOT NULL,
    national_id VARCHAR(20) UNIQUE,
    full_name_ar VARCHAR(100) NOT NULL,
    full_name_en VARCHAR(100),
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female') NOT NULL,
    phone1 VARCHAR(20) NOT NULL,
    address VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- --- 4. Appointments ---
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    department_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    status VARCHAR(50) DEFAULT 'scheduled',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES users(user_id),
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
);

-- --- 5. Invoices (Billing) ---
CREATE TABLE IF NOT EXISTS invoices (
    invoice_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT,
    patient_id INT,
    amount FLOAT NOT NULL,
    status ENUM('unpaid', 'paid') DEFAULT 'unpaid',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id),
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id)
);

-- --- 6. Triage ---
CREATE TABLE IF NOT EXISTS triage (
    triage_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT,
    weight FLOAT,
    height FLOAT,
    temperature FLOAT,
    blood_pressure VARCHAR(20),
    pulse INT,
    nurse_notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id)
);

-- --- 7. Consultations ---
CREATE TABLE IF NOT EXISTS consultations (
    consultation_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    doctor_id INT,
    appointment_id INT,
    subjective TEXT,
    objective TEXT,
    assessment TEXT,
    plan TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES users(user_id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id)
);

-- --- 8. Laboratory ---
CREATE TABLE IF NOT EXISTS lab_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT,
    patient_id INT,
    doctor_id INT,
    test_type VARCHAR(100) NOT NULL,
    result TEXT,
    price FLOAT DEFAULT 50.0,
    status VARCHAR(50) DEFAULT 'pending_payment',
    estimated_time_minutes INT DEFAULT 30,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id),
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES users(user_id)
);

-- --- 9. Radiology ---
CREATE TABLE IF NOT EXISTS radiology_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT,
    patient_id INT,
    doctor_id INT,
    scan_type VARCHAR(100) NOT NULL,
    report TEXT,
    image_path VARCHAR(255),
    price FLOAT DEFAULT 100.0,
    status VARCHAR(50) DEFAULT 'pending_payment',
    estimated_time_minutes INT DEFAULT 45,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id),
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES users(user_id)
);

-- --- 10. Pharmacy & Medicines ---
CREATE TABLE IF NOT EXISTS medicines (
    medicine_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price FLOAT DEFAULT 0.0,
    stock_quantity INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS prescriptions (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT,
    patient_id INT,
    doctor_id INT,
    medicine_name TEXT,
    dosage VARCHAR(100),
    duration VARCHAR(100),
    price FLOAT DEFAULT 30.0,
    status VARCHAR(50) DEFAULT 'pending_payment',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id),
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    FOREIGN KEY (doctor_id) REFERENCES users(user_id)
);

-- --- 11. Referrals ---
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
);
