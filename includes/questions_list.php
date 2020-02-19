<div class="question_item add_question" data-type="add">
    <div class="question_front_panel">
        <div class="question_title">< New question ></div>
    </div>
    <div class="question_back_panel">
        <form action="" method="post" name="question">
            <div class="question_back_item question_title_block">
                <span><?=__('Your question')?></span>
                <input type="text" name="text">
            </div>
            <div class="question_back_item">
                <span><?=__('Question Category')?></span>
                <select name="type">
                    <option disabled selected>Select type</option>
                    <?php
                    foreach($types as $type_item_key => $type_item_value){
                        if($type_item_key == "attributes" && isset($type_item_value['terms']) && !empty($type_item_value['terms'])){
                            foreach($type_item_value['terms'] as $term_item_key => $term_item_value){
                                ?>
                                <option value="<?= $type_item_key."/".$term_item_key ?>"><?= $term_item_value['name'] ?></option>
                                <?php
                            }
                        }
                        else{
                            ?>
                            <option value="<?=$type_item_key?>"><?=$type_item_value['name']?></option>
                            <?php
                        }
                        ?>
                    <?php }  ?>
                </select>
            </div>
            <div class="question_back_item answerValues">
                <span>Выберите новый ответ для вопроса</span>
            </div>
            <div class="question_back_item answers_block">
                <!--<button class="addNewQuestion btn">Добавить новый вопрос</button>-->
                <div class="answer_list">

                </div>
            </div>
            <div class="question_back_item save_button_block">
                <button id="saveQuestion">Save</button>
            </div>
        </form>
    </div>
</div>
<?php

foreach($questions as $questions_item){

    $data = $questions_item;
    $data['parents'] = Question::getParents($data['id']);

    require 'question_preview.php';
}