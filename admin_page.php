<div class="wrap">
	<h1 class="wp-heading-inline"><?=$plugin_name?></h1>
	<hr class="wp-header-end">
	<div class="block_message" id="qb_message"><span><?=__('Saved')?></span></div>
    <div class="questions_block">
        <div class="questions_lang">
            <select name="lang">
                <option value="ru">RU</option>
                <option selected value="en">EN</option>
                <option value="et">ET</option>
            </select>
        </div>
        <div class="questions_list">
            <?php
                require "includes/questions_list.php";
            ?>
        </div>
        <h1 class="wp-heading-inline">Related products</h1>
        <div class="products_list">
            <div class="products_all_list">
                <?php
                    require "includes/products_all_list.php";
                ?>
            </div>
            <div class="products_set_list">
                <?php
                    require "includes/products_set_list.php";
                ?>
            </div>
        </div>
    </div>
</div>