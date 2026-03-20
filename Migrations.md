# Database Migrations Guide

This project uses a hybrid approach to database migrations:
1. **Baseline Setup:** A custom `migrate.php` script to build the entire database from scratch.
2. **Incremental Migrations:** **Phinx** is used for future, incremental changes to the schema without losing data.

---

## 1. Initial Setup (Baseline Migration)

If you are setting up the project for the first time, or if you want to completely wipe the database and start fresh with the baseline schema and seed data, use the `migrate.php` script.

**Warning:** Running this script will execute `DROP DATABASE IF EXISTS rbi_engineering;`, destroying all current data.

### How to run the baseline migration:

**Via Browser:**
Open your web browser and navigate to:
`http://localhost/rbi/migrate.php`

**Via Command Line (CLI):**
Navigate to the project root and run:
```bash
cd c:\xampp\htdocs\rbi
php migrate.php
```

This script sequentially executes:
1. `database/schema.sql` (Creates DB and core tables)
2. `database/ml_tables.sql` (Machine Learning tables)
3. `database/integration_tables.sql` (SAP/Maximo integration tables)
4. `database/seed_data.sql` (Sample dashboard data)

---

## 2. Incremental Migrations (Phinx)

Once your application is in production or you have data you don't want to lose, you should **never** run `migrate.php`. Instead, use **Phinx** to generate version-controlled incremental migrations.

Phinx is already configured in the project (`phinx.php`).

### A. Creating a New Migration

When you need to add a new table, add a column, or alter an existing schema, generate a new migration file.

```bash
php vendor/bin/phinx create AddStatusToNotifications
```

This will create a new PHP file in the `db/migrations/` directory (e.g., `20231024123456_add_status_to_notifications.php`).

Open that file and define your schema changes in the `change()` method:

```php
public function change(): void
{
    $table = $this->table('notifications');
    $table->addColumn('status', 'string', ['default' => 'unread'])
          ->update();
}
```

### B. Running Migrations

To apply all pending migrations to your database, run:

```bash
php vendor/bin/phinx migrate
```

### C. Rolling Back Migrations

If you made a mistake and need to undo the last migration, run:

```bash
php vendor/bin/phinx rollback
```
To roll back multiple steps, you can pass the target date or version as an argument (see Phinx Documentation).