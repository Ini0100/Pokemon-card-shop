<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AlbumController;
use App\Controllers\Api\CatalogApiController;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CatalogController;
use App\Controllers\CheckoutController;
use App\Controllers\OrderController;
use App\Core\Router;

return static function (Router $router): void {
    // Catalog
    $router->get('/', [CatalogController::class, 'index']);
    $router->get('/products/{id}', [CatalogController::class, 'show']);

    // Authentication
    $router->get('/login', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login']);
    $router->post('/logout', [AuthController::class, 'logout']);
    $router->get('/register', [AuthController::class, 'showRegister']);
    $router->post('/register', [AuthController::class, 'register']);
    $router->get('/activate', [AuthController::class, 'activate']);

    // Cart & checkout
    $router->get('/cart', [CartController::class, 'index']);
    $router->post('/cart/items', [CartController::class, 'add']);
    $router->post('/cart/items/{id}', [CartController::class, 'update']);
    $router->post('/cart/items/{id}/delete', [CartController::class, 'remove']);
    $router->get('/checkout', [CheckoutController::class, 'show']);
    $router->post('/checkout', [CheckoutController::class, 'store']);

    // Customer area
    $router->get('/orders', [OrderController::class, 'index']);
    $router->get('/orders/{id}', [OrderController::class, 'show']);
    $router->get('/album', [AlbumController::class, 'index']);
    $router->post('/album/boosters/{id}/open', [AlbumController::class, 'openBooster']);

    // Administration
    $router->get('/admin', [AdminController::class, 'index']);
    $router->post('/admin/users', [AdminController::class, 'storeUser']);
    $router->post('/admin/users/{id}/delete', [AdminController::class, 'deleteUser']);
    $router->get('/admin/users/{id}/cart', [AdminController::class, 'userCart']);
    $router->post('/admin/users/{id}/cart/clear', [AdminController::class, 'clearUserCart']);
    $router->post('/admin/products', [AdminController::class, 'storeProduct']);
    $router->post('/admin/products/{id}/delete', [AdminController::class, 'deleteProduct']);

    // Public read-only JSON API
    $router->get('/api/categories', [CatalogApiController::class, 'categories']);
    $router->get('/api/categories/{id}/filters', [CatalogApiController::class, 'filters']);
    $router->get('/api/products', [CatalogApiController::class, 'products']);
    $router->get('/api/products/{id}', [CatalogApiController::class, 'product']);
};
