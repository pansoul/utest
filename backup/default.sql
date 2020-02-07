/*
SQLyog Ultimate v11.11 (64 bit)
MySQL - 5.1.63-community-log : Database - test
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `u_prepod_material` */

DROP TABLE IF EXISTS `u_prepod_material`;

CREATE TABLE `u_prepod_material` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `subject_id` int(11) unsigned DEFAULT NULL,
  `extension` varbinary(5) DEFAULT NULL,
  `size` int(100) DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `filename_original` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `filepath` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `u_prepod_material_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `u_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `u_prepod_material_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `u_prepod_subject` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_prepod_material` */

/*Table structure for table `u_prepod_subject` */

DROP TABLE IF EXISTS `u_prepod_subject`;

CREATE TABLE `u_prepod_subject` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `u_prepod_subject_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `u_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_prepod_subject` */

/*Table structure for table `u_student_material` */

DROP TABLE IF EXISTS `u_student_material`;

CREATE TABLE `u_student_material` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) unsigned DEFAULT NULL,
  `subject_id` int(11) unsigned DEFAULT NULL,
  `material_id` int(11) unsigned DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci,
  `date` datetime DEFAULT NULL,
  `is_hidden` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  KEY `group_id` (`group_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `u_student_material_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `u_prepod_material` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `u_student_material_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `u_univer_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `u_student_material_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `u_prepod_subject` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_student_material` */

/*Table structure for table `u_student_test` */

DROP TABLE IF EXISTS `u_student_test`;

CREATE TABLE `u_student_test` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) unsigned NOT NULL,
  `subject_id` int(11) unsigned NOT NULL,
  `test_id` int(11) unsigned NOT NULL COMMENT 'Id теста-основы',
  `is_mixing` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Перемешивание вопросов',
  `is_show_true` tinyint(1) NOT NULL DEFAULT '0',
  `count_q` tinyint(3) NOT NULL DEFAULT '0' COMMENT 'Количество выводимых вопросов тз теста',
  `is_time` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Будет ли тест на время',
  `time` tinyint(5) NOT NULL DEFAULT '0' COMMENT 'Время, отведенное на прохождение теста (мин)',
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `test_id` (`test_id`),
  KEY `group_id` (`group_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `u_student_test_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `u_test` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `u_student_test_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `u_univer_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `u_student_test_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `u_prepod_subject` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_student_test` */

/*Table structure for table `u_student_test_answer` */

DROP TABLE IF EXISTS `u_student_test_answer`;

CREATE TABLE `u_student_test_answer` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `test_id` int(11) unsigned DEFAULT NULL,
  `question_id` int(11) DEFAULT NULL,
  `user_answer` varbinary(100) DEFAULT NULL,
  `retake_value` tinyint(2) NOT NULL DEFAULT '0',
  `number` tinyint(3) NOT NULL,
  `q` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `test_id` (`test_id`),
  CONSTRAINT `u_student_test_answer_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `u_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `u_student_test_answer_ibfk_2` FOREIGN KEY (`test_id`) REFERENCES `u_student_test` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_student_test_answer` */

/*Table structure for table `u_student_test_passage` */

DROP TABLE IF EXISTS `u_student_test_passage`;

CREATE TABLE `u_student_test_passage` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `test_id` int(11) unsigned DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `retake` tinyint(2) NOT NULL DEFAULT '0',
  `last_q_number` tinyint(3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `test_id` (`test_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `u_student_test_passage_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `u_student_test` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `u_student_test_passage_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `u_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_student_test_passage` */

/*Table structure for table `u_student_test_time` */

DROP TABLE IF EXISTS `u_student_test_time`;

CREATE TABLE `u_student_test_time` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `test_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `date_start` datetime DEFAULT NULL,
  `date_finish` datetime DEFAULT NULL,
  `retake_value` tinyint(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `test_id` (`test_id`),
  CONSTRAINT `u_student_test_time_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `u_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `u_student_test_time_ibfk_2` FOREIGN KEY (`test_id`) REFERENCES `u_student_test` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_student_test_time` */

/*Table structure for table `u_test` */

DROP TABLE IF EXISTS `u_test`;

CREATE TABLE `u_test` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `subject_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `u_test_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `u_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `u_test_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `u_prepod_subject` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_test` */

/*Table structure for table `u_test_answer` */

DROP TABLE IF EXISTS `u_test_answer`;

CREATE TABLE `u_test_answer` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `question_id` int(11) unsigned DEFAULT NULL,
  `right_answer` varbinary(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `u_test_answer_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `u_test_question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_test_answer` */

/*Table structure for table `u_test_question` */

DROP TABLE IF EXISTS `u_test_question`;

CREATE TABLE `u_test_question` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `text` text COLLATE utf8_unicode_ci NOT NULL,
  `type` varbinary(10) DEFAULT NULL,
  `test_id` int(11) unsigned DEFAULT NULL,
  `ord` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `test_id` (`test_id`),
  CONSTRAINT `u_test_question_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `u_test` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_test_question` */

/*Table structure for table `u_univer_data` */

DROP TABLE IF EXISTS `u_univer_data`;

CREATE TABLE `u_univer_data` (
  `id` int(1) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone` varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fullname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_univer_data` */

insert  into `u_univer_data`(`id`,`name`,`address`,`phone`,`logo`,`fullname`) values (1,'','Адрес(а)','Телефон(ы)',NULL,'');

/*Table structure for table `u_univer_faculty` */

DROP TABLE IF EXISTS `u_univer_faculty`;

CREATE TABLE `u_univer_faculty` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_univer_faculty` */

/*Table structure for table `u_univer_group` */

DROP TABLE IF EXISTS `u_univer_group`;

CREATE TABLE `u_univer_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `speciality_id` int(11) unsigned NOT NULL,
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `speciality_id` (`speciality_id`),
  CONSTRAINT `u_univer_group_ibfk_1` FOREIGN KEY (`speciality_id`) REFERENCES `u_univer_speciality` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_univer_group` */

/*Table structure for table `u_univer_speciality` */

DROP TABLE IF EXISTS `u_univer_speciality`;

CREATE TABLE `u_univer_speciality` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `faculty_id` int(11) unsigned NOT NULL,
  `code` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `faculty_id` (`faculty_id`),
  CONSTRAINT `u_univer_speciality_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `u_univer_faculty` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_univer_speciality` */

/*Table structure for table `u_user` */

DROP TABLE IF EXISTS `u_user`;

CREATE TABLE `u_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `role` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `login` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Имя',
  `last_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Фамилия',
  `surname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Отчество',
  `phone` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `post` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Должность присутствует только у преподавателей',
  `group_id` int(11) unsigned DEFAULT NULL COMMENT 'Id группы. Параметр присущ только для студентов',
  PRIMARY KEY (`id`),
  KEY `u_user_ibfk_1` (`group_id`),
  CONSTRAINT `u_user_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `u_univer_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Table structure for table `u_user_roles` */

DROP TABLE IF EXISTS `u_user_roles`;

CREATE TABLE `u_user_roles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `group` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_user_roles` */

insert  into `u_user_roles`(`id`,`type`,`group`,`name`) values (1,'admin','0','Администратор'),(2,'prepod','0','Преподаватель'),(3,'student','0','Студент');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
