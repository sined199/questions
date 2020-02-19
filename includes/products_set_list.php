<?php
    if(count($products_sets) > 0) {
        foreach ($products_sets as $product) {
            ?>
            <div class="product_item" data-id="<?= $product['id'] ?>">
                <div class="product_img">
                    <img src="<?= $product['img'] ?>"/>
                </div>
                <div class="product_name">
                    <?= $product['title'] ?>
                </div>
            </div>
            <?php
        }
    }
?>