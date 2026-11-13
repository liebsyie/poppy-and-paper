
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/products.php');
    exit;
}

if (
    empty($_SESSION['csrf_token']) || 
    empty($_POST['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    $_SESSION['product_error'] = 'Invalid request. Please refresh the page and try again.';
    header('Location: ../admin/products.php');
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'save') {
    
    $productId = filter_var($_POST['product_id'] ?? '', FILTER_VALIDATE_INT);
    $categoryId = filter_var($_POST['category_id'] ?? '', FILTER_VALIDATE_INT);
    
    $name = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priceInput = trim($_POST['price'] ?? '');
    $stock = filter_var($_POST['stock_quantity'] ?? '', FILTER_VALIDATE_INT);
    $imagePath = trim($_POST['image_path'] ?? '');
    $status = $_POST['status'] ?? 'active';

    $_SESSION['product_old_input'] = $_POST;
    $_SESSION['product_old_input']['product_id'] = $productId ?: '';
    $_SESSION['product_old_input']['stock_quantity'] = $stock === false ? 0 : $stock;

    try {
        if (!$categoryId || $categoryId < 1) {
            throw new Exception('Please select a valid category.');
        }
        if ($name === '' || strlen($name) > 150) {
            throw new Exception('Product name is required and must not exceed 150 characters.');
        }
        if ($description === '') {
            throw new Exception('Please enter a product description.');
        }
        if ($priceInput === '' || !is_numeric($priceInput) || !is_finite((float) $priceInput) || (float) $priceInput <= 0 || (float) $priceInput > 99999999.99) {
            throw new Exception('Please enter a valid product price.');
        }
        if ($stock === false || $stock < 0) {
            throw new Exception('Please enter a valid stock quantity.');
        }
        if (strlen($imagePath) > 255) {
            throw new Exception('Image path must not exceed 255 characters.');
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new Exception('Invalid product status.');
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE category_id = :id");
        $stmt->execute(['id' => $categoryId]);
        if ((int) $stmt->fetchColumn() === 0) {
            throw new Exception('Selected category does not exist.');
        }

        if ($productId) {
            $stmt = $pdo->prepare("
                UPDATE products
                SET
                    category_id = :category_id,
                    product_name = :product_name,
                    description = :description,
                    price = :price,
                    stock_quantity = :stock,
                    image_path = :image_path,
                    status = :status
                WHERE product_id = :id
            ");
            $stmt->execute([
                'category_id' => $categoryId,
                'product_name' => $name,
                'description' => $description,
                'price' => $priceInput,
                'stock' => $stock,
                'image_path' => $imagePath !== '' ? $imagePath : null,
                'status' => $status,
                'id' => $productId
            ]);

            unset($_SESSION['product_old_input']);
            $_SESSION['product_success'] = 'Product successfully updated!';
            header('Location: ../admin/products.php?updated=1');
            exit;

        } else {
            $stmt = $pdo->prepare("
                INSERT INTO products (
                    category_id, product_name, description, price, stock_quantity, image_path, status
                ) VALUES (
                    :category_id, :product_name, :description, :price, :stock, :image_path, :status
                )
            ");
            $stmt->execute([
                'category_id' => $categoryId,
                'product_name' => $name,
                'description' => $description,
                'price' => $priceInput,
                'stock' => $stock,
                'image_path' => $imagePath !== '' ? $imagePath : null,
                'status' => $status
            ]);

            unset($_SESSION['product_old_input']);
            $_SESSION['product_success'] = 'Product successfully added!';
            header('Location: ../admin/products.php?added=1');
            exit;
        }

    } catch (Exception $e) {
        $_SESSION['product_error'] = $e->getMessage();
        header('Location: ../admin/products.php');
        exit;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $_SESSION['product_error'] = 'Unable to save the product. Please try again.';
        header('Location: ../admin/products.php');
        exit;
    }
}

elseif ($action === 'toggle') {
    
    $productId = filter_var($_POST['product_id'] ?? '', FILTER_VALIDATE_INT);
    $newStatus = $_POST['status'] ?? '';

    if ($productId && in_array($newStatus, ['active', 'inactive'], true)) {
        try {
            $stmt = $pdo->prepare("UPDATE products SET status = :status WHERE product_id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $productId]);

            $_SESSION['product_success'] = 'Product status successfully changed!';
            header('Location: ../admin/products.php?status_changed=1');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $_SESSION['product_error'] = 'Unable to change status. Please try again.';
            header('Location: ../admin/products.php');
            exit;
        }
    } else {
        $_SESSION['product_error'] = 'Invalid status request.';
        header('Location: ../admin/products.php');
        exit;
    }
}

elseif ($action === 'delete') {
    
    $productId = filter_var($_POST['product_id'] ?? '', FILTER_VALIDATE_INT);

    if (!$productId) {
        $_SESSION['product_error'] = 'Invalid product selected.';
        header('Location: ../admin/products.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = :id");
        $stmt->execute(['id' => $productId]);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new Exception('This product has order history and cannot be deleted. You can deactivate it instead.');
        }

        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = :id");
        $stmt->execute(['id' => $productId]);

        $_SESSION['product_success'] = 'Product successfully deleted!';
        header('Location: ../admin/products.php?deleted=1');
        exit;

    } catch (Exception $e) {
        $_SESSION['product_error'] = $e->getMessage();
        header('Location: ../admin/products.php');
        exit;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $_SESSION['product_error'] = 'Unable to delete the product. Please try again.';
        header('Location: ../admin/products.php');
        exit;
    }
}

header('Location: ../admin/products.php');
exit;
