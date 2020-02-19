<div class="question_item" data-id="<?=$data['id']?>" data-type="edit">
    <div class="question_front_panel">
        <div class="parent_quest_id">
            <div class="quest_id">
                <span>ID: <?=$data['id']?></span>
            </div>
            <div class="parents">
                Parents: <span><?=join(",",array_column($data['parents'],"id_parent_question"))?></span>
            </div>
        </div>
        <div class="question_title"><?=$data['text_question']?></div>
    </div>
    <div class="question_back_panel">
        
    </div>
</div>