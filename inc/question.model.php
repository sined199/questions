<?php

class Question extends Base
{
    public static $lang = "en";

    private static $types = [
        "product_cat" => [
            "name" => "Category",
            "finder" => true,
            "type" => "default"
        ],
        'price' => [
            "name" => "Price",
            "finder" => true,
            "type" => "custom"
        ],
        "product_tag" => [
            "name" => "Tags",
            "finder" => true,
            "type" => "default"
        ],
        "custom" => [
            "name" => "Custom",
            "finder" => false,
            "type" => "custom"
        ],
        "attributes" => [
            "name" => "Attributes",
            "finder" => true,
            "terms" => [],
            "type" => "default"
        ]
    ];

    function __construct($connect = null)
    {
        if($connect != null) parent::$wpdb = $connect;
    }

    public function getQuestions()
    {
        $where = "";
//        if(!empty($data)){
//            foreach($data as $item => $value){
//                if(!empty($where)) $where .= " and ";
//                switch($item){
//                    default:{
//                        $where .= "`$item` = '$value'";
//                        break;
//                    }
//                }
//            }
//        }
//        if(!empty($where)) $where = " WHERE ".$where;
        $where .= " WHERE `lang` = '".self::$lang."'";
        $sql = "SELECT * FROM ".self::questionsTableName().$where." ORDER by id DESC";
        $result = parent::$wpdb->get_results($sql);

        return json_decode(json_encode($result),true);
    }

    public function getQuestionData($id_question)
    {
        //TODO
        $question = $this->getQuestion($id_question);
        $answers = $this->getAnswers($id_question);
        $parents = $this->getParents($id_question);

        return [
            'question' => $question,
            'answers' => $answers,
            'parents' => $parents        
        ];
    }

    public function getQuestionByAnswer($id_answer)
    {
        if(!empty($id_answer)){
            $where = " WHERE `id_parent_answer` = '".$id_answer."'";
            $sql = "SELECT * FROM ".self::questionsParentTableName().$where;
            $result = parent::$wpdb->get_results($sql);
            $result = json_decode(json_encode($result[0]),true);

            return (isset($result['id_question'])) ? $result['id_question'] : null;
        }
    }

    public function getQuestionByQuestion($id_question)
    {
        if(!empty($id_question)){
            $where = " WHERE `id_parent_question` = '".$id_question."' AND `id_parent_answer` = '0'";
            $sql = "SELECT * FROM ".self::questionsParentTableName().$where;
            $result = parent::$wpdb->get_results($sql);
            $result = json_decode(json_encode($result[0]),true);

            return (isset($result['id_question'])) ? $result['id_question'] : null;
        }
    }

    public function getQuestion($id_question)
    {
        if(!empty($id_question)){
            $where = " WHERE `id` = '".$id_question."'";
            $sql = "SELECT * FROM ".self::questionsTableName().$where;
            $result = parent::$wpdb->get_results($sql);

            return json_decode(json_encode($result[0]),true);
        }
    }

    public function getAnswer($id_answer)
    {
        if(!empty($id_answer)){
            $where = " WHERE `id` = '".$id_answer."'";
            $sql = "SELECT * FROM ".self::answersTableName().$where;
            $result = parent::$wpdb->get_results($sql);

            return json_decode(json_encode($result[0]),true);
        }
    }

    public function getAnswers($id_question)
    {
        if(!empty($id_question)){
            $where = " WHERE `id_question` = '".$id_question."'";
            $sql = "SELECT * FROM ".self::answersTableName().$where;
            $result = parent::$wpdb->get_results($sql);

            return json_decode(json_encode($result),true);
        }
    }

    public function getParents($id_question)
    {
        if(!empty($id_question)){
            $where = " WHERE `id_question` = '".$id_question."'";
            $sql = "SELECT * FROM ".self::questionsParentTableName().$where;
            $result = parent::$wpdb->get_results($sql);

            $parents = json_decode(json_encode($result),true);
            return (count($parents)>0) ? $parents : [['id_parent_question' => 0, 'id_parent_answer' => 0]];
        }
    }

