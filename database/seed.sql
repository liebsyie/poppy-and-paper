USE poppy_paper_db;

INSERT INTO categories (category_name) VALUES
('Writing'),
('Journaling'),
('Accessories')
ON DUPLICATE KEY UPDATE category_name = VALUES(category_name);

INSERT INTO products
(category_id, product_name, description, price, stock_quantity, image_path, status)
SELECT c.category_id, seed.product_name, seed.description, seed.price,
       seed.stock_quantity, seed.image_path, seed.status
FROM (
    SELECT 'Writing' AS category_name, 'Pastel Gel Pen Set' AS product_name, 'A set of smooth-writing pastel gel pens.' AS description, 89.00 AS price, 25 AS stock_quantity, 'assets/images/products/pastel-gel-pens.jpg' AS image_path, 'active' AS status
    UNION ALL SELECT 'Writing', 'Dreamy Highlighter Set', 'Soft-color highlighters for notes and planners.', 129.00, 18, 'assets/images/products/highlighters.jpg', 'active'
    UNION ALL SELECT 'Writing', 'Mini Marker Collection', 'A colorful mini marker set for creative notes.', 109.00, 20, 'assets/images/products/mini-markers.jpg', 'active'
    UNION ALL SELECT 'Writing', 'Classic Pencil Bundle', 'A handy bundle of everyday writing pencils.', 59.00, 30, 'assets/images/products/pencil-bundle.jpg', 'active'
    UNION ALL SELECT 'Journaling', 'Floral Notebook', 'A pretty notebook for thoughts and ideas.', 149.00, 15, 'assets/images/products/floral-notebook.jpg', 'active'
    UNION ALL SELECT 'Journaling', 'Daily Planner', 'A simple planner for organizing your week.', 179.00, 12, 'assets/images/products/daily-planner.jpg', 'active'
    UNION ALL SELECT 'Journaling', 'Kawaii Sticker Pack', 'Cute stickers to decorate your journal.', 59.00, 40, 'assets/images/products/sticker-pack.jpg', 'active'
    UNION ALL SELECT 'Journaling', 'Mini Sticky Notes', 'Colorful sticky notes for quick reminders.', 49.00, 0, 'assets/images/products/sticky-notes.jpg', 'active'
    UNION ALL SELECT 'Accessories', 'Cloud Pencil Case', 'A soft pastel pencil case for stationery essentials.', 159.00, 14, 'assets/images/products/cloud-pencil-case.jpg', 'active'
    UNION ALL SELECT 'Accessories', 'Floral Washi Tape Set', 'Decorative tapes for journals and crafts.', 99.00, 22, 'assets/images/products/washi-tape.jpg', 'active'
    UNION ALL SELECT 'Accessories', 'Daisy Bookmark Set', 'A set of floral bookmarks for book lovers.', 69.00, 28, 'assets/images/products/daisy-bookmarks.jpg', 'active'
    UNION ALL SELECT 'Accessories', 'Pastel Desk Organizer', 'A compact organizer for a tidy workspace.', 199.00, 8, 'assets/images/products/desk-organizer.jpg', 'inactive'
) AS seed
JOIN categories c ON c.category_name = seed.category_name
WHERE NOT EXISTS (
    SELECT 1 FROM products p WHERE p.product_name = seed.product_name
);
