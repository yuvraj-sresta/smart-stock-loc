# Smart Stock – Inventory Management System
**ICT313 IT Engaged Project | Group Capstone**

## Quick Start (XAMPP)

1. Copy this folder to `C:\xampp\htdocs\smart-stock`
2. Start Apache and MySQL in XAMPP Control Panel
3. Open phpMyAdmin → Import `database/schema.sql`
4. Visit `http://localhost/smart-stock/database/seed_users.php` to set real password hashes
5. **Delete** `database/seed_users.php` after step 4
6. Visit `http://localhost/smart-stock/` — you'll be redirected to login

## Default Credentials
| Role  | Username | Password    |
|-------|----------|-------------|
| Admin | admin    | Admin@1234  |
| Staff | staff1   | Staff@1234  |

## Folder Structure
```
smart-stock/
├── config/         DB connection + constants
├── includes/       Shared PHP (auth, layout, functions)
├── assets/         CSS, JS, images
├── auth/           login.php, logout.php
├── dashboard/      Dashboard page
├── inventory/      Product CRUD + stock update
├── suppliers/      Supplier CRUD
├── reports/        Reports page
├── transactions/   Stock transaction log
├── errors/         403, 404 pages
└── database/       SQL schema + seeder
```

## Role Permissions
| Feature                  | Admin | Staff |
|--------------------------|:-----:|:-----:|
| Login/Logout             | ✅    | ✅    |
| Dashboard                | ✅    | ✅    |
| View Inventory           | ✅    | ✅    |
| Add/Edit/Delete Products | ✅    | ❌    |
| Update Stock Quantity    | ✅    | ✅    |
| View Suppliers           | ✅    | ❌    |
| Add/Edit/Delete Suppliers| ✅    | ❌    |
| View Reports             | ✅    | ❌    |
| View Transactions        | ✅    | ✅    |
