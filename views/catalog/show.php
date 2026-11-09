<?php
/**
 * @var array $product
 * @var list<array{property: string, value: string}> $attributes
 * @var array|null $currentUser
 */
$stock = (int) $product['stock'];
?>
<a href="<?= url('/') ?>" class="back-link">← Volver al catálogo</a>

<article class="product-detail">
    <div class="product-detail__media">
        <img src="<?= product_image($product['image_path'], 'high') ?>" alt="<?= e($product['name']) ?>">
    </div>

    <div class="product-detail__info">
        <h1><?= e($product['name']) ?></h1>
        <p class="product-detail__price"><?= money($product['price']) ?></p>

        <?php if ($product['description']): ?>
            <p><?= e($product['description']) ?></p>
        <?php endif; ?>

        <?php if ($attributes !== []): ?>
            <dl class="attribute-list">
                <?php foreach ($attributes as $attribute): ?>
                    <dt><?= e($attribute['property']) ?></dt>
                    <dd><?= e($attribute['value']) ?></dd>
                <?php endforeach; ?>
                <dt>Envío</dt>
                <dd><?= money($product['shipping_cost']) ?></dd>
            </dl>
        <?php endif; ?>

        <p class="muted"><?= $stock > 0 ? "{$stock} unidades disponibles" : 'Sin stock' ?></p>

        <?php if ($stock === 0): ?>
            <button class="button" disabled>Agotado</button>
        <?php elseif ($currentUser): ?>
            <form method="post" action="<?= url('/cart/items') ?>" class="add-to-cart">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <label for="quantity" class="visually-hidden">Cantidad</label>
                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= $stock ?>">
                <button type="submit" class="button button--primary">Añadir al carrito</button>
            </form>
        <?php else: ?>
            <a href="<?= url('/login') ?>" class="button button--primary">Inicia sesión para comprar</a>
        <?php endif; ?>
    </div>
</article>
