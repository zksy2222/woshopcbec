CREATE TABLE `sp_lang` (
                           `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                           `lang_name` varchar(64) CHARACTER SET utf8mb4 NOT NULL COMMENT '语言名称',
                           `lang_code` varchar(32) CHARACTER SET utf8mb4 NOT NULL COMMENT '前端语言代码',
                           `lang_code_front` varchar(32) CHARACTER SET utf8mb4 NOT NULL COMMENT '后端语言代码',
                           `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否默认语言 0-否 1-是',
                           `remark` varchar(255) CHARACTER SET utf8mb4 NOT NULL COMMENT '备注',
                           `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='语言表' ROW_FORMAT=COMPACT;



--page--


CREATE TABLE `sp_lang_key` (
                               `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                               `key_name` varchar(1024) CHARACTER SET utf8mb4 NOT NULL COMMENT 'lang键名',
                               `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                               `remark` varchar(255) NOT NULL COMMENT '备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Lang键名存储表' ROW_FORMAT=COMPACT;


--page--


CREATE TABLE `sp_lang_value` (
                                 `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                 `lang_id` int(11) NOT NULL COMMENT 'lang表id',
                                 `value_name` text CHARACTER SET utf8mb4 NOT NULL COMMENT '翻译后的文字值',
                                 `lang_key_id` int(11) NOT NULL COMMENT 'lang_key表id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Lang翻译值存储表' ROW_FORMAT=COMPACT;

