<?php
session_start();
require '../db_conn.php';

if (!isset($_SESSION['user_id'])) {
    echo 'error';
    exit();
}

if (isset($_POST['id']) && isset($_POST['title'])) {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $user_id = $_SESSION['user_id'];

    if (empty($title)) {
        echo 'error';
    } else {
        // Check if the to-do item belongs to the logged-in user
        $stmt = $conn->prepare("SELECT user_id FROM todos WHERE id=?");
        $stmt->execute([$id]);
        $todo_user_id = $stmt->fetchColumn();

        if ($todo_user_id == $user_id) {
            $stmt = $conn->prepare("UPDATE todos SET title=? WHERE id=?");
            $res = $stmt->execute([$title, $id]);

            if ($res) {
                echo 'success';
            } else {
                echo 'error';
            }
        } else {
            echo 'error';
        }
    }
} else {
    echo 'error';
}
?>