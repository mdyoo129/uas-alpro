<?php
session_start();
require '../db_conn.php';

if (!isset($_SESSION['user_id'])) {
    echo 0;
    exit();
}

if (isset($_POST['id'])) {
    $id = $_POST['id'];
    $user_id = $_SESSION['user_id'];

    if (empty($id)) {
        echo 0;
    } else {
        // Check if the to-do item belongs to the logged-in user
        $stmt = $conn->prepare("SELECT user_id FROM todos WHERE id=?");
        $stmt->execute([$id]);
        $todo_user_id = $stmt->fetchColumn();

        if ($todo_user_id == $user_id) {
            $stmt = $conn->prepare("DELETE FROM todos WHERE id=?");
            $res = $stmt->execute([$id]);

            if ($res) {
                echo 1;
            } else {
                echo 0;
            }
        } else {
            echo 0;
        }
    }
} else {
    header("Location: ../index.php?mess=error");
}
?>