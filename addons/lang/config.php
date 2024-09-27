<?php

return [
    'display' => [
        'title' => '是否显示:',
        'type' => 'radio',
        'options' => [
            '1' => '显示',
            '0' => '不显示'
        ],
        'value' => [
	        "name"=>"lang",
	        "title"=> "语言包插件",
	        "intro"=>"免费",
	        "author"=> "WoShop官方",
	        "version"=> "1.0.0",
	        "status"=>"0",
            "category_id"=>"0",
	        "lang" => [
				"pri_name"=>"语言管理",          //权限名称
				"mname"=> "lang",              //模块名称
				"cname"=> "lang",              //控制器名称
				"aname"=> "lang",              //操作方法名称
				"fwname"=> "lang",             //控制器访问别名
				"icon"=> "fa fa-language",
				"pid"=>"156",                //插件权限ID
				"status"=> "1",             //状态
				"sort"=> "21",            //排序
			    "type"=>"1"               //代表插件
			      ],
	        "one" => [
	        	"pri_name"=>"语言列表",
		        "mname"=> "lang",
		        "cname"=> "Lang",
		        "aname"=> "lst",
		        "fwname"=> "lang",
		        "status"=> "1",
		        "sort"=>"1",
		        "type"=>"1"
				      ],
	        "two"=> [
	        	"pri_name"=> "语言翻译",
		        "mname"=> "lang",
		        "cname"=> "LangTranslate",
		        "aname"=> "lst",
		        "fwname"=> "lang_translate",
		        "status"=> "1",
		        "sort"=> "2",
		        "type"=>"1"
	        ]

        ]
    ]
];

