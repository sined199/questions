var _tempValues = {};
var _emptyQuestion = {
	id: null,
	title: null,
	type: null,
    typeType: "default",
	lang: "en",
    start: 0,
	answers: {},
	// parents: [{
	// 	id_question: 0,
	// 	id_answer: 0
	// }]
    parents: []
};
var _currentQuestionItem = null;
var _currentQuestion = copy(_emptyQuestion);

// var lang = getUrlVars($("#wp-admin-bar-languages > a").attr("href"))['lang'];
//
// _currentQuestion.lang = (lang == "all") ? "en" : lang;

$(document).ready(function() {
	$("body").on("change","form[name='question'] [name='type']",function (e) {
		e.preventDefault();
		var type = $(this).val();

		$(".answerValues",_currentQuestionItem).removeClass("open").html("");
		$(".save_button_block",_currentQuestionItem).removeClass("open");
		$(".answers_block",_currentQuestionItem).removeClass("open");
		$(".answer_list",_currentQuestionItem).html("");

		if(type == null) return 0;

		$.ajax({
			url: "/wp-admin/admin-ajax.php",
			data: {
				action: 'getValuesForType',
				key: type,
                lang: _currentQuestion.lang
			},
			datatype: 'json',
			type: 'post',
			success: function(response){
                console.log(response);
				_currentQuestion.type = type;
				_currentQuestion.type_Type = response.type_Type;
				_currentQuestion.answers = {};

				if(response.hasOwnProperty("terms") != 0) {
					_tempValues = response.terms;
				}
				$(".answerValues",_currentQuestionItem).addClass("open").html(response.html);
			}
		})
	}).on('click',"#addAnswer",function(e){
		e.preventDefault();
		var _select = $(this).siblings("[name='getOption']");
		var _val = _select.val();
		var _val_name = "";
		var _answer_item_key = "";

		if(_val == null) return 0;
		if(_currentQuestion.type_Type == "custom"){
			_val_name = _val;
			_val = string_to_slug(_val);
		}

		_answer_item_key = btoa(_val);

		if(_currentQuestion.answers.hasOwnProperty(_answer_item_key) != 0) return 0;
		if(_currentQuestion.type_Type != "custom") {
			_val_name = _tempValues[_val];

			_currentQuestion.answers[_answer_item_key] = {
				value: _val,
				name: _val_name,
				type: "default"
			};

			_select.find("option[value='"+_val+"']").remove();
		}
		else{			
			_currentQuestion.answers[_answer_item_key] = {
				value: _val,
				name: _val_name,
				type: "custom"
			};
		}

		var answer_item = "<div class='answer_item' data-val='"+_answer_item_key+"'><input value='"+_val_name+"'><button class='deleteAnswer'>Delete</button>";
		answer_item += "</div>";

		$(".answers_block",_currentQuestionItem).addClass("open").find(".answer_list").append(answer_item);
		$(".save_button_block",_currentQuestionItem).addClass("open");
	}).on('change','.answer_item input',function(e){
		e.preventDefault();
		
		var _answer_item = $(this).closest(".answer_item");
		var _val = $(this).val();
		var _answer_item_key = _answer_item.data("val");

		_currentQuestion.answers[_answer_item_key].name = _val;
	}).on('click','.deleteAnswer',function(e) {
		e.preventDefault();

		var _answer_item = $(this).closest(".answer_item");
		var _val = _answer_item.data("val");

		delete _currentQuestion.answers[_val];
		_answer_item.remove();

		if(_currentQuestion.type_Type != "custom"){
			console.log(_val);
			_val = atob(_val);
			_val_name = _tempValues[_val];
			$(".answerValues select",_currentQuestionItem).append("<option value='"+_val+"'>"+_val_name+"</option>");
		}

		if(Object.keys(_currentQuestion.answers).length == 0){
			$(".answers_block",_currentQuestionItem).removeClass("open")
			$(".save_button_block",_currentQuestionItem).removeClass("open");
		}	
	}).on('change',"form[name='question'] .question_title_block [name='text']",function(e){
		e.preventDefault();
		var _title = $(this).val();
		_currentQuestion.title = _title;
		$(".question_title_block input",_currentQuestionItem).removeClass("emptyError");
		$(".question_title",_currentQuestionItem).html(_title);
	}).on('blur',"form[name='question'] .question_title_block [name='text']",function(e){
		e.preventDefault();
		var _title = $(this).val();
		if(_title == ""){
			$(this).addClass("emptyError");
			return 0;
		}
	}).on('click',".question_front_panel",function(){
		_currentQuestionItem = $(this).closest(".question_item");
        var _id = _currentQuestionItem.data("id");
		var _lang = _currentQuestion.lang;
        _currentQuestion = copy(_emptyQuestion);
        _currentQuestion.lang = _lang;
        _tempValues = [];

        if($(".question_item.open").length > 0){
            if(_id != $(".question_item.open").data("id")) {
                if ($(".question_item.open").data("type") == "edit") {
                    $(".question_item.open[data-type='edit']").removeClass("open").find(".question_back_panel").html("");
                }
                if ($(".question_item.open").data("type") == "add") {
                    $(".question_item.open[data-type='add']").removeClass("open");
                }
            }
        }

		if(_currentQuestionItem.data("type") == "add"){
			_currentQuestionItem.toggleClass("open");
			return 0;
		}
		if(!_currentQuestionItem.hasClass("open")){



			$.ajax({
				url: "/wp-admin/admin-ajax.php",
				data:{
					action:'getQuestionData',
					id: _id
				},
				datatype: "json",
				type: "POST",
				success: function(response){
					console.log(response);
					_currentQuestionItem.addClass("open").find(".question_back_panel").html(response.html);
					_currentQuestion = response.data;
					_tempValues = response.terms;
				}
			})
		}
		else{
            _currentQuestionItem.removeClass("open").find(".question_back_panel").html("");
        }
	}).on('click',"#saveQuestion",function(e){
		e.preventDefault();
		if(_currentQuestion.title == null){
			$(".question_title_block input",_currentQuestionItem).addClass("emptyError");
			return 0;
		}
		if(Object.keys(_currentQuestion.answers).length == 0) return 0;

		if(_currentQuestion.id != null) {
            var res = confirm("After deleting answers, communication between questions may be lost");
            if (!res) return 0;
        }

		var _lang = _currentQuestion.lang;

		$.ajax({
			url: "/wp-admin/admin-ajax.php",
			data: {
				action: 'saveQuestion',
				questionData: _currentQuestion
			},
			datatype: "json",
			type: "post",
			success: function(response){
				console.log(response);
				if(response.method == "ADD"){
					if(response.hasOwnProperty("id") != 0){
						$("form[name='question'] .question_title_block [name='text']",_currentQuestionItem).val("");
						$(".question_title",_currentQuestionItem).html("< New question >");
						$("form[name='question'] [name='type']",_currentQuestionItem).val("0").change();
						$(response.answer_item).insertAfter(_currentQuestionItem);

						_currentQuestion = copy(_emptyQuestion);
						_currentQuestion.lang = _lang;
                        _tempValues = [];
					}
					return 0;
				}
				if(response.method == "UPD"){
                    _currentQuestionItem.removeClass("open").find(".question_back_panel").html("");
					return 0;
				}
			}
		});
		console.log(_currentQuestion);
	}).on('click','#deleteQuestion',function(e){
	    e.preventDefault();
	    var res = confirm("Are you sure?");
	    if(!res) return 0;
	    var _id = _currentQuestion.id;
	    var _lang = _currentQuestion.lang;
	    $.ajax({
            url: "/wp-admin/admin-ajax.php",
            type: "post",
            datatype: "json",
            data: {
                action : "deleteQuestion",
                id : _id
            },
            success:function(response){
                console.log(response);
                if(response.success){
                    _currentQuestionItem.remove();
                    _currentQuestionItem = null;
                    _currentQuestion = copy(_emptyQuestion);
                    _currentQuestion.lang = _lang;
                    _tempValues = [];
                }
            }
        })
    }).on('click','#addDefaultQuestion',function(e){
        e.preventDefault();
        var _id = _currentQuestion.id;
        var _lang = _currentQuestion.lang;

        _currentQuestionItem.removeClass("open").find(".question_back_panel").html("");

        _currentQuestion = copy(_emptyQuestion);
        _emptyQuestion.parents = [];
        _tempValues = [];

        _currentQuestionItem = $(".question_item.add_question");
        _currentQuestionItem.addClass("open");

        _currentQuestion.parents.push({
            id_question : _id,
            id_answer : 0
        });

        _currentQuestion.lang = _lang;
    }).on('click','.addQuestion',function(e){
        e.preventDefault();
        var _val = $(this).closest(".answer_item").data("val");
        var _answer = _currentQuestion.answers[_val];
        var _lang = _currentQuestion.lang;
        var _id = _currentQuestion.id;

        _currentQuestionItem.removeClass("open").find(".question_back_panel").html("");
        _currentQuestion = copy(_emptyQuestion);
        _emptyQuestion.parents = [];
        _tempValues = [];

        _currentQuestionItem = $(".question_item.add_question");
        _currentQuestionItem.addClass("open");

        _currentQuestion.parents.push({
            id_question : _id,
            id_answer : _answer.id
        });

        _currentQuestion.lang = _lang;

    }).on('change','[name="startQuestion"]',function(e){
        e.preventDefault();
        _currentQuestion.start = ($(this).prop("checked")) ? "1" : "0";
    }).on('change','.questions_lang select[name="lang"]',function(e){
        e.preventDefault();
        var _lang = $(this).val();

        $.ajax({
            url: "/wp-admin/admin-ajax.php",
            datatype: "json",
            type: "post",
            data: {
                action: "changeLang",
                lang: _lang
            },
            success: function(response){
                _currentQuestion = copy(_emptyQuestion);
                _tempValues = [];
                _currentQuestion.lang = _lang;
                $(".questions_list").html(response.html_questions);
                $(".products_all_list").html(response.html_all_products);
                $(".products_set_list").html(response.html_set_products);
            }
        });
        console.log(_lang);
    }).on('change','select[name="childQuestion"]',function(e){
        e.preventDefault();
        var _this = $(this);

        var id_answer = 0;
        var id_question = _currentQuestion.id;
        var id_child_question = $(this).val();

        var answer_item = null;
        answer_item = _this.closest(".answer_item");

        if(answer_item.length > 0){
            id_answer = _currentQuestion.answers[answer_item.data("val")].id;
        }
        var save_span = $(".saved",answer_item);
        save_span.addClass("show");

        setChildQuestion(id_question, id_child_question, id_answer,save_span);

        if(id_child_question == 0){
            if(answer_item.length > 0){
                answer_item.find(".addQuestion").removeAttr("disabled");
            }
            else{
                _this.siblings("#addDefaultQuestion").removeAttr("disabled");
            }
        }
        else{
            if(answer_item.length > 0){
                answer_item.find(".addQuestion").attr("disabled","disabled");
            }
            else{
                _this.siblings("#addDefaultQuestion").attr("disabled","disabled");
            }
        }
    }).on("click",".products_all_list .product_item",function(e){
        e.preventDefault();
        var lang = _currentQuestion.lang;
        var id = $(this).data("id");
        var products_all_list = $(".products_all_list");
        var products_set_list = $(".products_set_list");

        // var product_item = products_all_list.find(".product_item[data-id='"+id+"']");
        var product_item = $(this);

        $.ajax({
            url: "/wp-admin/admin-ajax.php",
            datatype: "json",
            type: "post",
            data: {
                action: "productForQuestions",
                productid: id,
                lang: lang
            },
            success: function(response){
                console.log(response);
                switch (response.status) {
                    case 201:{
                        product_item.removeClass("active");
                        products_set_list.find(".product_item[data-id='"+id+"']").remove();
                        break;
                    }
                    case 404:{
                        var _empty = $("<div class='product_item'><div class='product_img'><img></div><div class='product_name'></div></div>");
                        var _img = product_item.find("img").attr("src");
                        var _title = product_item.find(".product_name span").text();

                        _empty.find(".product_img img").attr("src",_img);
                        _empty.find(".product_name").text(_title);
                        _empty.attr("data-id",id);

                        console.log(_empty);

                        products_set_list.append(_empty);
                        product_item.addClass("active");
                        break;
                    }
                }
            }
        })
    }).on("click",".products_set_list .product_item",function(e){
        e.preventDefault();
        var id = $(this).data("id");
        var products_all_list = $(".products_all_list");
        var products_set_list = $(".products_set_list");
        var this_product_item = $(this);
        var product_item = products_all_list.find(".product_item[data-id='"+id+"']");

        $.ajax({
            url: "/wp-admin/admin-ajax.php",
            datatype: "json",
            type: "post",
            data: {
                action: "removeProductFromQuestions",
                productid: id,
            },
            success: function(response){
                console.log(response);
                product_item.removeClass("active");
                this_product_item.remove();
            }
        })
    })
});

