-- ---------------------------------------------------------------------------
-- TCG Market — database schema
-- Target: MySQL 8 / MariaDB 10.4+ (XAMPP)
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS album_items;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS product_property_values;
DROP TABLE IF EXISTS product_categories;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS category_properties;
DROP TABLE IF EXISTS property_values;
DROP TABLE IF EXISTS properties;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- Users ---------------------------------------------------------------------

CREATE TABLE users (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email                 VARCHAR(255) NOT NULL,
    password_hash         VARCHAR(255) NOT NULL,
    role                  ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    is_active             TINYINT(1) NOT NULL DEFAULT 0,
    activation_token_hash CHAR(64) NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_activation_token (activation_token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catalog taxonomy -----------------------------------------------------------

CREATE TABLE categories (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50)  NOT NULL,
    name VARCHAR(100) NOT NULL,
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE properties (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50)  NOT NULL,
    name VARCHAR(100) NOT NULL,
    UNIQUE KEY uq_properties_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE property_values (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id INT UNSIGNED NOT NULL,
    code        VARCHAR(50)  NOT NULL,
    label       VARCHAR(100) NOT NULL,
    UNIQUE KEY uq_property_values_code (property_id, code),
    CONSTRAINT fk_property_values_property
        FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Which properties are offered as filters for each category.
CREATE TABLE category_properties (
    category_id INT UNSIGNED NOT NULL,
    property_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (category_id, property_id),
    CONSTRAINT fk_category_properties_category
        FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE,
    CONSTRAINT fk_category_properties_property
        FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products -------------------------------------------------------------------

-- `external_id` identifies products imported from an external catalog
-- (e.g. `tcgdex:base1-4`) so re-running an import updates instead of duplicating.
-- `image_path` is either a path under public/ or an absolute remote URL.
CREATE TABLE products (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    external_id   VARCHAR(64)   NULL,
    name          VARCHAR(255)  NOT NULL,
    description   TEXT          NULL,
    price         DECIMAL(10,2) NOT NULL,
    shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock         INT UNSIGNED  NOT NULL DEFAULT 0,
    image_path    VARCHAR(255)  NOT NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_products_external_id (external_id),
    KEY idx_products_price (price),
    KEY idx_products_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_categories (
    product_id  INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (product_id, category_id),
    KEY idx_product_categories_category (category_id),
    CONSTRAINT fk_product_categories_product
        FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT fk_product_categories_category
        FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_property_values (
    product_id        INT UNSIGNED NOT NULL,
    property_value_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (product_id, property_value_id),
    KEY idx_product_property_values_value (property_value_id),
    CONSTRAINT fk_product_property_values_product
        FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT fk_product_property_values_value
        FOREIGN KEY (property_value_id) REFERENCES property_values (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shopping cart --------------------------------------------------------------
-- Adding an item reserves stock until `reserved_until`; expired reservations
-- are returned to stock by CartService::releaseExpiredReservations().

CREATE TABLE cart_items (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED NOT NULL,
    product_id     INT UNSIGNED NOT NULL,
    quantity       INT UNSIGNED NOT NULL,
    reserved_until DATETIME     NOT NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cart_items_user_product (user_id, product_id),
    KEY idx_cart_items_reserved_until (reserved_until),
    CONSTRAINT fk_cart_items_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_product
        FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders ---------------------------------------------------------------------

CREATE TABLE orders (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED  NOT NULL,
    recipient_name   VARCHAR(150)  NOT NULL,
    shipping_address VARCHAR(255)  NOT NULL,
    city             VARCHAR(100)  NOT NULL,
    postal_code      VARCHAR(20)   NOT NULL,
    country          VARCHAR(100)  NOT NULL,
    subtotal         DECIMAL(10,2) NOT NULL,
    shipping_total   DECIMAL(10,2) NOT NULL,
    total            DECIMAL(10,2) NOT NULL,
    status           ENUM('pending', 'shipped') NOT NULL DEFAULT 'pending',
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_orders_user (user_id),
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Name and prices are copied at purchase time so the order history is
-- unaffected by later catalog changes or product deletion.
CREATE TABLE order_items (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id      INT UNSIGNED  NOT NULL,
    product_id    INT UNSIGNED  NULL,
    product_name  VARCHAR(255)  NOT NULL,
    unit_price    DECIMAL(10,2) NOT NULL,
    shipping_cost DECIMAL(10,2) NOT NULL,
    quantity      INT UNSIGNED  NOT NULL,
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collector album ------------------------------------------------------------
-- Purchased boosters and cards; boosters can be opened to obtain cards.

CREATE TABLE album_items (
    user_id    INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity   INT UNSIGNED NOT NULL,
    updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, product_id),
    CONSTRAINT fk_album_items_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_album_items_product
        FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
