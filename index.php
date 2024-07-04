<?php
session_start();
require 'db_conn.php';

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-do List</title>
    <link rel="stylesheet" href="css/style.css?v=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap">
</head>

<body>
    <div class="header-section">
        <div class="welcome-message">
            <p>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        </div>
        <a href="logout.php" class="logout-button">Logout</a>
    </div>

    <div class="main-section">
        <h1 class="title">───※To-do List※───</h1>

        <div class="add-section">
            <form action="app/add.php" method="POST" autocomplete="off">
                <?php if(isset($_GET['mess']) && $_GET['mess'] == 'error'){ ?>
                <input type="text" name="title" style="border-color: #ff6666" placeholder="What do you want to do?" />
                <?php }else{ ?>
                <input type="text" name="title" placeholder="What do you want to do?" />
                <?php } ?>

                <div class="row">
                    <!-- Input for category -->
                    <input type="text" name="category" placeholder="Category (optional)" />
                    <!-- Input for deadline -->
                    <input type="datetime-local" name="deadline" />
                </div>
                <button type="submit">Add +</button>
            </form>
        </div>

        <div class="filter-section">
            <label for="category-filter">Filter by Category:</label>
            <select id="category-filter">
                <option value="all">All Categories</option>
                <!-- Tambahkan opsi kategori dari database di sini -->
                <?php 
                // Query untuk mendapatkan kategori yang memiliki to-do items
                $categories = $conn->prepare("
                    SELECT categories.id, categories.name
                    FROM categories
                    JOIN todos ON categories.id = todos.category_id
                    WHERE todos.user_id = :user_id
                    GROUP BY categories.id
                    ORDER BY categories.name ASC
                ");
                $categories->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                $categories->execute();
                
                while ($category = $categories->fetch(PDO::FETCH_ASSOC)) {
                    $selected = (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : '';
                    echo '<option value="' . $category['id'] . '" ' . $selected . '>' . htmlspecialchars($category['name']) . '</option>';
                }
                ?>
            </select>
        </div>

        <?php
        // Ambil nilai filter kategori jika ada
        $filter_category_id = isset($_GET['category']) ? $_GET['category'] : 'all';

        // Query untuk mengambil data to-do berdasarkan kategori yang dipilih
        if ($filter_category_id === 'all') {
            $query = "SELECT todos.*, categories.name AS category_name 
                      FROM todos 
                      LEFT JOIN categories ON todos.category_id = categories.id 
                      WHERE todos.user_id = :user_id
                      ORDER BY categories.id DESC, todos.id DESC";
        } else {
            $query = "SELECT todos.*, categories.name AS category_name 
                      FROM todos 
                      LEFT JOIN categories ON todos.category_id = categories.id 
                      WHERE todos.category_id = :category_id AND todos.user_id = :user_id
                      ORDER BY todos.id DESC";
        }
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);

        if ($filter_category_id !== 'all') {
            $stmt->bindParam(':category_id', $filter_category_id, PDO::PARAM_INT);
        }

        $stmt->execute();
        $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="show-todo-section">
            <?php if(count($todos) <= 0){ ?>
            <div class="todo-item">
                <div class="empty">
                    <img src="img/air.gif" width="100%" />
                </div>
            </div>
            <?php } ?>

            <?php foreach($todos as $todo) { ?>
            <div class="todo-item">
                <span class="ellipsis-menu">
                    <i class="fas fa-ellipsis-v ellipsis-icon"></i>
                    <div class="menu-options">
                        <span class="edit">Edit</span>
                        <span class="delete">Delete</span>
                    </div>
                </span>
                <?php if($todo['checked']){ ?>
                <input type="checkbox" class="check-box" data-todo-id="<?php echo $todo['id']; ?>" checked />
                <h2 class="checked"><?php echo htmlspecialchars($todo['title']) ?></h2>
                <?php }else { ?>
                <input type="checkbox" data-todo-id="<?php echo $todo['id']; ?>" class="check-box" />
                <h2><?php echo htmlspecialchars($todo['title']) ?></h2>
                <input type="text" class="edit-input" value="<?php echo htmlspecialchars($todo['title']) ?>"
                    style="display:none;" />
                <?php } ?>
                <br>
                <?php if(isset($todo['deadline']) && $todo['deadline'] !== '' && $todo['deadline'] !== '0000-00-00 00:00:00'): ?>
                <small class="deadline">Due: <?php echo htmlspecialchars($todo['deadline']) ?></small>
                <?php endif; ?>

                <!-- Display category if available -->
                <?php if (!empty($todo['category_name'])): ?>
                <small class="category"><?php echo htmlspecialchars($todo['category_name']) ?></small>
                <?php endif; ?>
            </div>
            <?php } ?>
        </div>
    </div>

    <script src="js/jquery-3.2.1.min.js"></script>

    <script>
    $(document).ready(function() {
        // Handler untuk perubahan pada select filter kategori
        $('#category-filter').change(function() {
            var selectedCategory = $(this).val();
            var url = 'index.php'; // Ganti dengan URL halaman Anda
            if (selectedCategory !== 'all') {
                url += '?category=' + selectedCategory;
            }
            window.location.href = url;
        });

        $('.ellipsis-icon').click(function() {
            var menuOptions = $(this).siblings('.menu-options');
            $('.menu-options').not(menuOptions)
                .hide(); // Sembunyikan semua menu-options kecuali yang diklik
            menuOptions.toggle(); // Toggle (tampilkan/sembunyikan) menu-options yang sesuai
        });

        // Sembunyikan menu-options jika klik di luar area .ellipsis-menu
        $(document).click(function(event) {
            if (!$(event.target).closest('.ellipsis-menu').length) {
                $('.menu-options').hide();
            }
        });

        // Handle klik pada opsi Edit
        $('.edit').click(function() {
            var todoItem = $(this).closest('.todo-item');
            var titleElement = todoItem.find('h2');
            var editInput = todoItem.find('.edit-input');

            // Tampilkan input edit dan sembunyikan elemen h2
            titleElement.hide();
            editInput.show().focus();

            // Ketika input kehilangan fokus atau ditekan enter, simpan perubahan
            editInput.on('blur keyup', function(e) {
                if (e.type === 'blur' || e.key === 'Enter') {
                    var newTitle = editInput.val();
                    var id = todoItem.find('.check-box').data('todo-id');

                    $.post('app/edit.php', {
                        id: id,
                        title: newTitle
                    }, function(data) {
                        if (data === 'success') {
                            titleElement.text(newTitle);
                        }
                        // Kembali ke tampilan awal
                        editInput.hide();
                        titleElement.show();
                    });
                }
            });
        });

        // Handle klik pada opsi Delete
        $('.delete').click(function() {
            const id = $(this).closest('.todo-item').find('.check-box').data('todo-id');

            $.post("app/remove.php", {
                    id: id
                },
                (data) => {
                    if (data) {
                        $(this).closest('.todo-item').hide(600);
                    }
                }
            );
        });

        // Handle checkbox change
        $(".check-box").click(function(e) {
            const id = $(this).attr('data-todo-id');

            $.post('app/check.php', {
                    id: id
                },
                (data) => {
                    if (data != 'error') {
                        const h2 = $(this).next();
                        if (data === '1') {
                            h2.removeClass('checked');
                        } else {
                            h2.addClass('checked');
                        }
                    }
                }
            );
        });
    });
    </script>
</body>

</html>
