/*
SQLyog Ultimate v11.11 (64 bit)
MySQL - 5.1.65-community-log : Database - utest
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_prepod_material` */

insert  into `u_prepod_material`(`id`,`user_id`,`subject_id`,`extension`,`size`,`filename`,`filename_original`,`filepath`,`date`) values (1,2,1,'jpg',17624,'qwe','14198','/uploads/materials/p-2/1472357072.jpg','2016-08-28 07:04:32');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_prepod_subject` */

insert  into `u_prepod_subject`(`id`,`title`,`user_id`,`alias`) values (1,'Математика',2,'matematika');

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_student_material` */

insert  into `u_student_material`(`id`,`group_id`,`subject_id`,`material_id`,`comment`,`date`,`is_hidden`) values (1,1,1,NULL,'qweqweqw\r\nqwd\r\nwqd\r\nwd !!!','2016-08-28 07:06:03',1);
insert  into `u_student_material`(`id`,`group_id`,`subject_id`,`material_id`,`comment`,`date`,`is_hidden`) values (2,1,1,1,NULL,'2016-08-28 07:07:14',0);

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
  `count_q` smallint(3) NOT NULL DEFAULT '0' COMMENT 'Количество выводимых вопросов тз теста',
  `time` smallint(5) NOT NULL DEFAULT '0' COMMENT 'Время, отведенное на прохождение теста (сек)',
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `test_id` (`test_id`),
  KEY `group_id` (`group_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `u_student_test_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `u_test` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `u_student_test_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `u_univer_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `u_student_test_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `u_prepod_subject` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_student_test` */

insert  into `u_student_test`(`id`,`title`,`user_id`,`group_id`,`subject_id`,`test_id`,`is_mixing`,`is_show_true`,`count_q`,`time`,`date`) values (1,'Введение в дисциплину',2,1,1,1,1,1,3,600,'2016-08-02 08:52:58');

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_student_test_answer` */

insert  into `u_student_test_answer`(`id`,`user_id`,`test_id`,`question_id`,`user_answer`,`retake_value`,`number`,`q`) values (1,3,1,1,'s:1:\"1\";',0,1,'a:4:{s:4:\"text\";s:3:\"qwe\";s:4:\"type\";s:3:\"one\";s:6:\"answer\";a:2:{i:2;s:3:\"222\";i:1;s:3:\"qwe\";}s:5:\"right\";a:2:{i:1;s:1:\"0\";i:2;s:1:\"1\";}}');
insert  into `u_student_test_answer`(`id`,`user_id`,`test_id`,`question_id`,`user_answer`,`retake_value`,`number`,`q`) values (2,3,1,3,'a:5:{i:5;s:1:\"2\";i:8;s:1:\"5\";i:6;s:1:\"3\";i:7;s:1:\"4\";i:4;s:1:\"1\";}',0,2,'a:4:{s:4:\"text\";s:40:\"Расставьте по порядку\";s:4:\"type\";s:5:\"order\";s:6:\"answer\";a:5:{i:5;s:10:\"семья\";i:8;s:26:\"Успех в спорте\";i:6;s:42:\"Путешествие за границу\";i:7;s:20:\"Английский\";i:4;s:17:\"Своё дело\";}s:5:\"right\";a:5:{i:4;s:1:\"1\";i:5;s:1:\"2\";i:6;s:1:\"3\";i:7;s:1:\"4\";i:8;s:1:\"5\";}}');
insert  into `u_student_test_answer`(`id`,`user_id`,`test_id`,`question_id`,`user_answer`,`retake_value`,`number`,`q`) values (3,3,1,2,'s:2:\"25\";',0,3,'a:4:{s:4:\"text\";s:34:\"Сколько вам\r\nлет, а?\";s:4:\"type\";s:5:\"match\";s:6:\"answer\";a:1:{i:3;N;}s:5:\"right\";s:2:\"25\";}');
insert  into `u_student_test_answer`(`id`,`user_id`,`test_id`,`question_id`,`user_answer`,`retake_value`,`number`,`q`) values (4,4,1,1,NULL,0,1,'a:4:{s:4:\"text\";s:3:\"qwe\";s:4:\"type\";s:3:\"one\";s:6:\"answer\";a:2:{i:1;s:3:\"qwe\";i:2;s:3:\"222\";}s:5:\"right\";a:2:{i:1;s:1:\"0\";i:2;s:1:\"1\";}}');

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_student_test_passage` */

insert  into `u_student_test_passage`(`id`,`user_id`,`test_id`,`status`,`retake`,`last_q_number`) values (1,3,1,0,1,NULL);
insert  into `u_student_test_passage`(`id`,`user_id`,`test_id`,`status`,`retake`,`last_q_number`) values (2,4,1,1,0,1);

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_student_test_time` */

insert  into `u_student_test_time`(`id`,`test_id`,`user_id`,`date_start`,`date_finish`,`retake_value`) values (1,1,3,'2016-08-02 09:03:03','2016-08-02 09:03:59',0);
insert  into `u_student_test_time`(`id`,`test_id`,`user_id`,`date_start`,`date_finish`,`retake_value`) values (2,1,4,'2016-08-28 07:12:23',NULL,0);

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_test` */

insert  into `u_test`(`id`,`title`,`user_id`,`subject_id`) values (1,'Введение в дисциплину',2,1);
insert  into `u_test`(`id`,`title`,`user_id`,`subject_id`) values (2,'Новый',2,1);

/*Table structure for table `u_test_answer` */

DROP TABLE IF EXISTS `u_test_answer`;

CREATE TABLE `u_test_answer` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `question_id` int(11) unsigned DEFAULT NULL,
  `right_answer` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `u_test_answer_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `u_test_question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_test_answer` */

insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (1,'qwe',1,'0');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (2,'222',1,'1');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (3,'',2,'25');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (4,'Своё дело',3,'1');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (5,'семья',3,'2');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (6,'Путешествие за границу',3,'3');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (7,'Английский',3,'4');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (8,'Успех в спорте',3,'5');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (9,'Интерфейс',4,'0');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (10,'Абстрактный',4,'0');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (11,'Интерфейс и абстрактный',4,'1');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (12,'1',5,'0');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (13,'2',5,'1');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (14,'3',5,'0');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (15,'1',6,'0');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (16,'2',6,'1');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (17,'3',6,'0');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (18,'1',7,'0');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (19,'2',7,'1');
insert  into `u_test_answer`(`id`,`title`,`question_id`,`right_answer`) values (20,'3',7,'0');

/*Table structure for table `u_test_question` */

DROP TABLE IF EXISTS `u_test_question`;

CREATE TABLE `u_test_question` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `text` text COLLATE utf8_unicode_ci NOT NULL,
  `type` varbinary(10) DEFAULT NULL,
  `test_id` int(11) unsigned DEFAULT NULL,
  `ord` int(11) NOT NULL,
  `parent_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `test_id` (`test_id`),
  CONSTRAINT `u_test_question_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `u_test` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_test_question` */

insert  into `u_test_question`(`id`,`text`,`type`,`test_id`,`ord`,`parent_id`) values (1,'qwe','one',1,1469994208,0);
insert  into `u_test_question`(`id`,`text`,`type`,`test_id`,`ord`,`parent_id`) values (2,'Сколько вам\r\nлет, а?','match',1,1470060891,0);
insert  into `u_test_question`(`id`,`text`,`type`,`test_id`,`ord`,`parent_id`) values (3,'Расставьте по порядку','order',1,1470061046,0);
insert  into `u_test_question`(`id`,`text`,`type`,`test_id`,`ord`,`parent_id`) values (4,'&lt;p&gt;&lt;strong&gt;Абстрактный &lt;/strong&gt;(Abstract) класс - класс, который имеет хотя б 1 абстрактный (не определенный) метод; обозначается как abstract.&lt;br /&gt;\r\n&lt;br /&gt;\r\n&lt;strong&gt;Интерфейс &lt;/strong&gt;- такой же абстрактный класс,только в нем не может быть свойств и не определены тела у методов.&lt;br /&gt;\r\n&lt;br /&gt;\r\nТак же стоит заметить, что абстрактный класс наследуется(etxends), а интерфейс реализуется (implements). Вот и возникает разница между ними, что наследовать мы можем только 1 класс, а реализовать сколько угодно.&lt;br /&gt;\r\n&lt;span style=&quot;color:#ff0000&quot;&gt;ВАЖНО!&lt;/span&gt; При реализации интерфейса, необходимо реализовать все его методы, иначе будет Fatal error, так же это можно избежать, присвоив слово abstract.&lt;br /&gt;\r\n&lt;br /&gt;\r\n&lt;em&gt;Пример:&lt;/em&gt;&lt;/p&gt;\r\n\r\n&lt;pre&gt;\r\n&lt;code class=&quot;language-php&quot;&gt;interface I { \r\n    public function F(); \r\n    public function say(); \r\n} \r\n \r\nabstract class A implements I { \r\n    function say() { \r\n        echo \'Hello\'; \r\n    } \r\n    // function F() - не реализована \r\n} &lt;/code&gt;&lt;/pre&gt;','one',1,1472490692,0);
insert  into `u_test_question`(`id`,`text`,`type`,`test_id`,`ord`,`parent_id`) values (5,'&lt;p&gt;qwe&lt;/p&gt;','one',1,1475576472,NULL);
insert  into `u_test_question`(`id`,`text`,`type`,`test_id`,`ord`,`parent_id`) values (6,'&lt;p&gt;qwe&lt;/p&gt;','one',1,1475576521,NULL);
insert  into `u_test_question`(`id`,`text`,`type`,`test_id`,`ord`,`parent_id`) values (7,'&lt;p&gt;qwe&lt;/p&gt;','one',1,1475576568,NULL);

/*Table structure for table `u_univer_data` */

DROP TABLE IF EXISTS `u_univer_data`;

CREATE TABLE `u_univer_data` (
  `id` int(1) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8_unicode_ci,
  `phone` text COLLATE utf8_unicode_ci,
  `logo` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fullname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_univer_data` */

insert  into `u_univer_data`(`id`,`name`,`address`,`phone`,`logo`,`fullname`) values (1,'','Адрес(а)','&lt;p&gt;&lt;strong&gt;qweqw&lt;/strong&gt;&lt;/p&gt;\r\n\r\n&lt;p&gt;&lt;em&gt;&lt;strong&gt;adfas&lt;/strong&gt;&lt;/em&gt;&lt;/p&gt;\r\n\r\n&lt;pre&gt;\r\n&lt;em&gt;&lt;span style=&quot;color:#ff8c00&quot;&gt;&lt;strong&gt;asasfasf&lt;/strong&gt;&lt;/span&gt;&lt;/em&gt;&lt;/pre&gt;\r\n\r\n&lt;pre&gt;\r\n&lt;code class=&quot;language-php&quot;&gt;&amp;lt;?php\r\n\r\nclass AdminSettingController extends USiteController {\r\n\r\n    protected $routeMap = array(    \r\n        \'actionDefault\' =&amp;gt; \'show\',\r\n        \'setTitle\' =&amp;gt; \'Информация о вузе\'\r\n    );\r\n\r\n    public function run()\r\n    {           \r\n        $result = $this-&amp;gt;model-&amp;gt;doAction($this-&amp;gt;action);        \r\n        $html = $this-&amp;gt;loadView(\'\', $result);        \r\n        $this-&amp;gt;putModContent($html);         \r\n    }\r\n}&lt;/code&gt;&lt;/pre&gt;\r\n\r\n&lt;p&gt;&amp;nbsp;&lt;/p&gt;',NULL,'');

/*Table structure for table `u_univer_faculty` */

DROP TABLE IF EXISTS `u_univer_faculty`;

CREATE TABLE `u_univer_faculty` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_univer_faculty` */

insert  into `u_univer_faculty`(`id`,`title`,`alias`) values (1,'Факультет информационных технологий','fakultet-informatsionnyih-tehnologiy');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_univer_group` */

insert  into `u_univer_group`(`id`,`title`,`speciality_id`,`alias`) values (1,'АОС-10',1,'aos-10');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_univer_speciality` */

insert  into `u_univer_speciality`(`id`,`title`,`faculty_id`,`code`) values (1,'Система автоматизии и управления',1,'101259');

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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Data for the table `u_user` */

insert  into `u_user`(`id`,`role`,`login`,`password`,`salt`,`name`,`last_name`,`surname`,`phone`,`email`,`post`,`group_id`) values (1,'admin','admin','204963f8182798b2d611032800b73011','3dlj\"%O','Администратор','Администратор','Администратор',NULL,NULL,NULL,NULL);
insert  into `u_user`(`id`,`role`,`login`,`password`,`salt`,`name`,`last_name`,`surname`,`phone`,`email`,`post`,`group_id`) values (2,'prepod','prepod02','edebc28b57332195e0fdaf0c1b700570','1!:wq`n','Ольга','Иванова','Ивановна','','admin','old_prepod',NULL);
insert  into `u_user`(`id`,`role`,`login`,`password`,`salt`,`name`,`last_name`,`surname`,`phone`,`email`,`post`,`group_id`) values (3,'student','student03','f3f157bf2123f84765267d8b5978d6b2','7UFkl','Павел','Боровских','Сергеевич',NULL,NULL,NULL,1);
insert  into `u_user`(`id`,`role`,`login`,`password`,`salt`,`name`,`last_name`,`surname`,`phone`,`email`,`post`,`group_id`) values (4,'student','student04','bc39f2f10b12b0f061b45c1db5025af0','(RFm5','Василий','Пупкин','Иванович',NULL,NULL,NULL,1);
insert  into `u_user`(`id`,`role`,`login`,`password`,`salt`,`name`,`last_name`,`surname`,`phone`,`email`,`post`,`group_id`) values (5,'student','student05','f6947fc19bb3362d3f4dec3f12606604','ok~6Y[sE','Сергей','Сергеев','Сергеевич',NULL,NULL,NULL,1);
insert  into `u_user`(`id`,`role`,`login`,`password`,`salt`,`name`,`last_name`,`surname`,`phone`,`email`,`post`,`group_id`) values (6,'student','student06','9c6d4209f48d6b0bad1f33f7b20366d8','cn=0&*<_c','Анонимустик','Пупкин','Валерьянович',NULL,NULL,NULL,1);
insert  into `u_user`(`id`,`role`,`login`,`password`,`salt`,`name`,`last_name`,`surname`,`phone`,`email`,`post`,`group_id`) values (7,'prepod','prepod07','34ae18ba1d119bea92d09e3c9c357a09','RgX9uB','Наталья','Огородникова','Борисовна','','','docent',NULL);

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

insert  into `u_user_roles`(`id`,`type`,`group`,`name`) values (1,'admin','0','Администратор');
insert  into `u_user_roles`(`id`,`type`,`group`,`name`) values (2,'prepod','0','Преподаватель');
insert  into `u_user_roles`(`id`,`type`,`group`,`name`) values (3,'student','0','Студент');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
