<?php
session_start();
/*
Plugin Name: Questions Module
Description: Questions Module
Version: 1.0
Author: sined199dev
*/

// add_filter('get_terms', function($terms, $taxonomy, $query_vars, $term_query)
// {
//     if(function_exists('pll_get_term_language') && isset($query_vars['lang'])){
//         foreach ($terms as $key => $term) {


//             if($term->taxonomy == 'mytaxonomy' && pll_get_term_language($term->term_id) !== $query_vars['lang']){
//             	file_get_contents("https://api.telegram.org/bot917088152:AAE-cWWFYuSZjuli-NRixM7V_hFrQbr8X7g/sendMessage?chat_id=332089292&text=".$term->taxonomy);
//                 unset($terms[$key]);
//             }
//         }
//         $terms = array_values($terms);
//     }
//     return $terms;
// }, 10, 4);


require_once "inc/Base.php";
require_once "inc/question.model.php";
require_once "inc/products.model.php";
//require_once "inc/Base.php";
require_once 'questions_module_api.php';
require_once "ajax_actions.php";

// Admin page
function view_plugin_page(){
	global $wpdb;

	$questionObj = new Question($wpdb);
    $productsObj = new Products($wpdb);

	$questions = $questionObj->getQuestions();

	$types = $questionObj::getTypes();

	$products = $productsObj->getProducts();
	$products_sets = $productsObj->getProductsFromQuestions();

	$plugin_name = __('Questions');

	wp_enqueue_style('styles',plugins_url('styles.css',__FILE__));
	wp_enqueue_script('qb_jquery',plugins_url('jquery.min.js',__FILE__));
	wp_enqueue_script('actions',plugins_url('actions.js',__FILE__));

	include_once("admin_page.php");
}

// Create menu item
function register_menu_page(){
	add_menu_page(
		__('Questions'), __('Questions'), 'manage_options', 'questions', 'view_plugin_page', plugins_url( '' ), 6
	);
}
add_action( 'admin_menu', 'register_menu_page' );

// Activation
function qb_activation(){
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table_name_questions = $wpdb->prefix . "q_questions";
	$table_name_answers = $wpdb->prefix . "q_answers";
	$table_questions_parent = $wpdb->prefix . "q_questions_parent";
	$table_questions_related_products = $wpdb->prefix . "q_questions_related_products";

	$check = $wpdb->query("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '".$table_name_questions."'");
	if(!$check){
		$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}";
		$sql = "CREATE TABLE {$table_name_questions} (
		id  int(10) unsigned NOT NULL auto_increment,
		type_question varchar(255) NOT NULL default '',
		text_question varchar(255) NOT NULL default '',
		lang varchar(255) NOT NULL default '',
		start varchar(255) NOT NULL default '0',
		PRIMARY KEY  (id)
		)
		{$charset_collate};";
		dbDelta($sql);
	}

	$check = $wpdb->query("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '".$table_name_answers."'");
	if(!$check){
		$sql = "CREATE TABLE {$table_name_answers} (
		id int(10) unsigned NOT NULL auto_increment,
		id_question int(10) unsigned NOT NULL,
		text_answer varchar(255) NOT NULL default '',
		value_answer varchar(255) NOT NULL default '',
		type_answer varchar(255) NOT NULL default '',
		PRIMARY KEY  (id)
		)
		{$charset_collate};";
		dbDelta($sql);
	}
	$check = $wpdb->query("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '".$table_questions_parent."'");
	if(!$check){
		$sql = "CREATE TABLE {$table_questions_parent} (
		id int(10) unsigned NOT NULL auto_increment,
		id_question int(10) unsigned NOT NULL,
		id_parent_question varchar(255) NOT NULL default '',
		id_parent_answer varchar(255) NOT NULL default '',
		PRIMARY KEY  (id)
		)
		{$charset_collate};";
		dbDelta($sql);
	}
	$check = $wpdb->query("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '".$table_questions_related_products."'");
	if(!$check){
		$sql = "CREATE TABLE {$table_questions_related_products} (
		id int(10) unsigned NOT NULL auto_increment,
		id_product int(10) unsigned NOT NULL,
		lang varchar(255) NOT NULL default 'en',
		PRIMARY KEY  (id)
		)
		{$charset_collate};";
		dbDelta($sql);
	}
}
register_activation_hook(__FILE__, "qb_activation");

