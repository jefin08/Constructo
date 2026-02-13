# Constructo - Construction Material Management System

Constructo is a comprehensive e-commerce and management platform designed specifically for the construction industry. It streamlines the process of buying and selling construction materials, managing inventory, and handling orders efficiently.

## 🚀 Overview

This project provides a robust solution for construction material vendors and customers. It features a modern, responsive user interface for product browsing and purchasing, alongside a powerful admin dashboard for inventory and order management.

## ✨ Key Features

### For Customers (User Side)
- **Product Catalog**: extensive browsing of construction materials with filtering by category, brand, and search.
- **User Authentication**: Secure login and registration system.
- **Shopping Cart & Checkout**: Seamless "add to cart" functionality with a detailed checkout process including GST and shipping calculations.
- **Order Management**: track order status, view order history, and **download professional PDF invoices**.
- **Wishlist**: Save favorite items for later.
- **Responsive Design**: Optimized for desktops, tablets, and mobile devices.

### For Administrators (Admin Side)
- **Dashboard**: Overview of total products, orders, and key metrics.
- **Inventory Management**: Add, edit, and delete products including stock levels, pricing, and images.
- **Order Processing**: View and manage customer orders (status updates: Processing, Delivered, Cancelled).
- **Advanced Filtering**: Quickly find products by category, brand, or stock status (In Stock, Low Stock, Out of Stock).
- **User Management**: View client details.

## 🛠️ Technology Stack

- **Backend**: PHP (Native)
- **Database**: PostgreSQL
- **Frontend**: HTML5, CSS3 (Custom Modern Design System), JavaScript (Vanilla)
- **PDF Generation**: FPDF Library
- **Architecture**: MVC-inspired structure

## ⚙️ Setup & Installation

1.  **Clone the Repository**:
    ```bash
    git clone https://github.com/yourusername/constructo.git
    cd constructo
    ```

2.  **Database Setup**:
    - Ensure you have PostgreSQL installed.
    - Import the initial database schema (if provided) or set up tables for `products`, `users`, `orders`, etc.

3.  **Environment Configuration**:
    - Create a `.env` file in the root directory.
    - specific your database credentials:
      ```env
      DB_HOST=localhost
      DB_PORT=5432
      DB_NAME=constructo
      DB_USER=your_postgres_user
      DB_PASSWORD=your_postgres_password
      ```

4.  **Run the Application**:
    - Start a local PHP server:
      ```bash
      php -S localhost:8000
      ```
    - Access the app at `http://localhost:8000`.

## 📦 Project Structure

- `admin/`: Admin dashboard and management scripts.
- `user/`: Customer-facing pages (Storefront, Cart, Orders).
- `images/`: Product and system images.
- `fpdf/`: Library for PDF generation.
- `db_connect.php`: Database connection handler.

---
*Built with ❤️ for the Construction Industry.*
