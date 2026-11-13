
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/categories.php');
    exit;
}

if (
    empty($_SESSION['csrf_token']) || 
    empty($_POST['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    $_SESSION['category_error'] = 'Invalid request. Please refresh the page and try again.';
    header('Location: ../admin/categories.php');
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'save') {
    
    $categoryId = filter_var($_POST['category_id'] ?? '', FILTER_VALIDATE_INT);
    $categoryName = trim($_POST['category_name'] ?? '');

    if ($categoryName === '' || strlen($categoryName) > 100) {
        $_SESSION['category_error'] = 'Category name is required and must not exceed 100 characters.';
        header('Location: ../admin/categories.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM categories
            WHERE category_name = :name
            AND category_id != :id
        ");

        $stmt->execute([
            'name' => $categoryName,
            'id' => $categoryId ?: 0
        ]);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new Exception('This category already exists.');
        }

        if ($categoryId) {
            $stmt = $pdo->prepare("
                UPDATE categories
                SET category_name = :name
                WHERE category_id = :id
            ");
            $stmt->execute([
                'name' => $categoryName,
                'id' => $categoryId
            ]);

            $_SESSION['category_success'] = 'Category successfully updated!';
            header('Location: ../admin/categories.php?updated=1');
            exit;

        } else {
            $stmt = $pdo->prepare("
                INSERT INTO categories (category_name)
                VALUES (:name)
            ");
            $stmt->execute([
                'name' => $categoryName
            ]);

            $_SESSION['category_success'] = 'Category successfully added!';
            header('Location: ../admin/categories.php?added=1');
            exit;
        }

    } catch (Exception $e) {
        $_SESSION['category_error'] = $e->getMessage();
        header('Location: ../admin/categories.php');
        exit;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $_SESSION['category_error'] = 'Unable to save the category. Please try again.';
        header('Location: ../admin/categories.php');
        exit;
    }
}

elseif ($action === 'delete') {
    
    $categoryId = filter_var($_POST['category_id'] ?? '', FILTER_VALIDATE_INT);

    if (!$categoryId) {
        $_SESSION['category_error'] = 'Invalid category selected.';
        header('Location: ../admin/categories.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM products
            WHERE category_id = :id
        ");
        $stmt->execute(['id' => $categoryId]);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new Exception(
                'This category still has products assigned to it. Move or remove those products before deleting this category.'
            );
        }

        $stmt = $pdo->prepare("
            DELETE FROM categories
            WHERE category_id = :id
        ");
        $stmt->execute(['id' => $categoryId]);

        $_SESSION['category_success'] = 'Category successfully deleted!';
        header('Location: ../admin/categories.php?deleted=1');
        exit;

    } catch (Exception $e) {
        $_SESSION['category_error'] = $e->getMessage();
        header('Location: ../admin/categories.php');
        exit;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $_SESSION['category_error'] = 'Unable to delete the category. Please try again.';
        header('Location: ../admin/categories.php');
        exit;
    }
}

header('Location: ../admin/categories.php');
exit;
