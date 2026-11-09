<?php
/**
 * @var list<array> $users
 * @var list<array> $products
 * @var list<array> $categories
 * @var array<string, string> $errors
 * @var array<string, array> $old
 * @var array $currentUser
 */
$oldUser    = $old['user'] ?? [];
$oldProduct = $old['product'] ?? [];

$lowStock   = count(array_filter($products, static fn (array $p): bool => (int) $p['stock'] <= 5));
$totalStock = array_sum(array_map(static fn (array $p): int => (int) $p['stock'], $products));
?>
<header class="page-header">
    <h1>Panel de administración</h1>
</header>

<div class="admin-stats">
    <div class="stat">
        <span class="stat__value"><?= count($users) ?></span>
        <span class="stat__label">Usuarios registrados</span>
    </div>
    <div class="stat">
        <span class="stat__value"><?= count($products) ?></span>
        <span class="stat__label">Productos en catálogo</span>
    </div>
    <div class="stat">
        <span class="stat__value"><?= $totalStock ?></span>
        <span class="stat__label">Unidades en stock</span>
    </div>
    <div class="stat">
        <span class="stat__value"><?= $lowStock ?></span>
        <span class="stat__label">Productos con 5 o menos unidades</span>
    </div>
</div>

<?= $view->partial('partials/form-errors', ['errors' => $errors]) ?>

<section class="admin-section">
    <h2>Usuarios</h2>

    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Activa</th>
                    <th>Alta</th>
                    <th><span class="visually-hidden">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= (int) $user['id'] ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td>
                            <span class="tag<?= $user['role'] === 'admin' ? ' tag--primary' : '' ?>">
                                <?= $user['role'] === 'admin' ? 'Administrador' : 'Cliente' ?>
                            </span>
                        </td>
                        <td>
                            <span class="tag<?= $user['is_active'] ? ' tag--success' : '' ?>"><?= $user['is_active'] ? 'Activa' : 'Pendiente' ?></span>
                        </td>
                        <td><?= e(date('d/m/Y', strtotime($user['created_at']))) ?></td>
                        <td class="table-actions">
                            <a href="<?= url('/admin/users/' . $user['id'] . '/cart') ?>" class="button button--small">Carrito</a>
                            <?php if ((int) $user['id'] !== (int) $currentUser['id']): ?>
                                <form method="post" action="<?= url('/admin/users/' . $user['id'] . '/delete') ?>"
                                      data-confirm="¿Eliminar al usuario <?= e($user['email']) ?>? Esta acción no se puede deshacer.">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="button button--small button--danger">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <details class="card" <?= $oldUser ? 'open' : '' ?>>
        <summary>Añadir usuario</summary>
        <form method="post" action="<?= url('/admin/users') ?>" class="form form--grid">
            <?= csrf_field() ?>
            <div class="field">
                <label for="user-email">Correo electrónico</label>
                <input type="email" id="user-email" name="email" value="<?= e($oldUser['email'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label for="user-password">Contraseña</label>
                <input type="password" id="user-password" name="password" autocomplete="new-password" required>
            </div>
            <div class="field">
                <label for="user-role">Rol</label>
                <select id="user-role" name="role">
                    <option value="customer">Cliente</option>
                    <option value="admin" <?= ($oldUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
                </select>
            </div>
            <div class="field field--action">
                <button type="submit" class="button button--primary">Crear usuario</button>
            </div>
        </form>
    </details>
</section>

<section class="admin-section">
    <h2>Productos</h2>

    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th class="text-right">Precio</th>
                    <th class="text-right">Envío</th>
                    <th class="text-right">Stock</th>
                    <th><span class="visually-hidden">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= (int) $product['id'] ?></td>
                        <td><a href="<?= url('/products/' . $product['id']) ?>"><?= e($product['name']) ?></a></td>
                        <td><?= e($product['categories'] ?? '—') ?></td>
                        <td class="text-right"><?= money($product['price']) ?></td>
                        <td class="text-right"><?= money($product['shipping_cost']) ?></td>
                        <td class="text-right">
                            <?php if ((int) $product['stock'] <= 5): ?>
                                <span class="tag tag--warning"><?= (int) $product['stock'] ?></span>
                            <?php else: ?>
                                <?= (int) $product['stock'] ?>
                            <?php endif; ?>
                        </td>
                        <td class="table-actions">
                            <form method="post" action="<?= url('/admin/products/' . $product['id'] . '/delete') ?>"
                                  data-confirm="¿Eliminar «<?= e($product['name']) ?>»? Se quitará también de carritos y álbumes.">
                                <?= csrf_field() ?>
                                <button type="submit" class="button button--small button--danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <details class="card" <?= $oldProduct ? 'open' : '' ?>>
        <summary>Añadir producto</summary>
        <form method="post" action="<?= url('/admin/products') ?>" enctype="multipart/form-data" class="form form--grid">
            <?= csrf_field() ?>
            <div class="field">
                <label for="product-name">Nombre</label>
                <input type="text" id="product-name" name="name" maxlength="255" value="<?= e($oldProduct['name'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label for="product-category">Categoría</label>
                <select id="product-category" name="category_id" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>"
                            <?= (int) ($oldProduct['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>
                            <?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="product-price">Precio (€)</label>
                <input type="number" id="product-price" name="price" step="0.01" min="0.01" value="<?= e($oldProduct['price'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label for="product-shipping">Coste de envío (€)</label>
                <input type="number" id="product-shipping" name="shipping_cost" step="0.01" min="0" value="<?= e($oldProduct['shipping_cost'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label for="product-stock">Stock</label>
                <input type="number" id="product-stock" name="stock" min="0" step="1" value="<?= e(($oldProduct['stock'] ?? -1) >= 0 ? $oldProduct['stock'] : '') ?>" required>
            </div>
            <div class="field">
                <label for="product-image">Imagen (JPG, PNG, WEBP o GIF · máx. 2 MB)</label>
                <input type="file" id="product-image" name="image" accept="image/jpeg,image/png,image/webp,image/gif" required>
            </div>
            <div class="field field--full">
                <label for="product-description">Descripción</label>
                <textarea id="product-description" name="description" rows="3"><?= e($oldProduct['description'] ?? '') ?></textarea>
            </div>
            <div class="field field--action">
                <button type="submit" class="button button--primary">Crear producto</button>
            </div>
        </form>
    </details>
</section>
