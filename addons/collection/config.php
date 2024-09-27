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
	        "name"=>"collection",
	        "title"=> "商品采集管理(付费)",
	        "intro"=>"3000元",
	        "author"=> "WoShop官方",
	        "version"=> "1.0.0",
	        "status"=>"0",
            "category_id"=>"1",
            'key'=>'0D7B063CC04241BE3E01DA2466CAA549',       //99api秘钥
	        "collection" => [
				"pri_name"=>"商品采集管理(付费)",          //权限名称
				"mname"=> "collection",              //模块名称
				"cname"=> "Collection",              //控制器名称
				"aname"=> "collection",              //操作方法名称
				"fwname"=> "collection",             //控制器访问别名
				"icon"=> "fa fa-globe",
		        "pid"=>"153",
				"status"=> "1",             //状态
				"sort"=> "23",            //排序
			    "type"=>"1"               //代表插件
			      ],
	        "one" => [
	        	"pri_name"=>"虾皮采集",
		        "mname"=> "collection",
		        "cname"=> "Collection",
		        "aname"=> "info",
		        "fwname"=> "collection",
		        "status"=> "1",
		        "sort"=>"1",
		        "type"=>"1"
	        ]

        ]
    ]
];

