# Poppy & Paper 🌷

**Little things, lovely days.**

### Developed by

**Lovely Joromo**  
Bachelor of Science in Information Technology  
Cebu Technological University – Danao Campus

---

Poppy & Paper is a fictional pastel-kawaii stationery e-commerce website developed for the **CubeTech Innovations Web Development Internship Assessment**.

The project demonstrates a basic e-commerce workflow, including product browsing, shopping cart management, checkout, order processing, and administrative management.

## 1. Technology Stack

| Technology | Purpose |
|---|---|
| HTML5 | Website structure |
| CSS3 | Custom styling |
| Bootstrap 5.3.3 | Responsive layout and components |
| JavaScript | Client-side interactions |
| PHP 8+ | Server-side application logic |
| MySQL | Relational database |
| PDO | Database connectivity and prepared statements |
| XAMPP | Local development environment |

## 2. Features

### Customer Side

- Responsive homepage and product catalog
- Product browsing and product details
- Product search
- Category filtering
- Price sorting
- Product availability and stock indicators
- Session-based shopping cart
- Add, update, and remove cart items
- Checkout with customer and delivery information
- Server-side stock and price validation
- Order creation and order confirmation

### Admin Side

- Admin authentication and logout
- Dashboard overview and statistics
- Product management (Create, Read, Update, Delete)
- Category management
- Order management and status updates
- Order details
- Customer summary and search

### Order Management

The order workflow supports the following statuses:

1. Pending
2. Confirmed
3. Preparing
4. Shipped
5. Completed

Eligible orders can also be cancelled. Stock restoration is handled through the order cancellation workflow.

## 3. Project Structure

```text
poppy-and-paper/
│
├── actions/
│   ├── cart_add.php
│   ├── category-actions.php
│   ├── order-actions.php
│   ├── place-order.php
│   ├── product-actions.php
│   └── update-cart.php
│
├── admin/
│   ├── categories.php
│   ├── customers.php
│   ├── dashboard.php
│   ├── login.php
│   ├── logout.php
│   ├── order-details.php
│   ├── orders.php
│   └── products.php
│
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── fonts/
│   ├── images/
│   │   ├── banners/
│   │   ├── branding/
│   │   └── products/
│   └── js/
│       ├── admin.js
│       ├── cart.js
│       └── main.js
│
├── config/
│   └── database.php
│
├── database/
│   ├── schema.sql
│   └── seed.sql
│
├── includes/
│   ├── admin-sidebar.php
│   ├── auth.php
│   ├── footer.php
│   ├── functions.php
│   ├── header.php
│   └── navbar.php
│
├── cart.php
├── checkout.php
├── confirmation.php
├── index.php
├── product.php
├── shop.php
└── README.md
```

## 4. Local Installation and Setup

### Prerequisites

Make sure the following are installed:

- XAMPP
- PHP 8 or later
- MySQL or MariaDB
- A modern web browser

### Installation Steps

1. Clone the repository:

   ```bash
   git clone https://github.com/LIEBSYIE/poppy-and-paper.git
   ```

2. Place the project folder inside:

   ```text
   C:\xampp\htdocs\
   ```

3. Open the XAMPP Control Panel.

4. Start **Apache** and **MySQL**.

5. Open phpMyAdmin:

   ```text
   http://localhost/phpmyadmin/
   ```

6. Create a database named:

   ```text
   poppy_paper_db
   ```

7. Select the newly created database.

8. Import the following SQL files in order:
   - `database/schema.sql`
   - `database/seed.sql`

9. Open `config/database.php` and verify the database connection settings. Update the credentials if necessary.

10. Open the application in your browser:

    ```text
    http://localhost/poppy-and-paper/
    ```

## 5. Database Information

**Database Name:** `poppy_paper_db`

The application uses the following main database tables:

| Table | Description |
|---|---|
| `categories` | Stores product categories |
| `products` | Stores product information, prices, stock, and availability |
| `orders` | Stores customer orders and order information |
| `order_items` | Stores the products and purchase details associated with each order |
| `admin_users` | Stores administrator account information |

The database structure and initial demonstration data are provided through the SQL files in the `database/` directory.

## 6. Demo Data

The application includes fictional stationery products across multiple categories.

All product names, descriptions, prices, customer information, and contact details are intended for demonstration purposes only.

The database seed file provides the initial product and category data.

## 7. Authentication and Payment Notice

This project is intended for assessment and demonstration purposes.

- Administrative access requires valid administrator credentials.
- Customer checkout is demonstrated without an integrated online payment gateway.
- No actual financial transactions are processed.
- The application is designed for local development and is not intended for production deployment without additional security review.

## 8. Testing and Verification

The project is undergoing functional and responsive testing.

The final assessment documentation may include the following supporting evidence:

- Functional test results
- Desktop and mobile screenshots
- Application demonstration video
- System flow documentation
- Database documentation

## 9. AI and External Tool Disclosure

AI-assisted tools were used during development for selected coding assistance, troubleshooting, documentation, and product image creation.

The developer reviewed, integrated, and tested the project components as part of the development process.

Specific tools, templates, tutorials, and external resources used should be identified accurately in the final assessment submission.

## 10. Project Status

Poppy & Paper has its main customer shopping workflow and administrative modules implemented.

Remaining development activities include:

- Final code cleanup
- User interface improvements
- Functional and responsive verification
- Assessment documentation
- Preparation of supporting screenshots and demonstration materials

## 11. Developer

**Lovely Joromo**  
Bachelor of Science in Information Technology  
Cebu Technological University – Danao Campus

Developed as part of the CubeTech Innovations Web Development Internship Assessment.

---

**Poppy & Paper**  
*Little things, lovely days. 🌷*