SET FOREIGN_KEY_CHECKS = 0;

/* 
=================================
Создание структуры таблиц
================================= 
*/

/* Таблица `u_prepod_material` */

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
  KEY `subject_id` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


/* Таблица `u_prepod_subject` */

DROP TABLE IF EXISTS `u_prepod_subject`;

CREATE TABLE `u_prepod_subject` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


/* Таблица `u_student_material` */

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
  KEY `subject_id` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


/* Таблица `u_student_test` */

DROP TABLE IF EXISTS `u_student_test`;

CREATE TABLE `u_student_test` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `group_id` int(11) unsigned NOT NULL,
  `subject_id` int(11) unsigned NOT NULL,
  `test_id` int(11) unsigned NOT NULL COMMENT 'Id теста-основы',
  `is_mixing` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Перемешивание вопросов',
  `is_show_true` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Показывать верные ответы',
  `count_q` tinyint(3) NOT NULL DEFAULT '0' COMMENT 'Количество выводимых вопросов тз теста',
  `is_time` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Будет ли тест на время',
  `time` tinyint(5) NOT NULL DEFAULT '0' COMMENT 'Время, отведенное на прохождение теста (мин)',
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `test_id` (`test_id`),
  KEY `group_id` (`group_id`),
  KEY `subject_id` (`subject_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


/* Таблица `u_student_test_answer` */

DROP TABLE IF EXISTS `u_student_test_answer`;

CREATE TABLE `u_student_test_answer` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `test_id` int(11) unsigned DEFAULT NULL COMMENT 'Id назначенного теста',
  `question_id` int(11) unsigned DEFAULT NULL,
  `user_answer` text COLLATE utf8_unicode_ci,
  `retake_value` tinyint(2) NOT NULL DEFAULT '0',
  `number` tinyint(3) NOT NULL,
  `q` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `test_id` (`test_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


/* Таблица `u_student_test_passage` */

DROP TABLE IF EXISTS `u_student_test_passage`;

CREATE TABLE `u_student_test_passage` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `test_id` int(11) unsigned DEFAULT NULL COMMENT 'Id назначенного теста',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `retake` tinyint(2) NOT NULL DEFAULT '0',
  `last_q_number` tinyint(3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `test_id` (`test_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


/* Таблица `u_student_test_time` */

DROP TABLE IF EXISTS `u_student_test_time`;

CREATE TABLE `u_student_test_time` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `test_id` int(11) unsigned NOT NULL COMMENT 'Id назначенного теста',
  `user_id` int(11) unsigned NOT NULL,
  `date_start` datetime DEFAULT NULL,
  `date_finish` datetime DEFAULT NULL,
  `retake_value` tinyint(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `test_id` (`test_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


/* Таблица `u_test` */

DROP TABLE IF EXISTS `u_test`;

CREATE TABLE `u_test` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `subject_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


/* Таблица `u_test_answer` */

DROP TABLE IF EXISTS `u_test_answer`;

CREATE TABLE `u_test_answer` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `question_id` int(11) unsigned DEFAULT NULL,
  `right_answer` varbinary(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


/* Таблица `u_test_question` */

DROP TABLE IF EXISTS `u_test_question`;

CREATE TABLE `u_test_question` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `text` text COLLATE utf8_unicode_ci NOT NULL,
  `type` varbinary(10) DEFAULT NULL,
  `test_id` int(11) unsigned DEFAULT NULL,
  `ord` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `test_id` (`test_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


/* Таблица `u_univer_data` */

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


/* Таблица `u_univer_faculty` */

DROP TABLE IF EXISTS `u_univer_faculty`;

CREATE TABLE `u_univer_faculty` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


/* Таблица `u_univer_group` */

DROP TABLE IF EXISTS `u_univer_group`;

CREATE TABLE `u_univer_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `speciality_id` int(11) unsigned NOT NULL,
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `speciality_id` (`speciality_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


/* Таблица `u_univer_speciality` */

DROP TABLE IF EXISTS `u_univer_speciality`;

CREATE TABLE `u_univer_speciality` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `faculty_id` int(11) unsigned NOT NULL,
  `code` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `faculty_id` (`faculty_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


/* Таблица `u_user` */

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
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/* 
=================================
Добавление ограничений для таблиц
================================= 
*/

/* Изменяем таблицу `u_prepod_material` */

ALTER TABLE `u_prepod_material`

	ADD CONSTRAINT `u_prepod_material_ibfk_1` 
	FOREIGN KEY (`user_id`) REFERENCES `u_user` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE,
	
	ADD CONSTRAINT `u_prepod_material_ibfk_2` 
	FOREIGN KEY (`subject_id`) REFERENCES `u_prepod_subject` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE;
	
	
/* Изменяем таблицу `u_prepod_subject` */

ALTER TABLE `u_prepod_subject`

	ADD CONSTRAINT `u_prepod_subject_ibfk_1` FOREIGN KEY (`user_id`) 
	REFERENCES `u_user` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE;
	

/* Изменяем таблицу `u_student_material` */

ALTER TABLE `u_student_material`

	ADD CONSTRAINT `u_student_material_ibfk_1` FOREIGN KEY (`material_id`) 
	REFERENCES `u_prepod_material` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE,
	
	ADD CONSTRAINT `u_student_material_ibfk_2` FOREIGN KEY (`group_id`) 
	REFERENCES `u_univer_group` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE,
	
	ADD CONSTRAINT `u_student_material_ibfk_3` FOREIGN KEY (`subject_id`) 
	REFERENCES `u_prepod_subject` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE;
	

/* Изменяем таблицу `u_student_material` */

ALTER TABLE `u_student_test`

	ADD CONSTRAINT `u_student_test_ibfk_1` FOREIGN KEY (`test_id`) 
	REFERENCES `u_test` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE,
	
	ADD CONSTRAINT `u_student_test_ibfk_2` FOREIGN KEY (`group_id`) 
	REFERENCES `u_univer_group` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE,
	
	ADD CONSTRAINT `u_student_test_ibfk_3` FOREIGN KEY (`subject_id`) 
	REFERENCES `u_prepod_subject` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE,
	
	ADD CONSTRAINT `u_student_test_ibfk_4` FOREIGN KEY (`user_id`) 
	REFERENCES `u_user` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE;
	
	
/* Изменяем таблицу `u_student_test_answer` */

ALTER TABLE `u_student_test_answer`

	ADD CONSTRAINT `u_student_test_answer_ibfk_1` FOREIGN KEY (`user_id`) 
	REFERENCES `u_user` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE,
	
	ADD CONSTRAINT `u_student_test_answer_ibfk_2` FOREIGN KEY (`test_id`) 
	REFERENCES `u_student_test` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE,
	
	ADD CONSTRAINT `u_student_test_answer_ibfk_3` FOREIGN KEY (`question_id`) 
	REFERENCES `u_test_question` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE;
	

/* Изменяем таблицу `u_student_test_passage` */

ALTER TABLE `u_student_test_passage`

	ADD CONSTRAINT `u_student_test_passage_ibfk_1` FOREIGN KEY (`test_id`) 
	REFERENCES `u_student_test` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE,
	
	ADD CONSTRAINT `u_student_test_passage_ibfk_2` FOREIGN KEY (`user_id`) 
	REFERENCES `u_user` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE;
	

/* Изменяем таблицу `u_student_test_time` */

ALTER TABLE `u_student_test_time`

	ADD CONSTRAINT `u_student_test_time_ibfk_1` FOREIGN KEY (`user_id`) 
	REFERENCES `u_user` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE,
	
	ADD CONSTRAINT `u_student_test_time_ibfk_2` FOREIGN KEY (`test_id`) 
	REFERENCES `u_student_test` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE;
	
	
/* Изменяем таблицу `u_test` */

ALTER TABLE `u_test`

	ADD CONSTRAINT `u_test_ibfk_1` FOREIGN KEY (`user_id`) 
	REFERENCES `u_user` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE,
	
	ADD CONSTRAINT `u_test_ibfk_2` FOREIGN KEY (`subject_id`) 
	REFERENCES `u_prepod_subject` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE;
	
	
/* Изменяем таблицу `u_test_answer` */

ALTER TABLE `u_test_answer`

	ADD CONSTRAINT `u_test_answer_ibfk_1` FOREIGN KEY (`question_id`) 
	REFERENCES `u_test_question` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE;
	
	
/* Изменяем таблицу `u_test_question` */

ALTER TABLE `u_test_question`

	ADD CONSTRAINT `u_test_question_ibfk_1` FOREIGN KEY (`test_id`) 
	REFERENCES `u_test` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE;
	
	
/* Изменяем таблицу `u_univer_group` */

ALTER TABLE `u_univer_group`

	ADD CONSTRAINT `u_univer_group_ibfk_1` FOREIGN KEY (`speciality_id`) 
	REFERENCES `u_univer_speciality` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE;
	
	
/* Изменяем таблицу `u_univer_speciality` */

ALTER TABLE `u_univer_speciality`

	ADD CONSTRAINT `u_univer_speciality_ibfk_1` FOREIGN KEY (`faculty_id`) 
	REFERENCES `u_univer_faculty` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE;
	
	
/* Изменяем таблицу `u_user` */

ALTER TABLE `u_user`

	ADD CONSTRAINT `u_user_ibfk_1` FOREIGN KEY (`group_id`) 
	REFERENCES `u_univer_group` (`id`) 
	ON DELETE CASCADE 
	ON UPDATE CASCADE;
	
	
/* 
=================================
Вставка системных значений
================================= 
*/

/* Вставка в таблицу `u_univer_data` */

insert 
	into `u_univer_data` (`id`,`name`,`address`,`phone`,`logo`,`fullname`) 
	values (1,'','Адрес(а)','Телефон(ы)',NULL,'');


/* Вставка в таблицу `u_user` */
	
insert 
	into `u_user` (`id`,`role`,`login`,`password`,`salt`,`name`,`last_name`) 
	values (1,'admin','admin','','','Администратор','Администратор');