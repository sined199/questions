<form action="" method="post" name="question">
    <div class="question_back_item question_title_block">
        <span><?=__('Your question')?></span>
        <input type="text" name="text" value="<?=$data['title']?>">
    </div>
    <div class="question_back_item">
        <span><?=__('Start question')?></span>
        <input type="checkbox" name="startQuestion" <?=($data['start']==1) ? "checked" : ""?> value="<?=$data['start']?>">
    </div>
    <div class="question_back_item">
        <span><?=__('Question Category')?></span>
        <select name="type">
            <option disabled selected>Select type</option>
            <?php
                foreach($types as $type_item_key => $type_item_value){
                    $selected = "";

                    if($type_item_key == "attributes" && isset($type_item_value['terms']) && !empty($type_item_value['terms'])){
                        foreach($type_item_value['terms'] as $term_item_key => $term_item_value){
                            $selected = "";
                            if(($type_item_key."/".$term_item_key) == $data['type']) $selected = "selected";
                            ?>
                                <option value="<?= $type_item_key."/".$term_item_key ?>" <?=$selected?>><?= $term_item_value['name'] ?></option>
                            <?php
                        }
                    }
                    else{
                        if($type_item_key == $data['type']) $selected = "selected";
                        ?>
                            <option value="<?=$type_item_key?>" <?=$selected?>><?=$type_item_value['name']?></option>
                        <?php
                    }
            ?>
            <?php } ?>
        </select>
    </div>
    <div class="question_back_item answerValues open">
        <?php
            if($type_Type == "default"){
        ?>
            <select name='getOption'>
                <option disabled selected>Select option</option>
                <?php
                foreach($terms as $term_item_key => $term_item_value){
                    if(in_array($term_item_key,array_column($data['answers'],"value"))) continue;
                    ?>
                        <option value='<?=$term_item_key?>'><?=$term_item_value?></option>
                    <?php
                }
                ?>
            </select>
        <?php }else{ ?>
            <input name='getOption'>
        <?php } ?>
        <button id='addAnswer'>Add answer</button>
    </div>
    <div class="question_back_item answers_block open">
        <label class="top_message">Autosave after selecting a question</label>
        <button <?=($data['childQuestion']!=null) ? "disabled='disabled'" : ""?> class="addNewQuestion btn" id="addDefaultQuestion">Add new question</button>
        <select name="childQuestion">
            <option value="0">None</option>
            <?php
                foreach($data['questions'] as $question){
                    $selected = "";
                    if($data['childQuestion'] == $question['id']) $selected = "selected";
                    ?>
                    <option <?=$selected?> value="<?=$question['id']?>"><?=$question['text_question']?></option>
                    <?php
                }
            ?>
        </select>
        <div class="answer_list">

            <?php
                foreach($data['answers'] as $answer_item_key => $answer_item_value){
                    ?>
                    <div class='answer_item' data-val="<?=$answer_item_key?>">
                        <input value="<?=$answer_item_value['name']?>">
                        <select name="childQuestion">
                            <option value="0">None</option>
                            <?php
                                foreach($data['questions'] as $question){
                                    $selected = "";
                                    if($answer_item_value['childQuestion'] == $question['id']) $selected = "selected";
                                    ?>
                                        <option <?=$selected?> value="<?=$question['id']?>"><?=$question['text_question']?></option>
                                    <?php
                                }
                            ?>
                        </select>
                        <button class='deleteAnswer'>Delete</button>
                        <button <?=($answer_item_value['childQuestion']!=null) ? "disabled='disabled'" : ""?> class='addQuestion'>Add new question</button>
                        <span class="saved">Saved</span>
                    </div>
                    <?php
                }
                ?>
        </div>
    </div>
    <div class="question_back_item save_button_block open">
        <button id="saveQuestion">Save</button>
    </div>
    <div class="question_back_item delete_button_block open">
        <button id="deleteQuestion">Delete</button>
    </div>
</form>