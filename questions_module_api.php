<?php
/***API Request***/
add_action('rest_api_init', function() {
    createPostRoute('getquestion', 'getquestion');
    createPostRoute('getproductsafterquestions', 'getproductsafterquestions');
});
/*****************/



function getproductsafterquestions($request_data){
    global $wpdb;
    $data = $request_data->get_params();

    $lang = (isset($data['lang'])) ? $data['lang'] : "en";

    $request = json_decode(base64_decode($data['request']),true);

    $tax_query = [];
    $meta_query = [
        'relation' => "OR"
    ];

    $questionObj = new Question($wpdb);
    $productsObj = new Products($wpdb);
    $questionObj::$lang = $lang;
    $productsObj::$lang = $lang;

    $types = $questionObj::getTypes();

    $finder = false;

    foreach($request as $request_item){
        $question = $questionObj->getQuestion($request_item['id_question']);
        $answer = $questionObj->getAnswer($request_item['id_answer']);

        $type = $question['type_question'];
        $value = $answer['value_answer'];
        if($value == "all") continue;

        $type = explode("/",$type);

        if(isset($types[$type[0]])){
            $type_elem = $types[$type[0]];
            if(!$type_elem['finder']) continue;
            $finder = true;

            switch($type[0]){
                case 'product_cat':{
                    $tax_query[] = [
                        'taxonomy' => "product_cat",
                        'field' => "slug",
                        'terms' => $value
                    ];
                    break;
                }
                case 'price':{
                    $meta_query[] = [
                        'key' => '_price',
                        'value' => $value,
                        'compare' => '<',
                        'type' => 'NUMERIC'
                    ];
                    break;
                }
                case 'product_tag':{
                    $tax_query[] = [
                        'taxonomy' => "product_tag",
                        'field' => "slug",
                        'terms' => $value
                    ];
                    break;
                }
                case 'attributes':{
                    $tax_query[] = [
                        'taxonomy' => $type[1],
                        'field' => "slug",
                        'terms' => $value
                    ];
                    break;
                }
            }
        }
    }

    $products = [];


    $args = [
        'post_type' => "product",
        "posts_per_page" => -1,
        'lang' => $lang,
        'tax_query' => $tax_query,
        'meta_query' => $meta_query
    ];

    $_products = new WP_Query($args);

    if ($_products->have_posts()) {
        while ($_products->have_posts()) {
            $_products->the_post();
            $products[] = [
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'img' => get_the_post_thumbnail_url(get_the_ID(),"thumbnail"),
                'price' => get_post_meta(get_the_ID(), '_price')[0]
            ];
        }
    }


    $set_products = $productsObj->getProductsFromQuestions();
    if(!empty($set_products) && count($set_products)>0){
        foreach($set_products as $s_prod){
            $products[] = [
                'id' => $s_prod['id'],
                'title' => $s_prod['title'],
                'img' => $s_prod['img'],
                'price' => $s_prod['price']
            ];
        }
    }


    if(count($products) == 0){
        return ["code" => 404, "message" => "Products not found"];
    }
    else {
        return ["code" => 200, "products" => $products];
    }
}
function getquestion($request_data)
{
    global $wpdb;
    $data = $request_data->get_params();

    $lang = $data['lang'];

    $questionObj = new Question($wpdb);
    $questionObj::$lang = $lang;

    $start = false;
    $start = $data['start'];

    $id_question = null;
    if($start) {
        $id_question_start_obj = $questionObj::getStartQuestion();
        if ($id_question_start_obj == null) return ["code" => 404];
        $id_question = $id_question_start_obj['id'];
    }
    if((isset($data['id_question']) && !empty($data['id_question'])) && (isset($data['id_answer']) && !empty($data['id_answer']))){
        $id_question = $questionObj->getQuestionByAnswer($data['id_answer']);
        if($id_question == null){
            $id_question = $questionObj->getQuestionByQuestion($data['id_question']);
            if($id_question == null){
                return ["code" => 201,"Finish"];
            }
        }
    }

    $question = $questionObj->getQuestion($id_question);
    $answers = $questionObj->getAnswers($id_question);

    $data = [
        'title' => $question['text_question'],
        'id' => $question['id'],
        'answers' => []
    ];

    foreach($answers as $answer){
        $data['answers'][] = [
            'id' => $answer['id'],
            'title' => $answer['text_answer']
        ];
    }

    return ['code'=>200,'data' => $data];
}