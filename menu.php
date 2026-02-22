<?php
require_once 'config/session.php';
require_once 'config/db.php';
requireLogin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new menu item
    if (isset($_POST['add_item'])) {
        $name = trim($_POST['name']);
        $category_id = $_POST['category_id'];
        $description = trim($_POST['description']);
        $price = $_POST['price'];
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        // Handle image upload
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($ext), $allowed)) {
                $new_filename = uniqid() . '.' . $ext;
                $upload_dir = 'uploads/menu/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename)) {
                    $image = $upload_dir . $new_filename;
                }
            }
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO menu_items (name, category_id, description, price, is_available, image) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category_id, $description, $price, $is_available, $image]);
            
            // Add ingredients if provided
            if (isset($_POST['ingredient_id']) && !empty($_POST['ingredient_id'])) {
                $menu_item_id = $pdo->lastInsertId();
                $ingredient_ids = $_POST['ingredient_id'];
                $ingredient_quantities = $_POST['ingredient_quantity'];
                
                for ($i = 0; $i < count($ingredient_ids); $i++) {
                    if (!empty($ingredient_ids[$i]) && !empty($ingredient_quantities[$i])) {
                        $stmt = $pdo->prepare("INSERT INTO menu_item_ingredients (menu_item_id, inventory_item_id, quantity) 
                                              VALUES (?, ?, ?)");
                        $stmt->execute([$menu_item_id, $ingredient_ids[$i], $ingredient_quantities[$i]]);
                    }
                }
            }
            
            setFlashMessage('success', 'Menu item added successfully');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error adding menu item: ' . $e->getMessage());
        }
        
        header('Location: menu.php');
        exit();
    }
    
    // Edit menu item
    if (isset($_POST['edit_item'])) {
        $item_id = $_POST['item_id'];
        $name = trim($_POST['name']);
        $category_id = $_POST['category_id'];
        $description = trim($_POST['description']);
        $price = $_POST['price'];
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        try {
            // First get the current image
            $stmt = $pdo->prepare("SELECT image FROM menu_items WHERE id = ?");
            $stmt->execute([$item_id]);
            $current_item = $stmt->fetch();
            $image = $current_item['image'];
            
            // Handle new image upload if present
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['image']['name'];
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($ext), $allowed)) {
                    $new_filename = uniqid() . '.' . $ext;
                    $upload_dir = 'uploads/menu/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename)) {
                        // Delete old image if exists
                        if (!empty($image) && file_exists($image)) {
                            unlink($image);
                        }
                        $image = $upload_dir . $new_filename;
                    }
                }
            }
            
            $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, category_id = ?, description = ?, 
                                  price = ?, is_available = ?, image = ? WHERE id = ?");
            $stmt->execute([$name, $category_id, $description, $price, $is_available, $image, $item_id]);
            
            // Update ingredients
            if (isset($_POST['ingredient_id']) && !empty($_POST['ingredient_id'])) {
                // First delete existing ingredients
                $stmt = $pdo->prepare("DELETE FROM menu_item_ingredients WHERE menu_item_id = ?");
                $stmt->execute([$item_id]);
                
                // Add new ingredients
                $ingredient_ids = $_POST['ingredient_id'];
                $ingredient_quantities = $_POST['ingredient_quantity'];
                
                for ($i = 0; $i < count($ingredient_ids); $i++) {
                    if (!empty($ingredient_ids[$i]) && !empty($ingredient_quantities[$i])) {
                        $stmt = $pdo->prepare("INSERT INTO menu_item_ingredients (menu_item_id, inventory_item_id, quantity) 
                                              VALUES (?, ?, ?)");
                        $stmt->execute([$item_id, $ingredient_ids[$i], $ingredient_quantities[$i]]);
                    }
                }
            }
            
            setFlashMessage('success', 'Menu item updated successfully');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error updating menu item: ' . $e->getMessage());
        }
        
        header('Location: menu.php');
        exit();
    }
    
    // Delete menu item
    if (isset($_POST['delete_item'])) {
        $item_id = $_POST['item_id'];
        
        try {
            // First get the image to delete it
            $stmt = $pdo->prepare("SELECT image FROM menu_items WHERE id = ?");
            $stmt->execute([$item_id]);
            $item = $stmt->fetch();
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Delete menu item ingredients
            $stmt = $pdo->prepare("DELETE FROM menu_item_ingredients WHERE menu_item_id = ?");
            $stmt->execute([$item_id]);
            
            // Delete menu item
            $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->execute([$item_id]);
            
            // Commit transaction
            $pdo->commit();
            
            // Delete image file if exists
            if (!empty($item['image']) && file_exists($item['image'])) {
                unlink($item['image']);
            }
            
            setFlashMessage('success', 'Menu item deleted successfully');
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            setFlashMessage('error', 'Error deleting menu item: ' . $e->getMessage());
        }
        
        header('Location: menu.php');
        exit();
    }
    
    // Add category
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['category_name']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, type) VALUES (?, 'menu')");
            $stmt->execute([$name]);
            setFlashMessage('success', 'Category added successfully');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error adding category: ' . $e->getMessage());
        }
        
        header('Location: menu.php');
        exit();
    }
    
    // Edit category
    if (isset($_POST['edit_category'])) {
        $category_id = $_POST['category_id'];
        $name = trim($_POST['category_name']);
        
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ? AND type = 'menu'");
            $stmt->execute([$name, $category_id]);
            setFlashMessage('success', 'Category updated successfully');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error updating category: ' . $e->getMessage());
        }
        
        header('Location: menu.php');
        exit();
    }
    
    // Delete category
    if (isset($_POST['delete_category'])) {
        $category_id = $_POST['category_id'];
        
        try {
            // Check if category has menu items
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $itemCount = $stmt->fetchColumn();
            
            if ($itemCount > 0) {
                setFlashMessage('error', 'Cannot delete category: It contains menu items');
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND type = 'menu'");
                $stmt->execute([$category_id]);
                setFlashMessage('success', 'Category deleted successfully');
            }
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error deleting category: ' . $e->getMessage());
        }
        
        header('Location: menu.php');
        exit();
    }
    
    // Update menu item availability
    if (isset($_POST['update_availability'])) {
        $item_id = $_POST['item_id'];
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("UPDATE menu_items SET is_available = ? WHERE id = ?");
            $stmt->execute([$is_available, $item_id]);
            setFlashMessage('success', 'Menu item updated successfully');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error updating menu item: ' . $e->getMessage());
        }
        
        header('Location: menu.php');
        exit();
    }
}

