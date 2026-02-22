<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'delete_item':
            deleteMenuItem();
            break;
        case 'add_category':
            addCategory();
            break;
        case 'edit_category':
            editCategory();
            break;
        case 'delete_category':
            deleteCategory();
            break;
        default:
            setFlashMessage('error', 'Invalid action');
            header("Location: menu.php");
            exit();
    }
}

// Delete menu item
function deleteMenuItem() {
    global $conn;
    
    $item_id = (int)$_POST['item_id'];
    
    // Check if item exists
    $check_sql = "SELECT id FROM menu_items WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $item_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        setFlashMessage('error', 'Menu item not found');
        header("Location: menu.php");
        
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete menu item ingredients
        $delete_ingredients_sql = "DELETE FROM menu_item_ingredients WHERE menu_item_id = ?";
        $delete_ingredients_stmt = $conn->prepare($delete_ingredients_sql);
        $delete_ingredients_stmt->bind_param("i", $item_id);
        $delete_ingredients_stmt->execute();
        
        // Delete the menu item
        $delete_sql = "DELETE FROM menu_items WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $item_id);
        $delete_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        setFlashMessage('success', 'Menu item deleted successfully');
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        setFlashMessage('error', 'Failed to delete menu item: ' . $e->getMessage());
    }
    
    header("Location: menu.php");
    exit();
}

// Add category
function addCategory() {
    global $conn;
    
    $category_name = trim($_POST['category_name']);
    $category_type = $_POST['category_type']; // 'inventory' or 'menu'
    
    if (empty($category_name)) {
        setFlashMessage('error', 'Category name cannot be empty');
        header("Location: " . ($category_type === 'inventory' ? 'inventory.php' : 'menu.php'));
        exit();
    }
    
    // Check if category already exists
    $check_sql = "SELECT id FROM categories WHERE name = ? AND type = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $category_name, $category_type);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        setFlashMessage('error', 'Category already exists');
        header("Location: " . ($category_type === 'inventory' ? 'inventory.php' : 'menu.php'));
        exit();
    }
    
    // Add the category
    $insert_sql = "INSERT INTO categories (name, type) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ss", $category_name, $category_type);
    
    if ($insert_stmt->execute()) {
        setFlashMessage('success', 'Category added successfully');
    } else {
        setFlashMessage('error', 'Failed to add category: ' . $conn->error);
    }
    
    header("Location: " . ($category_type === 'inventory' ? 'inventory.php' : 'menu.php'));
    exit();
}

// Edit category
function editCategory() {
    global $conn;
    
    $category_id = (int)$_POST['category_id'];
    $category_name = trim($_POST['category_name']);
    
    // Get category type for redirect
    $type_sql = "SELECT type FROM categories WHERE id = ?";
    $type_stmt = $conn->prepare($type_sql);
    $type_stmt->bind_param("i", $category_id);
    $type_stmt->execute();
    $type_result = $type_stmt->get_result();
    $category_type = 'menu'; // Default
    
    if ($type_result->num_rows > 0) {
        $type_row = $type_result->fetch_assoc();
        $category_type = $type_row['type'];
    }
    
    if (empty($category_name)) {
        setFlashMessage('error', 'Category name cannot be empty');
        header("Location: " . ($category_type === 'inventory' ? 'inventory.php' : 'menu.php'));
        exit();
    }
    
    // Check if category exists
    $check_sql = "SELECT id FROM categories WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $category_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        setFlashMessage('error', 'Category not found');
        header("Location: " . ($category_type === 'inventory' ? 'inventory.php' : 'menu.php'));
        exit();
    }
    
    // Update the category
    $update_sql = "UPDATE categories SET name = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $category_name, $category_id);
    
    if ($update_stmt->execute()) {
        setFlashMessage('success', 'Category updated successfully');
    } else {
        setFlashMessage('error', 'Failed to update category: ' . $conn->error);
    }
    
    header("Location: " . ($category_type === 'inventory' ? 'inventory.php' : 'menu.php'));
    exit();
}

// Delete category
function deleteCategory() {
    global $conn;
    
    $category_id = (int)$_POST['category_id'];
    
    // Get category type for redirect
    $type_sql = "SELECT type FROM categories WHERE id = ?";
    $type_stmt = $conn->prepare($type_sql);
    $type_stmt->bind_param("i", $category_id);
    $type_stmt->execute();
    $type_result = $type_stmt->get_result();
    $category_type = 'menu'; // Default
    
    if ($type_result->num_rows > 0) {
        $type_row = $type_result->fetch_assoc();
        $category_type = $type_row['type'];
    }
    
    // Check if category exists
    $check_sql = "SELECT id FROM categories WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $category_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        setFlashMessage('error', 'Category not found');
        header("Location: " . ($category_type === 'inventory' ? 'inventory.php' : 'menu.php'));
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update items to remove category (set to NULL)
        if ($category_type === 'inventory') {
            $update_sql = "UPDATE inventory_items SET category_id = NULL WHERE category_id = ?";
        } else {
            $update_sql = "UPDATE menu_items SET category_id = NULL WHERE category_id = ?";
        }
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $category_id);
        $update_stmt->execute();
        
        // Delete the category
        $delete_sql = "DELETE FROM categories WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $category_id);
        $delete_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        setFlashMessage('success', 'Category deleted successfully');
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        setFlashMessage('error', 'Failed to delete category: ' . $e->getMessage());
    }
    
    header("Location: " . ($category_type === 'inventory' ? 'inventory.php' : 'menu.php'));
    exit();
}

// Helper function to set flash messages
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}
