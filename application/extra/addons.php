<?php

return [
    // 是否自动读取取插件钩子配置信息（默认是关闭）
    'autoload' => false,
    // 当关闭自动获取配置时需要手动配置hooks信息
    'hooks' => [
        // 可以定义多个钩子
        'getLangListHook'=>'lang'   //获取语言种类      // 键为钩子名称，用于在业务中自定义钩子处理，值为实现该钩子的插件，
        // 多个插件可以用数组也可以用逗号分割
    ]
];