// Get menu items
try {
    $stmt = $pdo->prepare("SELECT m.*, c.name as category_name 
                          FROM menu_items m 
                          LEFT JOIN categories c ON m.category_id = c.id 
                          ORDER BY c.name, m.name");
    $stmt->execute();
    $menuItems = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error fetching menu items: ' . $e->getMessage());
    $menuItems = [];
}

// Get menu categories
try {
    $stmt = $pdo->prepare("SELECT c.*, COUNT(m.id) as item_count 
                          FROM categories c 
                          LEFT JOIN menu_items m ON c.id = m.category_id 
                          WHERE c.type = 'menu' 
                          GROUP BY c.id
                          ORDER BY c.name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error fetching categories: ' . $e->getMessage());
    $categories = [];
}

// Get inventory items for ingredients
try {
    $stmt = $pdo->prepare("SELECT * FROM inventory_items ORDER BY name");
    $stmt->execute();
    $inventoryItems = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error fetching inventory items: ' . $e->getMessage());
    $inventoryItems = [];
}

// Include header
include_once 'includes/header.php';
?>

<h1 class="page-title">Menu Management</h1>

<?php echo displayFlashMessage(); ?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                Categories
                <button type="button" class="btn" onclick="openModal('addCategoryModal')">
                    <i class="fas fa-folder-plus"></i> Add Category
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Items</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">No categories found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo $category['item_count']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($category['item_count'] == 0): ?>
                                                <form method="post" action="" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                    <button type="submit" name="delete_category" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-danger" disabled title="Cannot delete category with items">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                Menu Items
                <button type="button" class="btn" onclick="openModal('addItemModal')">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            </div>
            <div class="card-body">
                <div class="item-grid">
                    <?php if (empty($menuItems)): ?>
                        <p class="text-center">No menu items found</p>
                    <?php else: ?>
                        <?php foreach ($menuItems as $item): ?>
                            <div class="item-card">
                                <div class="item-card-header">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                    <span class="badge <?php echo $item['is_available'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $item['is_available'] ? 'Available' : 'Not Available'; ?>
                                    </span>
                                </div>
                                <div class="item-card-body">
                                    <?php if ($item['image']): ?>
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/150" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                                    <?php endif; ?>
                                    
                                    <p><strong>Category:</strong> <?php echo htmlspecialchars($item['category_name']); ?></p>
                                    <p><strong>Price:</strong> <?php echo number_format($item['price'], 2); ?></p>
                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($item['description']); ?></p>
                                    
                                    <form action="menu.php" method="post">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <div class="form-group">
                                            <label class="form-check">
                                                <input type="checkbox" name="is_available" <?php echo $item['is_available'] ? 'checked' : ''; ?>>
                                                Available
                                            </label>
                                        </div>
                                        <button type="submit" name="update_availability" class="btn btn-sm">Update</button>
                                    </form>
                                    
                                    <div class="item-actions mt-2">
                                        <button class="btn btn-sm btn-info" onclick="editItem(<?php echo $item['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="post" action="" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this menu item?');">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" name="delete_item" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal" id="addItemModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Menu Item</h5>
                <button type="button" class="close" onclick="closeModal('addItemModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="menu.php" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name" class="form-label">Item Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id" class="form-label">Category</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="image" class="form-label">Image</label>
                        <input type="file" id="image" name="image" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-check">
                            <input type="checkbox" name="is_available" checked>
                            Available
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Ingredients</label>
                        <div id="ingredients-container">
                            <div class="ingredient-row">
                                <div class="row">
                                    <div class="col-md-6">
                                        <select name="ingredient_id[]" class="form-control">
                                            <option value="">Select Ingredient</option>
                                            <?php foreach ($inventoryItems as $item): ?>
                                                <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?> (<?php echo htmlspecialchars($item['unit']); ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="number" name="ingredient_quantity[]" class="form-control" step="0.01" min="0" placeholder="Quantity">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger btn-sm remove-ingredient">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" id="add-ingredient" class="btn btn-sm">
                            <i class="fas fa-plus"></i> Add Ingredient
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_item" class="btn btn-block">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div class="modal" id="editItemModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Menu Item</h5>
                <button type="button" class="close" onclick="closeModal('editItemModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="menu.php" method="post" enctype="multipart/form-data" id="editItemForm">
                    <input type="hidden" name="item_id" id="edit_item_id">
                    
                    <div class="form-group">
                        <label for="edit_name" class="form-label">Item Name</label>
                        <input type="text" id="edit_name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_category_id" class="form-label">Category</label>
                        <select id="edit_category_id" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_price" class="form-label">Price</label>
                        <input type="number" id="edit_price" name="price" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_image" class="form-label">Image</label>
                        <div id="current_image_container" class="mb-2"></div>
                        <input type="file" id="edit_image" name="image" class="form-control">
                        <small class="form-text text-muted">Leave empty to keep current image</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-check">
                            <input type="checkbox" name="is_available" id="edit_is_available">
                            Available
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Ingredients</label>
                        <div id="edit_ingredients_container">
                            <!-- Ingredients will be loaded dynamically -->
                        </div>
                        <button type="button" id="edit_add_ingredient" class="btn btn-sm">
                            <i class="fas fa-plus"></i> Add Ingredient
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="edit_item" class="btn btn-block">Update Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal" id="addCategoryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Category</h5>
                <button type="button" class="close" onclick="closeModal('addCategoryModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="menu.php" method="post">
                    <div class="form-group">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input type="text" id="category_name" name="category_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_category" class="btn btn-block">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal" id="editCategoryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="close" onclick="closeModal('editCategoryModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="menu.php" method="post">
                    <input type="hidden" name="category_id" id="edit_category_id_hidden">
                    <div class="form-group">
                        <label for="edit_category_name" class="form-label">Category Name</label>
                        <input type="text" id="edit_category_name" name="category_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="edit_category" class="btn btn-block">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Function to open modal
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

// Function to close modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Edit category
function editCategory(categoryId, categoryName) {
    document.getElementById('edit_category_id_hidden').value = categoryId;
    document.getElementById('edit_category_name').value = categoryName;
    openModal('editCategoryModal');
}

// Edit menu item
function editItem(itemId) {
    // Fetch item details via AJAX
    fetch('get_menu_item.php?id=' + itemId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = data.item;
                document.getElementById('edit_item_id').value = item.id;
                document.getElementById('edit_name').value = item.name;
                document.getElementById('edit_category_id').value = item.category_id;
                document.getElementById('edit_description').value = item.description;
                document.getElementById('edit_price').value = item.price;
                document.getElementById('edit_is_available').checked = item.is_available == 1;
                
                // Display current image if exists
                const imageContainer = document.getElementById('current_image_container');
                imageContainer.innerHTML = '';
                if (item.image) {
                    const img = document.createElement('img');
                    img.src = item.image;
                    img.alt = item.name;
                    img.style.maxWidth = '100px';
                    img.style.maxHeight = '100px';
                    imageContainer.appendChild(img);
                }
                
                // Load ingredients
                const ingredientsContainer = document.getElementById('edit_ingredients_container');
                ingredientsContainer.innerHTML = '';
                
                if (item.ingredients && item.ingredients.length > 0) {
                    item.ingredients.forEach(ingredient => {
                        addIngredientRow('edit_ingredients_container', ingredient.inventory_item_id, ingredient.quantity);
                    });
                } else {
                    // Add empty ingredient row if no ingredients
                    addIngredientRow('edit_ingredients_container');
                }
                
                openModal('editItemModal');
            } else {
                alert('Error loading menu item: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading menu item data.');
        });
}

// Function to add ingredient row
function addIngredientRow(containerId, selectedId = '', quantity = '') {
    const container = document.getElementById(containerId);
    const row = document.createElement('div');
    row.className = 'ingredient-row mb-2';
    
    // Create row HTML with inventory items
    row.innerHTML =`
        <div class="row">
            <div class="col-md-6">
                <select name="ingredient_id[]" class="form-control">
                    <option value="">Select Ingredient</option>
                    <?php foreach ($inventoryItems as $item): ?>
                        <option value="<?php echo $item['id']; ?>">${selectedId == <?php echo $item['id']; ?> ? 'selected' : ''}>
                        <?php echo htmlspecialchars($item['name']); ?> (<?php echo htmlspecialchars($item['unit']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <input type="number" name="ingredient_quantity[]" class="form-control" step="0.01" min="0" placeholder="Quantity" value="${quantity}">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-sm remove-ingredient">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(row);
    
    // Add event listener to remove button
    row.querySelector('.remove-ingredient').addEventListener('click', function() {
        container.removeChild(row);
    });
}

// Add ingredient button for add item form
document.getElementById('add-ingredient').addEventListener('click', function() {
    addIngredientRow('ingredients-container');
});

// Add ingredient button for edit item form
document.getElementById('edit_add_ingredient').addEventListener('click', function() {
    addIngredientRow('edit_ingredients_container');
});

// Add event listener for initial remove ingredient buttons
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.remove-ingredient').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('.ingredient-row');
            row.parentNode.removeChild(row);
        });
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        document.querySelectorAll('.modal').forEach(modal => {
            if (event.target === modal) {
                closeModal(modal.id);
            }
        });
    });
});
</script>

<style>
/* Grid layout for menu items */
.item-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.item-card {
    border: 1px solid #ddd;
    border-radius: 5px;
    overflow: hidden;
}

.item-card-header {
    background-color: #f8f9fa;
    padding: 10px;
    font-weight: bold;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.item-card-body {
    padding: 15px;
}

.item-image {
    width: 100%;
    height: 150px;
    object-fit: cover;
    margin-bottom: 10px;
}

.item-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
}

/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}

.modal-dialog {
    margin: 50px auto;
    max-width: 600px;
}

.modal-content {
    background-color: #fff;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #ddd;
}

.modal-body {
    padding: 15px;
}

.close {
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
}

/* Badge styles */
.badge {
    padding: 5px 8px;
    border-radius: 3px;
    font-size: 12px;
}

.badge-success {
    background-color: #28a745;
    color: white;
}

.badge-danger {
    background-color: #dc3545;
    color: white;
}
</style>

<?php include_once 'includes/footer.php'; ?>
<?php 
$current_page = basename($_SERVER['PHP_SELF']);
?>