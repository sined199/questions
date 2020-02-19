<?php

class Products extends Base
{
    public static $lang = "en";

    function __construct($connect = null)
    {
        if($connect != null) parent::$wpdb = $connect;
    }

    public function getProducts()
    {
        $args = [
            'post_type' => "product",
            "posts_per_page" => -1,
            'lang' => self::$lang
        ];

        $products = [];

        $productsObj = new WP_Query($args);
        if ($productsObj->have_posts()) {
            while ($productsObj->have_posts()) {
                $productsObj->the_post();
                $products[] = [
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'img' => get_the_post_thumbnail_url(get_the_ID(),"thumbnail"),
                    'link' => get_edit_post_link()
                ];
            }
        }

        return $products;
    }

    public function getProductsFromQuestions()
    {
        $products = [];
        $results = parent::$wpdb->get_results("SELECT * FROM `".self::relatedProductsTableName()."` WHERE `lang` = '".self::$lang."'");
        foreach($results as $res){
            $products[] = [
                'id' => $res->id_product,
                'title' => get_the_title($res->id_product),
                'img' => get_the_post_thumbnail_url($res->id_product,"thumbnail"),
                'link' => get_edit_post_link($res->id_product),
                'price' => get_post_meta($res->id_product, '_price')[0]
            ];
        }
        return $products;
    }

    public function getProductFromQuestions($productid)
    {
        if(empty($productid)) return ["status" => 500];

        $result = parent::$wpdb->get_results("SELECT * FROM `".self::relatedProductsTableName()."` WHERE `id_product` = '".$productid."' AND `lang` = '".self::$lang."' ");
        if(count($result) > 0) return ["status" => 201];
        return ["status" => 404];
    }

    public function addProductForQuestions($productid)
    {
        return parent::$wpdb->insert(
            self::relatedProductsTableName(),
            ["id_product" => $productid, "lang" => self::$lang],
            ["%d","%s"]
        );
    }

    public function removeProductFromQuestions($productid)
    {
        return parent::$wpdb->delete(
            self::relatedProductsTableName(),
            ["id_product" => $productid]
        );
    }

    private static function relatedProductsTableName()
    {
        return parent::$wpdb->prefix . "q_questions_related_products";
    }
}