function setChildQuestion(id_question, id_child_question, id_answer = 0,save_span)
{
    $.ajax({
        url: "/wp-admin/admin-ajax.php",
        data: {
            action: "setChildQuestion",
            id_question: id_question,
            id_answer: id_answer,
            id_child_question: id_child_question
        },
        datatype: "json",
        type: "POST",
        success: function(response){
            console.log(response);
            setTimeout(function () {
                save_span.removeClass("show");
            },2000);
        }
    })
}
function string_to_slug (str) {
    str = String(str).toString();
    str = str.replace(/^\s+|\s+$/g, ""); // trim
    str = str.toLowerCase();

    // remove accents, swap ñ for n, etc
    const swaps = {
        '0': ['°', '₀', '۰', '０'],
        '1': ['¹', '₁', '۱', '１'],
        '2': ['²', '₂', '۲', '２'],
        '3': ['³', '₃', '۳', '３'],
        '4': ['⁴', '₄', '۴', '٤', '４'],
        '5': ['⁵', '₅', '۵', '٥', '５'],
        '6': ['⁶', '₆', '۶', '٦', '６'],
        '7': ['⁷', '₇', '۷', '７'],
        '8': ['⁸', '₈', '۸', '８'],
        '9': ['⁹', '₉', '۹', '９'],
        'a': ['à', 'á', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'ā', 'ą', 'å', 'α', 'ά', 'ἀ', 'ἁ', 'ἂ', 'ἃ', 'ἄ', 'ἅ', 'ἆ', 'ἇ', 'ᾀ', 'ᾁ', 'ᾂ', 'ᾃ', 'ᾄ', 'ᾅ', 'ᾆ', 'ᾇ', 'ὰ', 'ά', 'ᾰ', 'ᾱ', 'ᾲ', 'ᾳ', 'ᾴ', 'ᾶ', 'ᾷ', 'а', 'أ', 'အ', 'ာ', 'ါ', 'ǻ', 'ǎ', 'ª', 'ა', 'अ', 'ا', 'ａ', 'ä'],
        'b': ['б', 'β', 'ب', 'ဗ', 'ბ', 'ｂ'],
        'c': ['ç', 'ć', 'č', 'ĉ', 'ċ', 'ｃ'],
        'd': ['ď', 'ð', 'đ', 'ƌ', 'ȡ', 'ɖ', 'ɗ', 'ᵭ', 'ᶁ', 'ᶑ', 'д', 'δ', 'د', 'ض', 'ဍ', 'ဒ', 'დ', 'ｄ'],
        'e': ['é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ', 'ë', 'ē', 'ę', 'ě', 'ĕ', 'ė', 'ε', 'έ', 'ἐ', 'ἑ', 'ἒ', 'ἓ', 'ἔ', 'ἕ', 'ὲ', 'έ', 'е', 'ё', 'э', 'є', 'ə', 'ဧ', 'ေ', 'ဲ', 'ე', 'ए', 'إ', 'ئ', 'ｅ'],
        'f': ['ф', 'φ', 'ف', 'ƒ', 'ფ', 'ｆ'],
        'g': ['ĝ', 'ğ', 'ġ', 'ģ', 'г', 'ґ', 'γ', 'ဂ', 'გ', 'گ', 'ｇ'],
        'h': ['ĥ', 'ħ', 'η', 'ή', 'ح', 'ه', 'ဟ', 'ှ', 'ჰ', 'ｈ'],
        'i': ['í', 'ì', 'ỉ', 'ĩ', 'ị', 'î', 'ï', 'ī', 'ĭ', 'į', 'ı', 'ι', 'ί', 'ϊ', 'ΐ', 'ἰ', 'ἱ', 'ἲ', 'ἳ', 'ἴ', 'ἵ', 'ἶ', 'ἷ', 'ὶ', 'ί', 'ῐ', 'ῑ', 'ῒ', 'ΐ', 'ῖ', 'ῗ', 'і', 'ї', 'и', 'ဣ', 'ိ', 'ီ', 'ည်', 'ǐ', 'ი', 'इ', 'ی', 'ｉ'],
        'j': ['ĵ', 'ј', 'Ј', 'ჯ', 'ج', 'ｊ'],
        'k': ['ķ', 'ĸ', 'к', 'κ', 'Ķ', 'ق', 'ك', 'က', 'კ', 'ქ', 'ک', 'ｋ'],
        'l': ['ł', 'ľ', 'ĺ', 'ļ', 'ŀ', 'л', 'λ', 'ل', 'လ', 'ლ', 'ｌ'],
        'm': ['м', 'μ', 'م', 'မ', 'მ', 'ｍ'],
        'n': ['ñ', 'ń', 'ň', 'ņ', 'ŉ', 'ŋ', 'ν', 'н', 'ن', 'န', 'ნ', 'ｎ'],
        'o': ['ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ø', 'ō', 'ő', 'ŏ', 'ο', 'ὀ', 'ὁ', 'ὂ', 'ὃ', 'ὄ', 'ὅ', 'ὸ', 'ό', 'о', 'و', 'θ', 'ို', 'ǒ', 'ǿ', 'º', 'ო', 'ओ', 'ｏ', 'ö'],
        'p': ['п', 'π', 'ပ', 'პ', 'پ', 'ｐ'],
        'q': ['ყ', 'ｑ'],
        'r': ['ŕ', 'ř', 'ŗ', 'р', 'ρ', 'ر', 'რ', 'ｒ'],
        's': ['ś', 'š', 'ş', 'с', 'σ', 'ș', 'ς', 'س', 'ص', 'စ', 'ſ', 'ს', 'ｓ'],
        't': ['ť', 'ţ', 'т', 'τ', 'ț', 'ت', 'ط', 'ဋ', 'တ', 'ŧ', 'თ', 'ტ', 'ｔ'],
        'u': ['ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự', 'û', 'ū', 'ů', 'ű', 'ŭ', 'ų', 'µ', 'у', 'ဉ', 'ု', 'ူ', 'ǔ', 'ǖ', 'ǘ', 'ǚ', 'ǜ', 'უ', 'उ', 'ｕ', 'ў', 'ü'],
        'v': ['в', 'ვ', 'ϐ', 'ｖ'],
        'w': ['ŵ', 'ω', 'ώ', 'ဝ', 'ွ', 'ｗ'],
        'x': ['χ', 'ξ', 'ｘ'],
        'y': ['ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ', 'ÿ', 'ŷ', 'й', 'ы', 'υ', 'ϋ', 'ύ', 'ΰ', 'ي', 'ယ', 'ｙ'],
        'z': ['ź', 'ž', 'ż', 'з', 'ζ', 'ز', 'ဇ', 'ზ', 'ｚ'],
        'aa': ['ع', 'आ', 'آ'],
        'ae': ['æ', 'ǽ'],
        'ai': ['ऐ'],
        'ch': ['ч', 'ჩ', 'ჭ', 'چ'],
        'dj': ['ђ', 'đ'],
        'dz': ['џ', 'ძ'],
        'ei': ['ऍ'],
        'gh': ['غ', 'ღ'],
        'ii': ['ई'],
        'ij': ['ĳ'],
        'kh': ['х', 'خ', 'ხ'],
        'lj': ['љ'],
        'nj': ['њ'],
        'oe': ['ö', 'œ', 'ؤ'],
        'oi': ['ऑ'],
        'oii': ['ऒ'],
        'ps': ['ψ'],
        'sh': ['ш', 'შ', 'ش'],
        'shch': ['щ'],
        'ss': ['ß'],
        'sx': ['ŝ'],
        'th': ['þ', 'ϑ', 'ث', 'ذ', 'ظ'],
        'ts': ['ц', 'ც', 'წ'],
        'ue': ['ü'],
        'uu': ['ऊ'],
        'ya': ['я'],
        'yu': ['ю'],
        'zh': ['ж', 'ჟ', 'ژ'],
        '(c)': ['©'],
        'A': ['Á', 'À', 'Ả', 'Ã', 'Ạ', 'Ă', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ', 'Ặ', 'Â', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ', 'Å', 'Ā', 'Ą', 'Α', 'Ά', 'Ἀ', 'Ἁ', 'Ἂ', 'Ἃ', 'Ἄ', 'Ἅ', 'Ἆ', 'Ἇ', 'ᾈ', 'ᾉ', 'ᾊ', 'ᾋ', 'ᾌ', 'ᾍ', 'ᾎ', 'ᾏ', 'Ᾰ', 'Ᾱ', 'Ὰ', 'Ά', 'ᾼ', 'А', 'Ǻ', 'Ǎ', 'Ａ', 'Ä'],
        'B': ['Б', 'Β', 'ब', 'Ｂ'],
        'C': ['Ç', 'Ć', 'Č', 'Ĉ', 'Ċ', 'Ｃ'],
        'D': ['Ď', 'Ð', 'Đ', 'Ɖ', 'Ɗ', 'Ƌ', 'ᴅ', 'ᴆ', 'Д', 'Δ', 'Ｄ'],
        'E': ['É', 'È', 'Ẻ', 'Ẽ', 'Ẹ', 'Ê', 'Ế', 'Ề', 'Ể', 'Ễ', 'Ệ', 'Ë', 'Ē', 'Ę', 'Ě', 'Ĕ', 'Ė', 'Ε', 'Έ', 'Ἐ', 'Ἑ', 'Ἒ', 'Ἓ', 'Ἔ', 'Ἕ', 'Έ', 'Ὲ', 'Е', 'Ё', 'Э', 'Є', 'Ə', 'Ｅ'],
        'F': ['Ф', 'Φ', 'Ｆ'],
        'G': ['Ğ', 'Ġ', 'Ģ', 'Г', 'Ґ', 'Γ', 'Ｇ'],
        'H': ['Η', 'Ή', 'Ħ', 'Ｈ'],
        'I': ['Í', 'Ì', 'Ỉ', 'Ĩ', 'Ị', 'Î', 'Ï', 'Ī', 'Ĭ', 'Į', 'İ', 'Ι', 'Ί', 'Ϊ', 'Ἰ', 'Ἱ', 'Ἳ', 'Ἴ', 'Ἵ', 'Ἶ', 'Ἷ', 'Ῐ', 'Ῑ', 'Ὶ', 'Ί', 'И', 'І', 'Ї', 'Ǐ', 'ϒ', 'Ｉ'],
        'J': ['Ｊ'],
        'K': ['К', 'Κ', 'Ｋ'],
        'L': ['Ĺ', 'Ł', 'Л', 'Λ', 'Ļ', 'Ľ', 'Ŀ', 'ल', 'Ｌ'],
        'M': ['М', 'Μ', 'Ｍ'],
        'N': ['Ń', 'Ñ', 'Ň', 'Ņ', 'Ŋ', 'Н', 'Ν', 'Ｎ'],
        'O': ['Ó', 'Ò', 'Ỏ', 'Õ', 'Ọ', 'Ô', 'Ố', 'Ồ', 'Ổ', 'Ỗ', 'Ộ', 'Ơ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ', 'Ø', 'Ō', 'Ő', 'Ŏ', 'Ο', 'Ό', 'Ὀ', 'Ὁ', 'Ὂ', 'Ὃ', 'Ὄ', 'Ὅ', 'Ὸ', 'Ό', 'О', 'Θ', 'Ө', 'Ǒ', 'Ǿ', 'Ｏ', 'Ö'],
        'P': ['П', 'Π', 'Ｐ'],
        'Q': ['Ｑ'],
        'R': ['Ř', 'Ŕ', 'Р', 'Ρ', 'Ŗ', 'Ｒ'],
        'S': ['Ş', 'Ŝ', 'Ș', 'Š', 'Ś', 'С', 'Σ', 'Ｓ'],
        'T': ['Ť', 'Ţ', 'Ŧ', 'Ț', 'Т', 'Τ', 'Ｔ'],
        'U': ['Ú', 'Ù', 'Ủ', 'Ũ', 'Ụ', 'Ư', 'Ứ', 'Ừ', 'Ử', 'Ữ', 'Ự', 'Û', 'Ū', 'Ů', 'Ű', 'Ŭ', 'Ų', 'У', 'Ǔ', 'Ǖ', 'Ǘ', 'Ǚ', 'Ǜ', 'Ｕ', 'Ў', 'Ü'],
        'V': ['В', 'Ｖ'],
        'W': ['Ω', 'Ώ', 'Ŵ', 'Ｗ'],
        'X': ['Χ', 'Ξ', 'Ｘ'],
        'Y': ['Ý', 'Ỳ', 'Ỷ', 'Ỹ', 'Ỵ', 'Ÿ', 'Ῠ', 'Ῡ', 'Ὺ', 'Ύ', 'Ы', 'Й', 'Υ', 'Ϋ', 'Ŷ', 'Ｙ'],
        'Z': ['Ź', 'Ž', 'Ż', 'З', 'Ζ', 'Ｚ'],
        'AE': ['Æ', 'Ǽ'],
        'Ch': ['Ч'],
        'Dj': ['Ђ'],
        'Dz': ['Џ'],
        'Gx': ['Ĝ'],
        'Hx': ['Ĥ'],
        'Ij': ['Ĳ'],
        'Jx': ['Ĵ'],
        'Kh': ['Х'],
        'Lj': ['Љ'],
        'Nj': ['Њ'],
        'Oe': ['Œ'],
        'Ps': ['Ψ'],
        'Sh': ['Ш'],
        'Shch': ['Щ'],
        'Ss': ['ẞ'],
        'Th': ['Þ'],
        'Ts': ['Ц'],
        'Ya': ['Я'],
        'Yu': ['Ю'],
        'Zh': ['Ж'],
    };

    Object.keys(swaps).forEach((swap) => {
        swaps[swap].forEach(s => {
            str = str.replace(new RegExp(s, "g"), swap);
        })
    });
    return str
        .replace(/[^a-z0-9 -]/g, "") // remove invalid chars
        .replace(/\s+/g, "-") // collapse whitespace and replace by -
        .replace(/-+/g, "-") // collapse dashes
        .replace(/^-+/, "") // trim - from start of text
        .replace(/-+$/, "");
}
function copy(mainObj) {
  let objCopy = {}; // objCopy will store a copy of the mainObj
  let key;

  for (key in mainObj) {
    objCopy[key] = mainObj[key]; // copies each property to the objCopy object
  }
  return objCopy;
}
function getUrlVars(url_string) {
    var vars = {};
    var parts = url_string.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}