function getValuesForType(){

    $type = $_POST['key'];
    $lang = $_POST['lang'];
    $response = [];

    $type = explode("/",$type);

    $questionObj = new Question();
    $questionObj::$lang = $lang;

    $allTypes = $questionObj::getTypes();
    $type_Type = $allTypes[$type[0]]['type'];

    $_type = $type[0];
    $html = "";
    if($type_Type == "default") {
        $_type = (count($type)>1) ? $type[1] : $type[0];
        $terms = $questionObj::getValuesForType($_type);
        $terms = array_column($terms, "name","slug");
        $terms = array_merge(["all" => "All"],$terms);
        $response['terms'] = $terms;

        $html .= "<select name='getOption'>";
        $html .= "<option disabled selected>Select option</option>";
        foreach($terms as $term_item_key => $term_item_value){
            $html .= "<option value='".$term_item_key."'>".$term_item_value."</option>";
        }
        $html .= "</select>";

        $html .= "<button id='addAnswer'>Add answer</button>";
    }
    else{
        $html .= "<input name='getOption'>";
        $html .= "<button id='addAnswer'>Add answer</button>";
    }
    $response['html'] = $html;
    $response['type'] = $_type;
    $response['type_Type'] = $type_Type;

    header("Content-Type: application/json");
    echo json_encode($response);
    die();
}

function saveQuestion()
{
    global $wpdb;

    $response = [];
    $data = $_POST['questionData'];

    $questionObj = new Question($wpdb);
    $questionObj::$lang = $data['lang'];

    $response = $questionObj->saveQuestion($data);


    if(isset($response['id']) && $response['method'] == "ADD"){
    	ob_start();

    	$data = $questionObj->getQuestion($response['id']);
    	$data['parents'] = $questionObj->getParents($response['id']);

    	require 'includes/question_preview.php';

    	$content = ob_get_contents();
    	ob_clean();

    	$response['answer_item'] = $content;
    }
    elseif($response['method'] == "UPD"){

//    	$response = $data;
    }

    header("Content-Type: application/json");
    echo json_encode($response);
    die();
}

function getQuestionData()
{
	global $wpdb;

	$response = [];
	$id = $_POST['id'];

	$questionObj = new Question($wpdb);

	$result = $questionObj->getQuestionData($id);
    $types = $questionObj->getTypes();

	$type = explode("/",$result['question']['type_question']);
    $_type = (count($type)>1) ? $type[1] : $type[0];
    $terms = $questionObj->getValuesForType($_type);
    $terms = array_column($terms, "name","slug");
    $terms = array_merge(["all" => "All"],$terms);

    $type_Type = $types[$type[0]]['type'];

    $questions = $questionObj->getQuestions();
    $childQuestion = $questionObj->getQuestionByQuestion($result['question']['id']);

    $data = [
        'id' => $result['question']['id'],
        'title' => $result['question']['text_question'],
        'type' => $result['question']['type_question'],
        'type_Type' => $type_Type,
        'start' => $result['question']['start'],
        'lang' => $result['question']['lang'],
        'answers' => filterAnswers($result['answers']),
        'parents' => array_map('filterParents',$result['parents']),
        'questions' => $questions,
        'childQuestion' => $childQuestion
    ];

	ob_start();
	require 'includes/question_data.php';
	$content = ob_get_contents();
	ob_clean();

	$response = [
		'html' => $content,
		'data' => $data,
		'terms'=> $terms,
        'types' => $types
	];

	header("Content-Type: application/json");
    echo json_encode($response);
    die();
}

