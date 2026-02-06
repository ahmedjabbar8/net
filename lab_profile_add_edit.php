<?php
// ملف إضافة وتعديل بروفايل التحاليل
include 'config.php';

// إضافة بروفايل جديد
if (isset($_POST['add_profile'])) {
    $profile_name = $conn->real_escape_string($_POST['profile_name']);
    $profile_price = (float) $_POST['profile_price'];
    $components = $conn->real_escape_string($_POST['components']);
    $sql = "INSERT INTO lab_tests (test_name, price, profile_components) VALUES ('$profile_name', $profile_price, '$components')";
    if ($conn->query($sql)) {
        echo json_encode(['status'=>'success','msg'=>'تمت إضافة البروفايل بنجاح']);
    } else {
        echo json_encode(['status'=>'error','msg'=>'خطأ في الإضافة: ' . $conn->error]);
    }
    exit();
}

// تعديل سعر بروفايل أو تحليل
if (isset($_POST['edit_price'])) {
    $test_id = (int) $_POST['test_id'];
    $new_price = (float) $_POST['new_price'];
    $sql = "UPDATE lab_tests SET price=$new_price WHERE test_id=$test_id";
    if ($conn->query($sql)) {
        echo json_encode(['status'=>'success','msg'=>'تم تعديل السعر بنجاح']);
    } else {
        echo json_encode(['status'=>'error','msg'=>'خطأ في التعديل: ' . $conn->error]);
    }
    exit();
}

// إضافة أو تعديل نسب التحليل (أقل/أعلى/الطبيعي)
if (isset($_POST['edit_ranges'])) {
    $test_id = (int) $_POST['test_id'];
    $min = isset($_POST['min_value']) ? (float) $_POST['min_value'] : null;
    $max = isset($_POST['max_value']) ? (float) $_POST['max_value'] : null;
    $reference = $conn->real_escape_string($_POST['reference_range'] ?? '');
    $sql = "UPDATE lab_tests SET min_value=".($min === null ? 'NULL' : $min).", max_value=".($max === null ? 'NULL' : $max).", reference_range='$reference' WHERE test_id=$test_id";
    if ($conn->query($sql)) {
        echo json_encode(['status'=>'success','msg'=>'تم تعديل النسب بنجاح']);
    } else {
        echo json_encode(['status'=>'error','msg'=>'خطأ في التعديل: ' . $conn->error]);
    }
    exit();
}

// تفعيل/تعطيل التحليل
if (isset($_POST['toggle_active'])) {
    $test_id = (int) $_POST['test_id'];
    $active = (int) $_POST['active'];
    $sql = "UPDATE lab_tests SET active=$active WHERE test_id=$test_id";
    if ($conn->query($sql)) {
        echo json_encode(['status'=>'success','msg'=>'تم تحديث حالة التفعيل']);
    } else {
        echo json_encode(['status'=>'error','msg'=>'خطأ في التحديث: ' . $conn->error]);
    }
    exit();
}

// ...existing code...