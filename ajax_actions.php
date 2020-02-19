<?php
add_action("wp_ajax_getValuesForType","getValuesForType");
add_action("wp_ajax_nopriv_getValuesForType","getValuesForType");

add_action("wp_ajax_saveQuestion","saveQuestion");
add_action("wp_ajax_nopriv_saveQuestion","saveQuestion");

add_action("wp_ajax_getQuestionData","getQuestionData");
add_action("wp_ajax_nopriv_getQuestionData","getQuestionData");

add_action("wp_ajax_deleteQuestion","deleteQuestion");
add_action("wp_ajax_nopriv_deleteQuestion","deleteQuestion");

add_action("wp_ajax_getStartQuestion","getStartQuestion");
add_action("wp_ajax_nopriv_getStartQuestion","getStartQuestion");

add_action("wp_ajax_setLocale","setLocale");
add_action("wp_ajax_nopriv_setLocale","setLocale");

add_action("wp_ajax_changeLang","changeLang");
add_action("wp_ajax_nopriv_changeLang","changeLang");

add_action("wp_ajax_setChildQuestion","setChildQuestion");
add_action("wp_ajax_nopriv_setChildQuestion","setChildQuestion");

add_action("wp_ajax_productForQuestions","productForQuestions");
add_action("wp_ajax_nopriv_productForQuestions","productForQuestions");

add_action("wp_ajax_removeProductFromQuestions","removeProductFromQuestions");
add_action("wp_ajax_nopriv_removeProductFromQuestions","removeProductFromQuestions");
