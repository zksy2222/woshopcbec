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
	        "name"=>"currency",
	        "title"=> "货币模块插件",
	        "intro"=>"免费",
	        "author"=> "WoShop官方",
	        "version"=> "1.0.0",
	        "status"=>"0",
            "category_id"=>"0",
	        "currency" => [
				"pri_name"=>"货币管理",          //权限名称
				"mname"=> "currency",              //模块名称
				"cname"=> "Currency",              //控制器名称
				"aname"=> "currency",              //操作方法名称
				"fwname"=> "currency",             //控制器访问别名
				"icon"=> "fa fa-globe",
		        "pid"=>"156",
				"status"=> "1",             //状态
				"sort"=> "22",            //排序
			    "type"=>"1"               //代表插件
			      ],
	        "one" => [
	        	"pri_name"=>"货币列表",
		        "mname"=> "currency",
		        "cname"=> "Currency",
		        "aname"=> "lst",
		        "fwname"=> "currency",
		        "status"=> "1",
		        "sort"=>"1",
		        "type"=>"1"
	        ]

        ]
    ]
];

