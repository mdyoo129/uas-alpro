<?php
session_start();
require '../db_conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (isset($_POST['title'])) {
    $title = $_POST['title'];
    $category_name = isset($_POST['category']) ? $_POST['category'] : null;
    $deadline = isset($_POST['deadline']) ? $_POST['deadline'] : null;
    $user_id = $_SESSION['user_id'];

    if (empty($title)) {
        header("Location: ../index.php?mess=error");
    } else {
        // Check if category is provided
        if (!empty($category_name)) {
            // Check if category already exists
            $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt->execute([$category_name]);
            $category_id = $stmt->fetchColumn();

            if (!$category_id) {
                // If category does not exist, insert new category
                $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$category_name]);
                $category_id = $conn->lastInsertId();
            }
        } else {
            $category_id = null;
        }

        // Prepare and execute the insert statement
        $stmt = $conn->prepare("INSERT INTO todos (title, category_id, deadline, user_id) VALUES (?, ?, ?, ?)");
        $res = $stmt->execute([$title, $category_id, $deadline, $user_id]);

        if ($res) {
            header("Location: ../index.php?mess=success");
        } else {
            header("Location: ../index.php");
        }
        $conn = null;
        exit();
    }
} else {
    header("Location: ../index.php?mess=error");
}
?>