    private static function setAttributesForType()
    {
        $res = wc_get_attribute_taxonomies();
        $return = [];
        foreach($res as $res_item){
            self::$types['attributes']['terms']['pa_'.$res_item->attribute_name] = [
                'name' => $res_item->attribute_label
            ];
        }
    }

    public static function getTypes()
    {
        self::setAttributesForType();
        return self::$types;
    }

    public static function getStartQuestion()
    {
        $sql = "SELECT * FROM ".self::questionsTableName()." WHERE `start` = '1' AND `lang` = '".self::$lang."'";
        $result = parent::$wpdb->get_results($sql);

        return json_decode(json_encode($result[0]),true);
    }

    private static function getLangQuestion($id_question)
    {
        if(!empty($id_question)){
            $result = parent::$wpdb->get_results("SELECT `lang` FROM ".self::answersTableName()." WHERE `id` = '$id_question'");

            return $result[0]->lang;
        }
    }

    private static function setStartQuestion($id_question,$val)
    {
        if($val == "1") {
            $start = self::getStartQuestion();
            if ($start != null) {
                parent::$wpdb->update(
                    self::questionsTableName(),
                    ["start" => "0"],
                    ["id" => $start['id']]
                );
            }
        }

        parent::$wpdb->update(
            self::questionsTableName(),
            ["start"=>$val],
            ["id"=>$id_question]
        );

        return ["start" => $val, "lang" => self::$lang];
    }

    public static function saveQuestion($data = [])
    {
        if(empty($data)) return 0;
        if($data['id'] == null) return self::addQuestion($data);
        return self::updateQuestion($data);
    }

    private static function updateQuestion($data = [])
    {
        if(empty($data)) return 0;
        $result = parent::$wpdb->update(
            self::questionsTableName(),
            [
                "text_question" => $data['title'],
                "type_question" => $data['type'],
            ],
            [
                "id" => $data["id"]
            ]
        );

        $arrNewAnswers = [];
        $arrOldAnswers = [];

        if(count($data['answers']) > 0){
            $answers = self::getAnswers($data['id']);

            foreach($data['answers'] as $answer){
                if(isset($answer['id'])) $arrOldAnswers[$answer['id']] = $answer['name'];
                else $arrNewAnswers[] = $answer;
            }

            self::addAnswers($arrNewAnswers,$data['id']);

            foreach ($answers as $answer){
                if(!in_array($answer['id'],array_keys($arrOldAnswers))){
                    parent::$wpdb->delete(
                        self::answersTableName(),
                        [
                            "id" => $answer['id']
                        ]
                    );

                    parent::$wpdb->delete(
                        self::questionsParentTableName(),
                        [
                            'id_parent_answer' => $answer['id']
                        ]
                    );
                }
                else{
                    parent::$wpdb->update(
                      self::answersTableName(),
                      [
                          'text_answer' => $arrOldAnswers[$answer['id']]
                      ],
                      [
                          'id' => $answer['id']
                      ]
                    );
                }
            }

        }

        $res = self::setStartQuestion($data["id"],$data["start"]);

        return ['method' => "UPD","result" => $result,"data" => $data,'res' => $res];
    }

    private static function addQuestion($data = [])
    {
        if(empty($data)) return 0;

        parent::$wpdb->insert(
            self::questionsTableName(),
            ['text_question' => $data['title'], 'type_question' => $data['type'], 'lang' => $data['lang']],
            ['%s','%s','%s']
        );

        $id = parent::$wpdb->insert_id;

        self::addAnswers($data['answers'],$id);

        if(!empty($data['parents'])){
            foreach($data['parents'] as $parent_key => $parent_item){
                parent::$wpdb->insert(
                    self::questionsParentTableName(),
                    ['id_question' => $id, 'id_parent_question' => $parent_item['id_question'], 'id_parent_answer' => $parent_item['id_answer']],
                    ['%d','%d','%d']
                );
            }
        }

        return ['method' => "ADD",'id' => $id];
    }

