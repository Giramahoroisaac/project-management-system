<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db_connect.php';

// Ensure user is logged in
requireLogin();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $project_name = mysqli_real_escape_string($conn, $_POST['project_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $institution = mysqli_real_escape_string($conn, $_POST['institution']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Insert project details
        $sql = "INSERT INTO projects (user_id, project_name, description, institution, location, contact, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssss", $user_id, $project_name, $description, $institution, $location, $contact);
        mysqli_stmt_execute($stmt);
        $project_id = mysqli_insert_id($conn);

        // Handle file uploads
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain', 'application/zip'];
        $max_size = 10 * 1024 * 1024; // 10MB

        if (!empty($_FILES['project_files']['name'][0])) {
            $upload_path = "../uploads/projects/";
            
            foreach ($_FILES['project_files']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['project_files']['error'][$key] === 0) {
                    $file_size = $_FILES['project_files']['size'][$key];
                    $file_type = $_FILES['project_files']['type'][$key];
                    $file_name = $_FILES['project_files']['name'][$key];

                    // Validate file
                    if ($file_size > $max_size) {
                        throw new Exception("File $file_name is too large. Maximum size is 10MB.");
                    }
                    if (!in_array($file_type, $allowed_types)) {
                        throw new Exception("File $file_name is not an allowed file type.");
                    }

                    // Generate unique filename
                    $new_filename = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $file_name);
                    $file_path = $upload_path . $new_filename;

                    // Move file
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        // Save file info to database
                        $file_sql = "INSERT INTO project_files (project_id, file_name, file_path) VALUES (?, ?, ?)";
                        $file_stmt = mysqli_prepare($conn, $file_sql);
                        mysqli_stmt_bind_param($file_stmt, "iss", $project_id, $file_name, $new_filename);
                        mysqli_stmt_execute($file_stmt);
                    } else {
                        throw new Exception("Failed to upload file $file_name");
                    }
                }
            }
        }

        // Log the action
        logAction($conn, 'project_submit', "User submitted project: $project_name");

        // Commit transaction
        mysqli_commit($conn);
        
        $_SESSION['message'] = "Project submitted successfully!";
        header("Location: dashboard.php");
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: submit_project.php");
        exit();
    }
}

// If not POST request, redirect to dashboard
header("Location: dashboard.php");
exit();
?>
