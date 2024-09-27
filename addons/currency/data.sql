-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- 主机： localhost:3306
-- 生成日期： 2021-02-07 16:59:12
-- 服务器版本： 5.6.44-log
-- PHP 版本： 7.4.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `wosmart`
--

-- --------------------------------------------------------

--
-- 表的结构 `sp_currency`
--

CREATE TABLE `sp_currency` (
                               `id` int(10) UNSIGNED NOT NULL,
                               `currency_name` varchar(255) NOT NULL COMMENT '货币名称',
                               `currency_symbol` char(10) NOT NULL COMMENT '符号',
                               `currency_code` char(10) NOT NULL COMMENT '代码',
                               `currency_exchange` float(10,3) NOT NULL COMMENT '汇率',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 转储表的索引
--

--
-- 表的索引 `sp_currency`
--
ALTER TABLE `sp_currency`
    ADD PRIMARY KEY (`id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `sp_currency`
--
ALTER TABLE `sp_currency`
    MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