    public function deleteQuestion($id_question)
    {
        if(!empty($id_question)){
            parent::$wpdb->delete(
                self::questionsTableName(),
                [
                    'id' => $id_question
                ]
            );

            parent::$wpdb->delete(
                self::answersTableName(),
                [
                    'id_question' => $id_question
                ]
            );

            parent::$wpdb->delete(
                self::questionsParentTableName(),
                [
                    'id_question' => $id_question
                ]
            );

            parent::$wpdb->update(
                self::questionsParentTableName(),
                [
                    'id_parent_question' => 0,
                    'id_parent_answer' => 0
                ],
                [
                    "id_parent_question" => $id_question
                ]
            );
        }
    }

    public static function getValuesForType($type)
    {

        $data = get_terms([
            'taxonomy' => $type,
            'hide_empty' => false,
            'lang' => self::$lang
        ]);

    
        return $data;
    }

    public function changeQuestionParent($id_question, $id_answer, $id_child_question)
    {
//        if($id_child_question == 0){
//            if($id_answer == 0) {
//                parent::$wpdb->delete(
//                    self::questionsParentTableName(),
//                    [
//                        'id_parent_question' => $id_question,
//                        'id_parent_answer' => 0
//                    ]
//                );
//            }
//            else{
//                parent::$wpdb->delete(
//                    self::questionsParentTableName(),
//                    [
//                        'id_parent_question' => $id_question,
//                        'id_parent_answer' => $id_answer
//                    ]
//                );
//            }
//            return 0;
//        }

        $res = parent::$wpdb->get_results("SELECT `id`, `id_question` FROM ".self::questionsParentTableName()." WHERE `id_parent_question` = '$id_question' AND `id_parent_answer` = '$id_answer'");

        $id_old_question = count($res) ? $res[0]->id_question : 0;

        parent::$wpdb->delete(
            self::questionsParentTableName(),
            [
                'id' => $res[0]->id
            ]
        );

        if($id_child_question != 0){
            parent::$wpdb->insert(
                self::questionsParentTableName(),
                [
                    'id_question' => $id_child_question,
                    'id_parent_question' => $id_question,
                    'id_parent_answer' => $id_answer
                ]
            );
        }

        return $id_old_question;

        //        if($id_answer == 0){
//            parent::$wpdb->delete(
//                self::questionsParentTableName(),
//                [
//                    'id_parent_question' => $id_question,
//                    'id_parent_answer' => 0
//                ]
//            );
//
//            parent::$wpdb->insert(
//                self::questionsParentTableName(),
//                [
//                    'id_question' => $id_child_question,
//                    'id_parent_question' => $id_question,
//                    'id_parent_answer' => $id_answer
//                ]
//            );
//        }
//        else{
//            parent::$wpdb->delete(
//                self::questionsParentTableName(),
//                [
//                    'id_parent_question' => $id_question,
//                    'id_parent_answer' => $id_answer
//                ]
//            );
//
//            parent::$wpdb->insert(
//                self::questionsParentTableName(),
//                [
//                    'id_question' => $id_child_question,
//                    'id_parent_question' => $id_question,
//                    'id_parent_answer' => $id_answer
//                ]
//            );
//
//
////            parent::$wpdb->update(
////                self::questionsParentTableName(),
////                [
////                    'id_parent_question' => $id_question
////                ],
////                [
////                    'id_question' => $id_child_question,
////                    'id_parent_answer' => 0
////                ]
////            );
//        }

    }

    private function addAnswers($answers,$id_question)
    {
        if(!empty($answers)){
            foreach($answers as $answer_key => $answer_item){
                parent::$wpdb->insert(
                    self::answersTableName(),
                    ['id_question' => $id_question, 'text_answer' => $answer_item['name'], 'value_answer' => $answer_item['value'], 'type_answer' => $answer_item['type']],
                    ['%d','%s','%s','%s']
                );
            }
        }
    }

    private static function questionsTableName()
    {
        return parent::$wpdb->prefix . "q_questions";
    }

    private static function answersTableName()
    {
        return parent::$wpdb->prefix . "q_answers";
    }

    private static function questionsParentTableName()
    {
        return parent::$wpdb->prefix . "q_questions_parent";
    }
}