function setChildQuestion()
{
    global $wpdb;

    $response = [];

    $id_question = $_POST['id_question'];
    $id_answer = $_POST['id_answer'];
    $id_child_question = $_POST['id_child_question'];

    $questionObj = new Question($wpdb);
    $id_old_question = $questionObj->changeQuestionParent($id_question,$id_answer,$id_child_question);
    $id_new_question = $id_child_question;

    $result = [];

    if($id_old_question != 0) {
        $parents_str = join(",", array_column($questionObj->getParents($id_old_question), "id_parent_question"));
        $result[] = [
            'id' => $id_old_question,
            'parents_str' => $parents_str
        ];
    }
    if($id_new_question != 0) {
        $parents_str = join(",", array_column($questionObj->getParents($id_new_question), "id_parent_question"));
        $result[] = [
            'id' => $id_new_question,
            'parents_str' => $parents_str
        ];
    }

    $response['result'] = $result;


    header("Content-Type: application/json");
    echo json_encode($response);
    die();
}

function deleteQuestion()
{
    global $wpdb;
    $response = [];

    $id_question = $_POST['id'];

    $questionObj = new Question($wpdb);

    $questionObj->deleteQuestion($id_question);

    $response = ["success" => true];

    header("Content-Type: application/json");
    echo json_encode($response);
    die();
}

function filterAnswers($arr)
{
    global $wpdb;
    $questionObj = new Question($wpdb);

	$return = [];
	foreach($arr as $arr_value){
	    $childQuestion = $questionObj->getQuestionByAnswer($arr_value['id']);
		$return[base64_encode($arr_value['value_answer'])] = [
			'id' => $arr_value['id'],
			'value' => $arr_value['value_answer'],
			'name' => $arr_value['text_answer'],
			'type' => $arr_value['type_answer'],
            'childQuestion' => ($childQuestion != null) ? $childQuestion : null
		];
	}
	return $return;
}

function filterParents($elem)
{
	return [
		'id_question' => $elem['id_parent_question'],
		'id_answer' => $elem['id_parent_answer']
	];
}

function changeLang()
{
    $response = [];
    global $wpdb;

    $questionObj = new Question($wpdb);
    $productsObj = new Products($wpdb);

    $questionObj::$lang = $_POST['lang'];
    $productsObj::$lang = $_POST['lang'];

    $questions = $questionObj->getQuestions();
    $types = $questionObj::getTypes();
    ob_start();
        require "includes/questions_list.php";
    $html = ob_get_contents();
    ob_clean();
    $response['html_questions'] = $html;

    $products = $productsObj->getProducts();
    $products_sets = $productsObj->getProductsFromQuestions();

    ob_start();
        require "includes/products_all_list.php";
    $html = ob_get_contents();
    ob_clean();
    $response['html_all_products'] = $html;

    ob_start();
        require "includes/products_set_list.php";
    $html = ob_get_contents();
    ob_clean();
    $response['html_set_products'] = $html;

    header("Content-Type: application/json");
    echo json_encode($response);
    die();
}

function productForQuestions()
{
    $response = [];
    global $wpdb;

    $productid = (int)$_POST['productid'];
    $lang = $_POST['lang'];

    $productObj = new Products($wpdb);
    $productObj::$lang = $lang;

    $result = $productObj->getProductFromQuestions($productid);
    switch($result['status']){
        case 404:{
            $response['result'] = $productObj->addProductForQuestions($productid);
            break;
        }
        case 201:{
            $response['result'] = $productObj->removeProductFromQuestions($productid);
            break;
        }
    }

    $response['status'] = $result['status'];

    header("Content-Type: application/json");
    echo json_encode($response);
    die();
}

function removeProductFromQuestions()
{
    $response = [];
    global $wpdb;

    $productid = (int)$_POST['productid'];

    $productObj = new Products($wpdb);

    $response['result'] = $productObj->removeProductFromQuestions($productid);

    header("Content-Type: application/json");
    echo json_encode($response);
    die();
}

