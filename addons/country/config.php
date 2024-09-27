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
	        "name"=>"country",
	        "title"=> "国家模块插件",
	        "intro"=>"免费",
	        "author"=> "WoShop官方",
	        "version"=> "1.0.0",
	        "status"=>"0",
            "category_id"=>"0",
	        "country" => [
				"pri_name"=>"国家管理",          //权限名称
				"mname"=> "country",              //模块名称
				"cname"=> "Country",              //控制器名称
				"aname"=> "country",              //操作方法名称
				"fwname"=> "country",             //控制器访问别名
				"icon"=> "fa fa-globe",
		        "pid"=>"156",
				"status"=> "1",             //状态
				"sort"=> "23",            //排序
			    "type"=>"1"               //代表插件
			      ],
	        "one" => [
	        	"pri_name"=>"国家列表",
		        "mname"=> "country",
		        "cname"=> "Country",
		        "aname"=> "lst",
		        "fwname"=> "country",
		        "status"=> "1",
		        "sort"=>"1",
		        "type"=>"1"
	        ]

        ]
    ]
];

