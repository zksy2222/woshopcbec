-- phpMyAdmin SQL Dump
-- version 4.7.9
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: 2021-02-07 17:00:12
-- 服务器版本： 5.5.57-log
-- PHP Version: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dev_s1107_com`
--

-- --------------------------------------------------------

--
-- 表的结构 `sp_country`
--

CREATE TABLE `sp_country` (
                              `id` int(11) UNSIGNED NOT NULL,
                              `country_cname` varchar(20) NOT NULL COMMENT '国家中文名称',
                              `country_ename` varchar(50) NOT NULL COMMENT '英文名称',
                              `country_bname` varchar(50) NOT NULL COMMENT '本国名称',
                              `country_code` varchar(20) NOT NULL COMMENT '国家代码',
                              `country_img` varchar(100) NOT NULL COMMENT '国旗',
                              `lang_id` int(11) NOT NULL COMMENT '默认语言ID',
                              `currency_id` int(11) NOT NULL COMMENT '默认货币ID',
                              `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `sp_country`
--
ALTER TABLE `sp_country`
    ADD PRIMARY KEY (`id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `sp_country`
--
ALTER TABLE `sp_country`
    MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
