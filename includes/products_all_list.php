<?php
    foreach($products as $product){
        $active = "";
        if(count($products_sets) > 0) {
            if (in_array($product['id'], array_column($products_sets, "id"))) $active = "active";
        }
            ?>
            <div class="product_item <?=$active?>" data-id="<?=$product['id']?>">
                <div class="product_img">
                    <img src="<?=$product['img']?>" />
                </div>
                <div class="product_name">
                    <span><?=$product['title']?></span>
                </div>
            </div>
        <?php
    }
?>