-- MySQL dump 10.19  Distrib 10.3.32-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: chemwiki
-- ------------------------------------------------------
-- Server version	10.3.32-MariaDB-0ubuntu0.20.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `actor`
--

DROP TABLE IF EXISTS `actor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actor` (
  `actor_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actor_user` int(10) unsigned DEFAULT NULL,
  `actor_name` varbinary(255) NOT NULL,
  PRIMARY KEY (`actor_id`),
  UNIQUE KEY `actor_name` (`actor_name`),
  UNIQUE KEY `actor_user` (`actor_user`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `actor`
--

LOCK TABLES `actor` WRITE;
/*!40000 ALTER TABLE `actor` DISABLE KEYS */;
INSERT INTO `actor` VALUES (1,1,'WikiSysop'),(2,2,'MediaWiki default'),(5,NULL,'127.0.0.1');
/*!40000 ALTER TABLE `actor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `archive`
--

DROP TABLE IF EXISTS `archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `archive` (
  `ar_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ar_namespace` int(11) NOT NULL DEFAULT 0,
  `ar_title` varbinary(255) NOT NULL DEFAULT '',
  `ar_comment_id` bigint(20) unsigned NOT NULL,
  `ar_actor` bigint(20) unsigned NOT NULL,
  `ar_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `ar_minor_edit` tinyint(4) NOT NULL DEFAULT 0,
  `ar_rev_id` int(10) unsigned NOT NULL,
  `ar_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `ar_len` int(10) unsigned DEFAULT NULL,
  `ar_page_id` int(10) unsigned DEFAULT NULL,
  `ar_parent_id` int(10) unsigned DEFAULT NULL,
  `ar_sha1` varbinary(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`ar_id`),
  UNIQUE KEY `ar_revid_uniq` (`ar_rev_id`),
  KEY `name_title_timestamp` (`ar_namespace`,`ar_title`,`ar_timestamp`),
  KEY `ar_actor_timestamp` (`ar_actor`,`ar_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `archive`
--

LOCK TABLES `archive` WRITE;
/*!40000 ALTER TABLE `archive` DISABLE KEYS */;
/*!40000 ALTER TABLE `archive` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bot_passwords`
--

DROP TABLE IF EXISTS `bot_passwords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bot_passwords` (
  `bp_user` int(10) unsigned NOT NULL,
  `bp_app_id` varbinary(32) NOT NULL,
  `bp_password` tinyblob NOT NULL,
  `bp_token` binary(32) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `bp_restrictions` blob NOT NULL,
  `bp_grants` blob NOT NULL,
  PRIMARY KEY (`bp_user`,`bp_app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bot_passwords`
--

LOCK TABLES `bot_passwords` WRITE;
/*!40000 ALTER TABLE `bot_passwords` DISABLE KEYS */;
/*!40000 ALTER TABLE `bot_passwords` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category` (
  `cat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cat_title` varbinary(255) NOT NULL,
  `cat_pages` int(11) NOT NULL DEFAULT 0,
  `cat_subcats` int(11) NOT NULL DEFAULT 0,
  `cat_files` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`cat_id`),
  UNIQUE KEY `cat_title` (`cat_title`),
  KEY `cat_pages` (`cat_pages`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category`
--

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` VALUES (1,'Imported_vocabulary',3,0,0);
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorylinks`
--

DROP TABLE IF EXISTS `categorylinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categorylinks` (
  `cl_from` int(10) unsigned NOT NULL DEFAULT 0,
  `cl_to` varbinary(255) NOT NULL DEFAULT '',
  `cl_sortkey` varbinary(230) NOT NULL DEFAULT '',
  `cl_sortkey_prefix` varbinary(255) NOT NULL DEFAULT '',
  `cl_timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cl_collation` varbinary(32) NOT NULL DEFAULT '',
  `cl_type` enum('page','subcat','file') NOT NULL DEFAULT 'page',
  PRIMARY KEY (`cl_from`,`cl_to`),
  KEY `cl_sortkey` (`cl_to`,`cl_type`,`cl_sortkey`,`cl_from`),
  KEY `cl_timestamp` (`cl_to`,`cl_timestamp`),
  KEY `cl_collation_ext` (`cl_collation`,`cl_to`,`cl_type`,`cl_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorylinks`
--

LOCK TABLES `categorylinks` WRITE;
/*!40000 ALTER TABLE `categorylinks` DISABLE KEYS */;
INSERT INTO `categorylinks` VALUES (4,'Imported_vocabulary','SMW IMPORT SKOS','','2022-02-04 13:12:46','uppercase','page'),(5,'Imported_vocabulary','SMW IMPORT FOAF','','2022-02-04 13:12:47','uppercase','page'),(6,'Imported_vocabulary','SMW IMPORT OWL','','2022-02-04 13:12:47','uppercase','page');
/*!40000 ALTER TABLE `categorylinks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `change_tag`
--

DROP TABLE IF EXISTS `change_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `change_tag` (
  `ct_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ct_rc_id` int(11) DEFAULT NULL,
  `ct_log_id` int(10) unsigned DEFAULT NULL,
  `ct_rev_id` int(10) unsigned DEFAULT NULL,
  `ct_params` blob DEFAULT NULL,
  `ct_tag_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ct_id`),
  UNIQUE KEY `change_tag_rc_tag_id` (`ct_rc_id`,`ct_tag_id`),
  UNIQUE KEY `change_tag_log_tag_id` (`ct_log_id`,`ct_tag_id`),
  UNIQUE KEY `change_tag_rev_tag_id` (`ct_rev_id`,`ct_tag_id`),
  KEY `change_tag_tag_id_id` (`ct_tag_id`,`ct_rc_id`,`ct_rev_id`,`ct_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `change_tag`
--

LOCK TABLES `change_tag` WRITE;
/*!40000 ALTER TABLE `change_tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `change_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `change_tag_def`
--

DROP TABLE IF EXISTS `change_tag_def`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `change_tag_def` (
  `ctd_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ctd_name` varbinary(255) NOT NULL,
  `ctd_user_defined` tinyint(1) NOT NULL,
  `ctd_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`ctd_id`),
  UNIQUE KEY `ctd_name` (`ctd_name`),
  KEY `ctd_count` (`ctd_count`),
  KEY `ctd_user_defined` (`ctd_user_defined`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `change_tag_def`
--

LOCK TABLES `change_tag_def` WRITE;
/*!40000 ALTER TABLE `change_tag_def` DISABLE KEYS */;
/*!40000 ALTER TABLE `change_tag_def` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comment`
--

DROP TABLE IF EXISTS `comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comment` (
  `comment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_hash` int(11) NOT NULL,
  `comment_text` blob NOT NULL,
  `comment_data` blob DEFAULT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `comment_hash` (`comment_hash`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comment`
--

LOCK TABLES `comment` WRITE;
/*!40000 ALTER TABLE `comment` DISABLE KEYS */;
INSERT INTO `comment` VALUES (1,0,'',NULL),(2,-1031124222,'Semantic MediaWiki group import',NULL),(3,1410840408,'Semantic MediaWiki default vocabulary import',NULL);
/*!40000 ALTER TABLE `comment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `content`
--

DROP TABLE IF EXISTS `content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `content` (
  `content_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `content_size` int(10) unsigned NOT NULL,
  `content_sha1` varbinary(32) NOT NULL,
  `content_model` smallint(5) unsigned NOT NULL,
  `content_address` varbinary(255) NOT NULL,
  PRIMARY KEY (`content_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `content`
--

LOCK TABLES `content` WRITE;
/*!40000 ALTER TABLE `content` DISABLE KEYS */;
INSERT INTO `content` VALUES (1,784,'ezw1y7tpy380c53gmx6sikr7wj415iu',1,'tt:1'),(2,589,'tmz3l2uctisu9w7umutjhmul9gs7lzh',2,'tt:2'),(3,2155,'fk6zda7duib0qaof4ucwc7o8cgcsd4l',2,'tt:3'),(4,982,'7uej7l6j7zibffqxeqr83oypfu8tya3',1,'tt:4'),(5,298,'54j1f1u0gxrlqu4877gsk6vv72gs354',1,'tt:5'),(6,1196,'mjrj8ysg8aclt8sddeabn26vvadmsuy',1,'tt:6'),(7,227,'r84n0cewys8sf532il1jpkj3virql2i',1,'tt:7'),(8,154,'4ywl3swqwhp0fsjdfg6poaq2d6n77xf',1,'tt:8'),(9,198,'0yfosgdiyxnbl3w8m6h2o3nd5ehtb6u',1,'tt:9'),(10,209,'p482vcrjzqtstzps5n3lp9i5hzy2scs',1,'tt:10');
/*!40000 ALTER TABLE `content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `content_models`
--

DROP TABLE IF EXISTS `content_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `content_models` (
  `model_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `model_name` varbinary(64) NOT NULL,
  PRIMARY KEY (`model_id`),
  UNIQUE KEY `model_name` (`model_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `content_models`
--

LOCK TABLES `content_models` WRITE;
/*!40000 ALTER TABLE `content_models` DISABLE KEYS */;
INSERT INTO `content_models` VALUES (2,'smw/schema'),(1,'wikitext');
/*!40000 ALTER TABLE `content_models` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `externallinks`
--

DROP TABLE IF EXISTS `externallinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `externallinks` (
  `el_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `el_from` int(10) unsigned NOT NULL DEFAULT 0,
  `el_to` blob NOT NULL,
  `el_index` blob NOT NULL,
  `el_index_60` varbinary(60) NOT NULL,
  PRIMARY KEY (`el_id`),
  KEY `el_from` (`el_from`,`el_to`(40)),
  KEY `el_to` (`el_to`(60),`el_from`),
  KEY `el_index` (`el_index`(60)),
  KEY `el_index_60` (`el_index_60`,`el_id`),
  KEY `el_from_index_60` (`el_from`,`el_index_60`,`el_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `externallinks`
--

LOCK TABLES `externallinks` WRITE;
/*!40000 ALTER TABLE `externallinks` DISABLE KEYS */;
INSERT INTO `externallinks` VALUES (1,4,'http://www.w3.org/TR/skos-reference/skos.rdf','http://org.w3.www./TR/skos-reference/skos.rdf','http://org.w3.www./TR/skos-reference/skos.rdf'),(2,4,'http://www.w3.org/2004/02/skos/core#%7C','http://org.w3.www./2004/02/skos/core#%7C','http://org.w3.www./2004/02/skos/core#%7C'),(3,5,'http://www.foaf-project.org/','http://org.foaf-project.www./','http://org.foaf-project.www./'),(4,5,'http://xmlns.com/foaf/0.1/%7C','http://com.xmlns./foaf/0.1/%7C','http://com.xmlns./foaf/0.1/%7C'),(5,6,'http://www.w3.org/2002/07/owl','http://org.w3.www./2002/07/owl','http://org.w3.www./2002/07/owl'),(6,6,'http://www.w3.org/2002/07/owl#%7C','http://org.w3.www./2002/07/owl#%7C','http://org.w3.www./2002/07/owl#%7C');
/*!40000 ALTER TABLE `externallinks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `filearchive`
--

DROP TABLE IF EXISTS `filearchive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `filearchive` (
  `fa_id` int(11) NOT NULL AUTO_INCREMENT,
  `fa_name` varbinary(255) NOT NULL DEFAULT '',
  `fa_archive_name` varbinary(255) DEFAULT '',
  `fa_storage_group` varbinary(16) DEFAULT NULL,
  `fa_storage_key` varbinary(64) DEFAULT '',
  `fa_deleted_user` int(11) DEFAULT NULL,
  `fa_deleted_timestamp` binary(14) DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `fa_deleted_reason_id` bigint(20) unsigned NOT NULL,
  `fa_size` int(10) unsigned DEFAULT 0,
  `fa_width` int(11) DEFAULT 0,
  `fa_height` int(11) DEFAULT 0,
  `fa_metadata` mediumblob DEFAULT NULL,
  `fa_bits` int(11) DEFAULT 0,
  `fa_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE','3D') DEFAULT NULL,
  `fa_major_mime` enum('unknown','application','audio','image','text','video','message','model','multipart','chemical') DEFAULT 'unknown',
  `fa_minor_mime` varbinary(100) DEFAULT 'unknown',
  `fa_description_id` bigint(20) unsigned NOT NULL,
  `fa_actor` bigint(20) unsigned NOT NULL,
  `fa_timestamp` binary(14) DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `fa_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `fa_sha1` varbinary(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`fa_id`),
  KEY `fa_name` (`fa_name`,`fa_timestamp`),
  KEY `fa_storage_group` (`fa_storage_group`,`fa_storage_key`),
  KEY `fa_deleted_timestamp` (`fa_deleted_timestamp`),
  KEY `fa_actor_timestamp` (`fa_actor`,`fa_timestamp`),
  KEY `fa_sha1` (`fa_sha1`(10))
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `filearchive`
--

LOCK TABLES `filearchive` WRITE;
/*!40000 ALTER TABLE `filearchive` DISABLE KEYS */;
/*!40000 ALTER TABLE `filearchive` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `image`
--

DROP TABLE IF EXISTS `image`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `image` (
  `img_name` varbinary(255) NOT NULL DEFAULT '',
  `img_size` int(10) unsigned NOT NULL DEFAULT 0,
  `img_width` int(11) NOT NULL DEFAULT 0,
  `img_height` int(11) NOT NULL DEFAULT 0,
  `img_metadata` mediumblob NOT NULL,
  `img_bits` int(11) NOT NULL DEFAULT 0,
  `img_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE','3D') DEFAULT NULL,
  `img_major_mime` enum('unknown','application','audio','image','text','video','message','model','multipart','chemical') NOT NULL DEFAULT 'unknown',
  `img_minor_mime` varbinary(100) NOT NULL DEFAULT 'unknown',
  `img_description_id` bigint(20) unsigned NOT NULL,
  `img_actor` bigint(20) unsigned NOT NULL,
  `img_timestamp` varbinary(14) NOT NULL DEFAULT '',
  `img_sha1` varbinary(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`img_name`),
  KEY `img_actor_timestamp` (`img_actor`,`img_timestamp`),
  KEY `img_size` (`img_size`),
  KEY `img_timestamp` (`img_timestamp`),
  KEY `img_sha1` (`img_sha1`(10)),
  KEY `img_media_mime` (`img_media_type`,`img_major_mime`,`img_minor_mime`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `image`
--

LOCK TABLES `image` WRITE;
/*!40000 ALTER TABLE `image` DISABLE KEYS */;
/*!40000 ALTER TABLE `image` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `imagelinks`
--

DROP TABLE IF EXISTS `imagelinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imagelinks` (
  `il_from` int(10) unsigned NOT NULL DEFAULT 0,
  `il_from_namespace` int(11) NOT NULL DEFAULT 0,
  `il_to` varbinary(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`il_from`,`il_to`),
  KEY `il_to` (`il_to`,`il_from`),
  KEY `il_backlinks_namespace` (`il_from_namespace`,`il_to`,`il_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `imagelinks`
--

LOCK TABLES `imagelinks` WRITE;
/*!40000 ALTER TABLE `imagelinks` DISABLE KEYS */;
/*!40000 ALTER TABLE `imagelinks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interwiki`
--

DROP TABLE IF EXISTS `interwiki`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `interwiki` (
  `iw_prefix` varbinary(32) NOT NULL,
  `iw_url` blob NOT NULL,
  `iw_api` blob NOT NULL,
  `iw_wikiid` varbinary(64) NOT NULL,
  `iw_local` tinyint(1) NOT NULL,
  `iw_trans` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`iw_prefix`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interwiki`
--

LOCK TABLES `interwiki` WRITE;
/*!40000 ALTER TABLE `interwiki` DISABLE KEYS */;
INSERT INTO `interwiki` VALUES ('acronym','https://www.acronymfinder.com/~/search/af.aspx?string=exact&Acronym=$1','','',0,0),('advogato','http://www.advogato.org/$1','','',0,0),('arxiv','https://www.arxiv.org/abs/$1','','',0,0),('c2find','http://c2.com/cgi/wiki?FindPage&value=$1','','',0,0),('cache','https://www.google.com/search?q=cache:$1','','',0,0),('commons','https://commons.wikimedia.org/wiki/$1','https://commons.wikimedia.org/w/api.php','',0,0),('dictionary','http://www.dict.org/bin/Dict?Database=*&Form=Dict1&Strategy=*&Query=$1','','',0,0),('doi','https://dx.doi.org/$1','','',0,0),('drumcorpswiki','http://www.drumcorpswiki.com/$1','http://drumcorpswiki.com/api.php','',0,0),('dwjwiki','http://www.suberic.net/cgi-bin/dwj/wiki.cgi?$1','','',0,0),('elibre','http://enciclopedia.us.es/index.php/$1','http://enciclopedia.us.es/api.php','',0,0),('emacswiki','https://www.emacswiki.org/emacs/$1','','',0,0),('foldoc','https://foldoc.org/?$1','','',0,0),('foxwiki','https://fox.wikis.com/wc.dll?Wiki~$1','','',0,0),('freebsdman','https://www.FreeBSD.org/cgi/man.cgi?apropos=1&query=$1','','',0,0),('gentoo-wiki','http://gentoo-wiki.com/$1','','',0,0),('google','https://www.google.com/search?q=$1','','',0,0),('googlegroups','https://groups.google.com/groups?q=$1','','',0,0),('hammondwiki','http://www.dairiki.org/HammondWiki/$1','','',0,0),('hrwiki','http://www.hrwiki.org/wiki/$1','http://www.hrwiki.org/w/api.php','',0,0),('imdb','http://www.imdb.com/find?q=$1&tt=on','','',0,0),('kmwiki','https://kmwiki.wikispaces.com/$1','','',0,0),('linuxwiki','http://linuxwiki.de/$1','','',0,0),('lojban','https://mw.lojban.org/papri/$1','','',0,0),('lqwiki','http://wiki.linuxquestions.org/wiki/$1','','',0,0),('meatball','http://www.usemod.com/cgi-bin/mb.pl?$1','','',0,0),('mediawikiwiki','https://www.mediawiki.org/wiki/$1','https://www.mediawiki.org/w/api.php','',0,0),('memoryalpha','http://en.memory-alpha.org/wiki/$1','http://en.memory-alpha.org/api.php','',0,0),('metawiki','http://sunir.org/apps/meta.pl?$1','','',0,0),('metawikimedia','https://meta.wikimedia.org/wiki/$1','https://meta.wikimedia.org/w/api.php','',0,0),('mozillawiki','https://wiki.mozilla.org/$1','https://wiki.mozilla.org/api.php','',0,0),('mw','https://www.mediawiki.org/wiki/$1','https://www.mediawiki.org/w/api.php','',0,0),('oeis','https://oeis.org/$1','','',0,0),('openwiki','http://openwiki.com/ow.asp?$1','','',0,0),('pmid','https://www.ncbi.nlm.nih.gov/pubmed/$1?dopt=Abstract','','',0,0),('pythoninfo','https://wiki.python.org/moin/$1','','',0,0),('rfc','https://tools.ietf.org/html/rfc$1','','',0,0),('s23wiki','http://s23.org/wiki/$1','http://s23.org/w/api.php','',0,0),('seattlewireless','http://seattlewireless.net/$1','','',0,0),('senseislibrary','https://senseis.xmp.net/?$1','','',0,0),('shoutwiki','http://www.shoutwiki.com/wiki/$1','http://www.shoutwiki.com/w/api.php','',0,0),('squeak','http://wiki.squeak.org/squeak/$1','','',0,0),('theopedia','https://www.theopedia.com/$1','','',0,0),('tmbw','http://www.tmbw.net/wiki/$1','http://tmbw.net/wiki/api.php','',0,0),('tmnet','http://www.technomanifestos.net/?$1','','',0,0),('twiki','http://twiki.org/cgi-bin/view/$1','','',0,0),('uncyclopedia','https://en.uncyclopedia.co/wiki/$1','https://en.uncyclopedia.co/w/api.php','',0,0),('unreal','https://wiki.beyondunreal.com/$1','https://wiki.beyondunreal.com/w/api.php','',0,0),('usemod','http://www.usemod.com/cgi-bin/wiki.pl?$1','','',0,0),('wiki','http://c2.com/cgi/wiki?$1','','',0,0),('wikia','http://www.wikia.com/wiki/$1','','',0,0),('wikibooks','https://en.wikibooks.org/wiki/$1','https://en.wikibooks.org/w/api.php','',0,0),('wikidata','https://www.wikidata.org/wiki/$1','https://www.wikidata.org/w/api.php','',0,0),('wikif1','http://www.wikif1.org/$1','','',0,0),('wikihow','https://www.wikihow.com/$1','https://www.wikihow.com/api.php','',0,0),('wikimedia','https://foundation.wikimedia.org/wiki/$1','https://foundation.wikimedia.org/w/api.php','',0,0),('wikinews','https://en.wikinews.org/wiki/$1','https://en.wikinews.org/w/api.php','',0,0),('wikinfo','http://wikinfo.co/English/index.php/$1','','',0,0),('wikipedia','https://en.wikipedia.org/wiki/$1','https://en.wikipedia.org/w/api.php','',0,0),('wikiquote','https://en.wikiquote.org/wiki/$1','https://en.wikiquote.org/w/api.php','',0,0),('wikisource','https://wikisource.org/wiki/$1','https://wikisource.org/w/api.php','',0,0),('wikispecies','https://species.wikimedia.org/wiki/$1','https://species.wikimedia.org/w/api.php','',0,0),('wikiversity','https://en.wikiversity.org/wiki/$1','https://en.wikiversity.org/w/api.php','',0,0),('wikivoyage','https://en.wikivoyage.org/wiki/$1','https://en.wikivoyage.org/w/api.php','',0,0),('wikt','https://en.wiktionary.org/wiki/$1','https://en.wiktionary.org/w/api.php','',0,0),('wiktionary','https://en.wiktionary.org/wiki/$1','https://en.wiktionary.org/w/api.php','',0,0);
/*!40000 ALTER TABLE `interwiki` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ip_changes`
--

DROP TABLE IF EXISTS `ip_changes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ip_changes` (
  `ipc_rev_id` int(10) unsigned NOT NULL DEFAULT 0,
  `ipc_rev_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `ipc_hex` varbinary(35) NOT NULL DEFAULT '',
  PRIMARY KEY (`ipc_rev_id`),
  KEY `ipc_rev_timestamp` (`ipc_rev_timestamp`),
  KEY `ipc_hex_time` (`ipc_hex`,`ipc_rev_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ip_changes`
--

LOCK TABLES `ip_changes` WRITE;
/*!40000 ALTER TABLE `ip_changes` DISABLE KEYS */;
INSERT INTO `ip_changes` VALUES (2,'20220204131242','7F000001'),(3,'20220204131246','7F000001'),(4,'20220204131246','7F000001'),(5,'20220204131247','7F000001'),(6,'20220204131247','7F000001'),(7,'20220204131247','7F000001'),(8,'20220204131248','7F000001'),(9,'20220204131248','7F000001'),(10,'20220204131249','7F000001');
/*!40000 ALTER TABLE `ip_changes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ipblocks`
--

DROP TABLE IF EXISTS `ipblocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ipblocks` (
  `ipb_id` int(11) NOT NULL AUTO_INCREMENT,
  `ipb_address` tinyblob NOT NULL,
  `ipb_user` int(10) unsigned NOT NULL DEFAULT 0,
  `ipb_by_actor` bigint(20) unsigned NOT NULL,
  `ipb_reason_id` bigint(20) unsigned NOT NULL,
  `ipb_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `ipb_auto` tinyint(1) NOT NULL DEFAULT 0,
  `ipb_anon_only` tinyint(1) NOT NULL DEFAULT 0,
  `ipb_create_account` tinyint(1) NOT NULL DEFAULT 1,
  `ipb_enable_autoblock` tinyint(1) NOT NULL DEFAULT 1,
  `ipb_expiry` varbinary(14) NOT NULL DEFAULT '',
  `ipb_range_start` tinyblob NOT NULL,
  `ipb_range_end` tinyblob NOT NULL,
  `ipb_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `ipb_block_email` tinyint(1) NOT NULL DEFAULT 0,
  `ipb_allow_usertalk` tinyint(1) NOT NULL DEFAULT 0,
  `ipb_parent_block_id` int(11) DEFAULT NULL,
  `ipb_sitewide` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ipb_id`),
  UNIQUE KEY `ipb_address_unique` (`ipb_address`(255),`ipb_user`,`ipb_auto`),
  KEY `ipb_user` (`ipb_user`),
  KEY `ipb_range` (`ipb_range_start`(8),`ipb_range_end`(8)),
  KEY `ipb_timestamp` (`ipb_timestamp`),
  KEY `ipb_expiry` (`ipb_expiry`),
  KEY `ipb_parent_block_id` (`ipb_parent_block_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipblocks`
--

LOCK TABLES `ipblocks` WRITE;
/*!40000 ALTER TABLE `ipblocks` DISABLE KEYS */;
/*!40000 ALTER TABLE `ipblocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ipblocks_restrictions`
--

DROP TABLE IF EXISTS `ipblocks_restrictions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ipblocks_restrictions` (
  `ir_ipb_id` int(11) NOT NULL,
  `ir_type` tinyint(1) NOT NULL,
  `ir_value` int(11) NOT NULL,
  PRIMARY KEY (`ir_ipb_id`,`ir_type`,`ir_value`),
  KEY `ir_type_value` (`ir_type`,`ir_value`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipblocks_restrictions`
--

LOCK TABLES `ipblocks_restrictions` WRITE;
/*!40000 ALTER TABLE `ipblocks_restrictions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ipblocks_restrictions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `iwlinks`
--

DROP TABLE IF EXISTS `iwlinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `iwlinks` (
  `iwl_from` int(10) unsigned NOT NULL DEFAULT 0,
  `iwl_prefix` varbinary(32) NOT NULL DEFAULT '',
  `iwl_title` varbinary(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`iwl_from`,`iwl_prefix`,`iwl_title`),
  KEY `iwl_prefix_title_from` (`iwl_prefix`,`iwl_title`,`iwl_from`),
  KEY `iwl_prefix_from_title` (`iwl_prefix`,`iwl_from`,`iwl_title`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `iwlinks`
--

LOCK TABLES `iwlinks` WRITE;
/*!40000 ALTER TABLE `iwlinks` DISABLE KEYS */;
/*!40000 ALTER TABLE `iwlinks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job`
--

DROP TABLE IF EXISTS `job`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job` (
  `job_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_cmd` varbinary(60) NOT NULL DEFAULT '',
  `job_namespace` int(11) NOT NULL,
  `job_title` varbinary(255) NOT NULL,
  `job_timestamp` varbinary(14) DEFAULT NULL,
  `job_params` mediumblob NOT NULL,
  `job_random` int(10) unsigned NOT NULL DEFAULT 0,
  `job_attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `job_token` varbinary(32) NOT NULL DEFAULT '',
  `job_token_timestamp` varbinary(14) DEFAULT NULL,
  `job_sha1` varbinary(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`job_id`),
  KEY `job_sha1` (`job_sha1`),
  KEY `job_cmd_token` (`job_cmd`,`job_token`,`job_random`),
  KEY `job_cmd_token_id` (`job_cmd`,`job_token`,`job_id`),
  KEY `job_cmd` (`job_cmd`,`job_namespace`,`job_title`,`job_params`(128)),
  KEY `job_timestamp` (`job_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job`
--

LOCK TABLES `job` WRITE;
/*!40000 ALTER TABLE `job` DISABLE KEYS */;
INSERT INTO `job` VALUES (1,'smw.propertyStatisticsRebuild',0,'SMW\\SQLStore\\Installer','20220204131240','a:7:{s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"db42ba0748fde875c7dd60ebc4ffd96d265c3390\";s:16:\"rootJobTimestamp\";s:14:\"20220204131240\";s:17:\"waitOnCommandLine\";b:1;s:9:\"namespace\";i:0;s:5:\"title\";s:22:\"SMW\\SQLStore\\Installer\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";}',1214969454,0,'',NULL,'pe610xn4upvlx7lnuoke8cojktg4kdm'),(2,'smw.entityIdDisposer',0,'SMW\\SQLStore\\Installer','20220204131241','a:7:{s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"f7d4c5a67d33c85004251afffefccf7a1c6b631b\";s:16:\"rootJobTimestamp\";s:14:\"20220204131241\";s:17:\"waitOnCommandLine\";b:1;s:9:\"namespace\";i:0;s:5:\"title\";s:22:\"SMW\\SQLStore\\Installer\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";}',1517791779,0,'',NULL,'c4nu5mm5s9jos0l0kwc1ty3wviv3bvc'),(3,'htmlCacheUpdate',112,'Group:Schema_properties','20220204131245','a:10:{s:5:\"table\";s:9:\"pagelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"a55e6438bdceaa907e050edc8513950fc0148a5d\";s:16:\"rootJobTimestamp\";s:14:\"20220204131245\";s:11:\"causeAction\";s:10:\"page-touch\";s:9:\"namespace\";i:112;s:5:\"title\";s:23:\"Group:Schema_properties\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";s:10:\"causeAgent\";s:7:\"unknown\";}',1693168301,0,'',NULL,'re1c0xk6evkcbpq5jeji9f5u0u52unk'),(4,'htmlCacheUpdate',112,'Group:Schema_properties','20220204131245','a:10:{s:5:\"table\";s:13:\"templatelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"69ce3c18f7a6719c22d8dbcf1e35101bfacfba29\";s:16:\"rootJobTimestamp\";s:14:\"20220204131245\";s:11:\"causeAction\";s:11:\"page-create\";s:9:\"namespace\";i:112;s:5:\"title\";s:23:\"Group:Schema_properties\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";s:10:\"causeAgent\";s:7:\"unknown\";}',600006117,0,'',NULL,'imuup91si2v4sdovaj9lsodmhe9l4mv'),(5,'refreshLinksPrioritized',112,'Group:Schema_properties','20220204131246','a:8:{s:9:\"namespace\";i:112;s:5:\"title\";s:23:\"Group:Schema_properties\";s:16:\"rootJobTimestamp\";s:14:\"20220204131242\";s:23:\"useRecursiveLinksUpdate\";b:1;s:14:\"triggeringUser\";a:2:{s:6:\"userId\";i:0;s:8:\"userName\";s:9:\"127.0.0.1\";}s:20:\"triggeringRevisionId\";i:2;s:11:\"causeAction\";s:9:\"edit-page\";s:10:\"causeAgent\";s:9:\"127.0.0.1\";}',671666483,0,'',NULL,'ln42vne5491wyr3f4rxutwszzidmjvr'),(6,'htmlCacheUpdate',112,'Group:Predefined_properties','20220204131246','a:10:{s:5:\"table\";s:9:\"pagelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"11bd329075e84905aec0ec3b49d6cb326d40ce9d\";s:16:\"rootJobTimestamp\";s:14:\"20220204131246\";s:11:\"causeAction\";s:10:\"page-touch\";s:9:\"namespace\";i:112;s:5:\"title\";s:27:\"Group:Predefined_properties\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";s:10:\"causeAgent\";s:7:\"unknown\";}',1994485234,0,'',NULL,'9kajnb8aiqfqi46an4h17jfz5fahbrb'),(7,'htmlCacheUpdate',112,'Group:Predefined_properties','20220204131246','a:10:{s:5:\"table\";s:13:\"templatelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"00d1f60ebc8fa76c3da9fea5bdd63e961208e24c\";s:16:\"rootJobTimestamp\";s:14:\"20220204131246\";s:11:\"causeAction\";s:11:\"page-create\";s:9:\"namespace\";i:112;s:5:\"title\";s:27:\"Group:Predefined_properties\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";s:10:\"causeAgent\";s:7:\"unknown\";}',2056722568,0,'',NULL,'74l0atlo45lpmxsnz3jx1enihoa3ich'),(8,'refreshLinksPrioritized',112,'Group:Predefined_properties','20220204131246','a:8:{s:9:\"namespace\";i:112;s:5:\"title\";s:27:\"Group:Predefined_properties\";s:16:\"rootJobTimestamp\";s:14:\"20220204131246\";s:23:\"useRecursiveLinksUpdate\";b:1;s:14:\"triggeringUser\";a:2:{s:6:\"userId\";i:0;s:8:\"userName\";s:9:\"127.0.0.1\";}s:20:\"triggeringRevisionId\";i:3;s:11:\"causeAction\";s:9:\"edit-page\";s:10:\"causeAgent\";s:9:\"127.0.0.1\";}',1693122934,0,'',NULL,'monr8iqom3xrblr3q6a3f4tbp0jdz6u'),(9,'htmlCacheUpdate',8,'Smw_import_skos','20220204131246','a:10:{s:5:\"table\";s:9:\"pagelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"a7bf1cb3e1dbe726f2b0bdd51eae4431d93e813d\";s:16:\"rootJobTimestamp\";s:14:\"20220204131246\";s:11:\"causeAction\";s:10:\"page-touch\";s:9:\"namespace\";i:8;s:5:\"title\";s:15:\"Smw_import_skos\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";s:10:\"causeAgent\";s:7:\"unknown\";}',1577995189,0,'',NULL,'n1ysusmg2te76bdqf61nqgjtkpq2ftp'),(10,'htmlCacheUpdate',8,'Smw_import_skos','20220204131246','a:10:{s:5:\"table\";s:13:\"templatelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"c8cef9ed49352e8959935d6a31c65893898a5e1d\";s:16:\"rootJobTimestamp\";s:14:\"20220204131246\";s:11:\"causeAction\";s:11:\"page-create\";s:9:\"namespace\";i:8;s:5:\"title\";s:15:\"Smw_import_skos\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";s:10:\"causeAgent\";s:7:\"unknown\";}',1370381442,0,'',NULL,'seoiznsea1m9f7xnulutrrmaoap2d60'),(11,'htmlCacheUpdate',8,'Smw_import_foaf','20220204131247','a:10:{s:5:\"table\";s:9:\"pagelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"0db78f8fa270cda8b487d18e67d508e029c77c1d\";s:16:\"rootJobTimestamp\";s:14:\"20220204131247\";s:11:\"causeAction\";s:10:\"page-touch\";s:9:\"namespace\";i:8;s:5:\"title\";s:15:\"Smw_import_foaf\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";s:10:\"causeAgent\";s:7:\"unknown\";}',1967739326,0,'',NULL,'s2w85htgo2k1tskwb5ki3lr0tm6si6u'),(12,'htmlCacheUpdate',8,'Smw_import_foaf','20220204131247','a:10:{s:5:\"table\";s:13:\"templatelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"2210d88b80eb4c7846ee4a84484183eacdc55af7\";s:16:\"rootJobTimestamp\";s:14:\"20220204131247\";s:11:\"causeAction\";s:11:\"page-create\";s:9:\"namespace\";i:8;s:5:\"title\";s:15:\"Smw_import_foaf\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";s:10:\"causeAgent\";s:7:\"unknown\";}',671595416,0,'',NULL,'gim1trsmeu4yk31zltlz91rfygpwlap'),(13,'htmlCacheUpdate',8,'Smw_import_owl','20220204131247','a:10:{s:5:\"table\";s:9:\"pagelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"c39e85994f34686eb0bc5169bde9cf152cc1537e\";s:16:\"rootJobTimestamp\";s:14:\"20220204131247\";s:11:\"causeAction\";s:10:\"page-touch\";s:9:\"namespace\";i:8;s:5:\"title\";s:14:\"Smw_import_owl\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";s:10:\"causeAgent\";s:7:\"unknown\";}',1363530655,0,'',NULL,'oryqrq17ssszzmy5krcqngzgd75bif1'),(14,'htmlCacheUpdate',8,'Smw_import_owl','20220204131247','a:10:{s:5:\"table\";s:13:\"templatelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"7740089b77256b717c14f3509a27b61bbccb770d\";s:16:\"rootJobTimestamp\";s:14:\"20220204131247\";s:11:\"causeAction\";s:11:\"page-create\";s:9:\"namespace\";i:8;s:5:\"title\";s:14:\"Smw_import_owl\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";s:10:\"causeAgent\";s:7:\"unknown\";}',1257543708,0,'',NULL,'eegb0oxhhrmri6tnnxj4kmm05kiwms2'),(15,'htmlCacheUpdate',102,'Foaf:knows','20220204131248','a:10:{s:5:\"table\";s:9:\"pagelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"f90083ba6a5818880e2c4759258fb527b3e6d14c\";s:16:\"rootJobTimestamp\";s:14:\"20220204131248\";s:11:\"causeAction\";s:10:\"page-touch\";s:9:\"namespace\";i:102;s:5:\"title\";s:10:\"Foaf:knows\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";s:10:\"causeAgent\";s:7:\"unknown\";}',629672003,0,'',NULL,'2b9454257l2f68mxb04w8pw794lq22a'),(16,'htmlCacheUpdate',102,'Foaf:knows','20220204131248','a:10:{s:5:\"table\";s:13:\"templatelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"204a3631f6989c3b57af09467033e00947e1e27b\";s:16:\"rootJobTimestamp\";s:14:\"20220204131248\";s:11:\"causeAction\";s:11:\"page-create\";s:9:\"namespace\";i:102;s:5:\"title\";s:10:\"Foaf:knows\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";s:10:\"causeAgent\";s:7:\"unknown\";}',1243333494,0,'',NULL,'79fljy1vhsioxk6lxauom6elq6wbr7y'),(17,'smw.changePropagationDispatch',102,'Foaf:knows','20220204131248','a:4:{s:17:\"isTypePropagation\";b:1;s:9:\"namespace\";i:102;s:5:\"title\";s:10:\"Foaf:knows\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";}',1363203734,0,'',NULL,'8nljvs7p0hch8sn9lyr8zro4y3ihut1'),(18,'refreshLinksPrioritized',102,'Foaf:knows','20220204131248','a:8:{s:9:\"namespace\";i:102;s:5:\"title\";s:10:\"Foaf:knows\";s:16:\"rootJobTimestamp\";s:14:\"20220204131247\";s:23:\"useRecursiveLinksUpdate\";b:1;s:14:\"triggeringUser\";a:2:{s:6:\"userId\";i:0;s:8:\"userName\";s:9:\"127.0.0.1\";}s:20:\"triggeringRevisionId\";i:7;s:11:\"causeAction\";s:9:\"edit-page\";s:10:\"causeAgent\";s:9:\"127.0.0.1\";}',1740654526,0,'',NULL,'j2tj0wzkuv8yug0ax2wf035dql6xo71'),(19,'htmlCacheUpdate',102,'Foaf:name','20220204131248','a:10:{s:5:\"table\";s:9:\"pagelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"81480dda86392163bf2081bb2c17b528d85019f5\";s:16:\"rootJobTimestamp\";s:14:\"20220204131248\";s:11:\"causeAction\";s:10:\"page-touch\";s:9:\"namespace\";i:102;s:5:\"title\";s:9:\"Foaf:name\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";s:10:\"causeAgent\";s:7:\"unknown\";}',1089358980,0,'',NULL,'6tzzmiut5fix4abwze1omol3z17uexq'),(20,'htmlCacheUpdate',102,'Foaf:name','20220204131248','a:10:{s:5:\"table\";s:13:\"templatelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"85f08eb62697e859310b26cee6530f9e08fdff49\";s:16:\"rootJobTimestamp\";s:14:\"20220204131248\";s:11:\"causeAction\";s:11:\"page-create\";s:9:\"namespace\";i:102;s:5:\"title\";s:9:\"Foaf:name\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";s:10:\"causeAgent\";s:7:\"unknown\";}',1131854323,0,'',NULL,'dhj2dthoxfonuftmbwwtj1tnr8p0dlo'),(21,'smw.changePropagationDispatch',102,'Foaf:name','20220204131248','a:4:{s:17:\"isTypePropagation\";b:1;s:9:\"namespace\";i:102;s:5:\"title\";s:9:\"Foaf:name\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";}',365371901,0,'',NULL,'6igb8rp3n6ybr117xuiwulcyitpmmrx'),(22,'refreshLinksPrioritized',102,'Foaf:name','20220204131248','a:8:{s:9:\"namespace\";i:102;s:5:\"title\";s:9:\"Foaf:name\";s:16:\"rootJobTimestamp\";s:14:\"20220204131248\";s:23:\"useRecursiveLinksUpdate\";b:1;s:14:\"triggeringUser\";a:2:{s:6:\"userId\";i:0;s:8:\"userName\";s:9:\"127.0.0.1\";}s:20:\"triggeringRevisionId\";i:8;s:11:\"causeAction\";s:9:\"edit-page\";s:10:\"causeAgent\";s:9:\"127.0.0.1\";}',686012698,0,'',NULL,'hujpiqvvv3lsiwrj4s7qg9qflnzdl00'),(23,'htmlCacheUpdate',102,'Foaf:homepage','20220204131248','a:10:{s:5:\"table\";s:9:\"pagelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"b920e6c2a025232b0681df61bce1de9e47e227bf\";s:16:\"rootJobTimestamp\";s:14:\"20220204131248\";s:11:\"causeAction\";s:10:\"page-touch\";s:9:\"namespace\";i:102;s:5:\"title\";s:13:\"Foaf:homepage\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";s:10:\"causeAgent\";s:7:\"unknown\";}',1964057295,0,'',NULL,'0gg0wtlmikgmmyw9vnucp7xkhtmnm8h'),(24,'htmlCacheUpdate',102,'Foaf:homepage','20220204131248','a:10:{s:5:\"table\";s:13:\"templatelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"24d55c63c71de556ef49b5cef81cec62a98c2e5f\";s:16:\"rootJobTimestamp\";s:14:\"20220204131248\";s:11:\"causeAction\";s:11:\"page-create\";s:9:\"namespace\";i:102;s:5:\"title\";s:13:\"Foaf:homepage\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";s:10:\"causeAgent\";s:7:\"unknown\";}',548786083,0,'',NULL,'76slsmk27zpwebpe6sw5vkywa2jt5ou'),(25,'smw.changePropagationDispatch',102,'Foaf:homepage','20220204131249','a:4:{s:17:\"isTypePropagation\";b:1;s:9:\"namespace\";i:102;s:5:\"title\";s:13:\"Foaf:homepage\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";}',705556209,0,'',NULL,'mggb2wki4ydxgtwnt3o57gqcdv37qhm'),(26,'refreshLinksPrioritized',102,'Foaf:homepage','20220204131249','a:8:{s:9:\"namespace\";i:102;s:5:\"title\";s:13:\"Foaf:homepage\";s:16:\"rootJobTimestamp\";s:14:\"20220204131248\";s:23:\"useRecursiveLinksUpdate\";b:1;s:14:\"triggeringUser\";a:2:{s:6:\"userId\";i:0;s:8:\"userName\";s:9:\"127.0.0.1\";}s:20:\"triggeringRevisionId\";i:9;s:11:\"causeAction\";s:9:\"edit-page\";s:10:\"causeAgent\";s:9:\"127.0.0.1\";}',514772476,0,'',NULL,'h1kl3jyskcs88rmlizruftx8kwnrvi6'),(27,'htmlCacheUpdate',102,'Owl:differentFrom','20220204131249','a:10:{s:5:\"table\";s:9:\"pagelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"c7df09c84a8ced8104b7105f9c8834deb7498cb1\";s:16:\"rootJobTimestamp\";s:14:\"20220204131249\";s:11:\"causeAction\";s:10:\"page-touch\";s:9:\"namespace\";i:102;s:5:\"title\";s:17:\"Owl:differentFrom\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";s:10:\"causeAgent\";s:7:\"unknown\";}',118502209,0,'',NULL,'pmqd5rvc1p503ar5sanoqktlmjlbe2t'),(28,'htmlCacheUpdate',102,'Owl:differentFrom','20220204131249','a:10:{s:5:\"table\";s:13:\"templatelinks\";s:9:\"recursive\";b:1;s:13:\"rootJobIsSelf\";b:1;s:16:\"rootJobSignature\";s:40:\"a8a9cca80e8d5487be5e09c4696ed8a8c98e1093\";s:16:\"rootJobTimestamp\";s:14:\"20220204131249\";s:11:\"causeAction\";s:11:\"page-create\";s:9:\"namespace\";i:102;s:5:\"title\";s:17:\"Owl:differentFrom\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";s:10:\"causeAgent\";s:7:\"unknown\";}',817992513,0,'',NULL,'7hvcbwevq4almg1z617335gze608ano'),(29,'smw.changePropagationDispatch',102,'Owl:differentFrom','20220204131249','a:4:{s:17:\"isTypePropagation\";b:1;s:9:\"namespace\";i:102;s:5:\"title\";s:17:\"Owl:differentFrom\";s:9:\"requestId\";s:24:\"b968129ee702ec08e261525c\";}',1983613771,0,'',NULL,'apzys243ydihz9qtkq0i1a0e7m89gen'),(30,'refreshLinksPrioritized',102,'Owl:differentFrom','20220204131249','a:8:{s:9:\"namespace\";i:102;s:5:\"title\";s:17:\"Owl:differentFrom\";s:16:\"rootJobTimestamp\";s:14:\"20220204131249\";s:23:\"useRecursiveLinksUpdate\";b:1;s:14:\"triggeringUser\";a:2:{s:6:\"userId\";i:0;s:8:\"userName\";s:9:\"127.0.0.1\";}s:20:\"triggeringRevisionId\";i:10;s:11:\"causeAction\";s:9:\"edit-page\";s:10:\"causeAgent\";s:9:\"127.0.0.1\";}',1014418543,0,'',NULL,'lsqi9clmsmtdhi5u9i85li1v91awh3h');
/*!40000 ALTER TABLE `job` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `l10n_cache`
--

DROP TABLE IF EXISTS `l10n_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `l10n_cache` (
  `lc_lang` varbinary(35) NOT NULL,
  `lc_key` varbinary(255) NOT NULL,
  `lc_value` mediumblob NOT NULL,
  PRIMARY KEY (`lc_lang`,`lc_key`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `l10n_cache`
--

LOCK TABLES `l10n_cache` WRITE;
/*!40000 ALTER TABLE `l10n_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `l10n_cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `langlinks`
--

DROP TABLE IF EXISTS `langlinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `langlinks` (
  `ll_from` int(10) unsigned NOT NULL DEFAULT 0,
  `ll_lang` varbinary(35) NOT NULL DEFAULT '',
  `ll_title` varbinary(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ll_from`,`ll_lang`),
  KEY `ll_lang` (`ll_lang`,`ll_title`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `langlinks`
--

LOCK TABLES `langlinks` WRITE;
/*!40000 ALTER TABLE `langlinks` DISABLE KEYS */;
/*!40000 ALTER TABLE `langlinks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_search`
--

DROP TABLE IF EXISTS `log_search`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_search` (
  `ls_field` varbinary(32) NOT NULL,
  `ls_value` varbinary(255) NOT NULL,
  `ls_log_id` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`ls_field`,`ls_value`,`ls_log_id`),
  KEY `ls_log_id` (`ls_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_search`
--

LOCK TABLES `log_search` WRITE;
/*!40000 ALTER TABLE `log_search` DISABLE KEYS */;
INSERT INTO `log_search` VALUES ('associated_rev_id','1',1),('associated_rev_id','10',10),('associated_rev_id','2',2),('associated_rev_id','3',3),('associated_rev_id','4',4),('associated_rev_id','5',5),('associated_rev_id','6',6),('associated_rev_id','7',7),('associated_rev_id','8',8),('associated_rev_id','9',9);
/*!40000 ALTER TABLE `log_search` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logging`
--

DROP TABLE IF EXISTS `logging`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logging` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `log_type` varbinary(32) NOT NULL DEFAULT '',
  `log_action` varbinary(32) NOT NULL DEFAULT '',
  `log_timestamp` binary(14) NOT NULL DEFAULT '19700101000000',
  `log_actor` bigint(20) unsigned NOT NULL,
  `log_namespace` int(11) NOT NULL DEFAULT 0,
  `log_title` varbinary(255) NOT NULL DEFAULT '',
  `log_page` int(10) unsigned DEFAULT NULL,
  `log_comment_id` bigint(20) unsigned NOT NULL,
  `log_params` blob NOT NULL,
  `log_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`log_id`),
  KEY `type_time` (`log_type`,`log_timestamp`),
  KEY `actor_time` (`log_actor`,`log_timestamp`),
  KEY `page_time` (`log_namespace`,`log_title`,`log_timestamp`),
  KEY `times` (`log_timestamp`),
  KEY `log_actor_type_time` (`log_actor`,`log_type`,`log_timestamp`),
  KEY `log_page_id_time` (`log_page`,`log_timestamp`),
  KEY `log_type_action` (`log_type`,`log_action`,`log_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logging`
--

LOCK TABLES `logging` WRITE;
/*!40000 ALTER TABLE `logging` DISABLE KEYS */;
INSERT INTO `logging` VALUES (1,'create','create','20210916140536',2,0,'Hauptseite',1,1,'a:1:{s:17:\"associated_rev_id\";i:1;}',0),(2,'create','create','20220204131242',5,112,'Group:Schema_properties',2,2,'a:1:{s:17:\"associated_rev_id\";i:2;}',0),(3,'create','create','20220204131246',5,112,'Group:Predefined_properties',3,2,'a:1:{s:17:\"associated_rev_id\";i:3;}',0),(4,'create','create','20220204131246',5,8,'Smw_import_skos',4,3,'a:1:{s:17:\"associated_rev_id\";i:4;}',0),(5,'create','create','20220204131247',5,8,'Smw_import_foaf',5,3,'a:1:{s:17:\"associated_rev_id\";i:5;}',0),(6,'create','create','20220204131247',5,8,'Smw_import_owl',6,3,'a:1:{s:17:\"associated_rev_id\";i:6;}',0),(7,'create','create','20220204131247',5,102,'Foaf:knows',7,3,'a:1:{s:17:\"associated_rev_id\";i:7;}',0),(8,'create','create','20220204131248',5,102,'Foaf:name',8,3,'a:1:{s:17:\"associated_rev_id\";i:8;}',0),(9,'create','create','20220204131248',5,102,'Foaf:homepage',9,3,'a:1:{s:17:\"associated_rev_id\";i:9;}',0),(10,'create','create','20220204131249',5,102,'Owl:differentFrom',10,3,'a:1:{s:17:\"associated_rev_id\";i:10;}',0);
/*!40000 ALTER TABLE `logging` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `module_deps`
--

DROP TABLE IF EXISTS `module_deps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `module_deps` (
  `md_module` varbinary(255) NOT NULL,
  `md_skin` varbinary(32) NOT NULL,
  `md_deps` mediumblob NOT NULL,
  PRIMARY KEY (`md_module`,`md_skin`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `module_deps`
--

LOCK TABLES `module_deps` WRITE;
/*!40000 ALTER TABLE `module_deps` DISABLE KEYS */;
/*!40000 ALTER TABLE `module_deps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `objectcache`
--

DROP TABLE IF EXISTS `objectcache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `objectcache` (
  `keyname` varbinary(255) NOT NULL DEFAULT '',
  `value` mediumblob DEFAULT NULL,
  `exptime` datetime DEFAULT NULL,
  PRIMARY KEY (`keyname`),
  KEY `exptime` (`exptime`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `objectcache`
--

LOCK TABLES `objectcache` WRITE;
/*!40000 ALTER TABLE `objectcache` DISABLE KEYS */;
/*!40000 ALTER TABLE `objectcache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oldimage`
--

DROP TABLE IF EXISTS `oldimage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oldimage` (
  `oi_name` varbinary(255) NOT NULL DEFAULT '',
  `oi_archive_name` varbinary(255) NOT NULL DEFAULT '',
  `oi_size` int(10) unsigned NOT NULL DEFAULT 0,
  `oi_width` int(11) NOT NULL DEFAULT 0,
  `oi_height` int(11) NOT NULL DEFAULT 0,
  `oi_bits` int(11) NOT NULL DEFAULT 0,
  `oi_description_id` bigint(20) unsigned NOT NULL,
  `oi_actor` bigint(20) unsigned NOT NULL,
  `oi_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `oi_metadata` mediumblob NOT NULL,
  `oi_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE','3D') DEFAULT NULL,
  `oi_major_mime` enum('unknown','application','audio','image','text','video','message','model','multipart','chemical') NOT NULL DEFAULT 'unknown',
  `oi_minor_mime` varbinary(100) NOT NULL DEFAULT 'unknown',
  `oi_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `oi_sha1` varbinary(32) NOT NULL DEFAULT '',
  KEY `oi_actor_timestamp` (`oi_actor`,`oi_timestamp`),
  KEY `oi_name_timestamp` (`oi_name`,`oi_timestamp`),
  KEY `oi_name_archive_name` (`oi_name`,`oi_archive_name`(14)),
  KEY `oi_sha1` (`oi_sha1`(10))
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oldimage`
--

LOCK TABLES `oldimage` WRITE;
/*!40000 ALTER TABLE `oldimage` DISABLE KEYS */;
/*!40000 ALTER TABLE `oldimage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `page`
--

DROP TABLE IF EXISTS `page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `page` (
  `page_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_namespace` int(11) NOT NULL,
  `page_title` varbinary(255) NOT NULL,
  `page_restrictions` tinyblob DEFAULT NULL,
  `page_is_redirect` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `page_is_new` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `page_random` double unsigned NOT NULL,
  `page_touched` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `page_links_updated` varbinary(14) DEFAULT NULL,
  `page_latest` int(10) unsigned NOT NULL,
  `page_len` int(10) unsigned NOT NULL,
  `page_content_model` varbinary(32) DEFAULT NULL,
  `page_lang` varbinary(35) DEFAULT NULL,
  PRIMARY KEY (`page_id`),
  UNIQUE KEY `name_title` (`page_namespace`,`page_title`),
  KEY `page_random` (`page_random`),
  KEY `page_len` (`page_len`),
  KEY `page_redirect_namespace_len` (`page_is_redirect`,`page_namespace`,`page_len`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `page`
--

LOCK TABLES `page` WRITE;
/*!40000 ALTER TABLE `page` DISABLE KEYS */;
INSERT INTO `page` VALUES (1,0,'Hauptseite','',0,1,0.831237389014,'20210916140536',NULL,1,784,'wikitext',NULL),(2,112,'Group:Schema_properties','',0,1,0.52446510618,'20220204131246',NULL,2,589,'smw/schema',NULL),(3,112,'Group:Predefined_properties','',0,1,0.248179414765,'20220204131246',NULL,3,2155,'smw/schema',NULL),(4,8,'Smw_import_skos','',0,1,0.334043094005,'20220204131247','20220204131246',4,982,'wikitext',NULL),(5,8,'Smw_import_foaf','',0,1,0.248344602318,'20220204131247','20220204131247',5,298,'wikitext',NULL),(6,8,'Smw_import_owl','',0,1,0.38894922977,'20220204131247','20220204131247',6,1196,'wikitext',NULL),(7,102,'Foaf:knows','',0,1,0.289303898533,'20220204131248',NULL,7,227,'wikitext',NULL),(8,102,'Foaf:name','',0,1,0.301503554326,'20220204131248',NULL,8,154,'wikitext',NULL),(9,102,'Foaf:homepage','',0,1,0.809020070563,'20220204131249',NULL,9,198,'wikitext',NULL),(10,102,'Owl:differentFrom','',0,1,0.859056457348,'20220204131249',NULL,10,209,'wikitext',NULL);
/*!40000 ALTER TABLE `page` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `page_props`
--

DROP TABLE IF EXISTS `page_props`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `page_props` (
  `pp_page` int(11) NOT NULL,
  `pp_propname` varbinary(60) NOT NULL,
  `pp_value` blob NOT NULL,
  `pp_sortkey` float DEFAULT NULL,
  PRIMARY KEY (`pp_page`,`pp_propname`),
  UNIQUE KEY `pp_propname_page` (`pp_propname`,`pp_page`),
  UNIQUE KEY `pp_propname_sortkey_page` (`pp_propname`,`pp_sortkey`,`pp_page`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `page_props`
--

LOCK TABLES `page_props` WRITE;
/*!40000 ALTER TABLE `page_props` DISABLE KEYS */;
INSERT INTO `page_props` VALUES (4,'smw-semanticdata-status','1',1),(5,'smw-semanticdata-status','1',1),(6,'smw-semanticdata-status','1',1);
/*!40000 ALTER TABLE `page_props` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `page_restrictions`
--

DROP TABLE IF EXISTS `page_restrictions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `page_restrictions` (
  `pr_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pr_page` int(11) NOT NULL,
  `pr_type` varbinary(60) NOT NULL,
  `pr_level` varbinary(60) NOT NULL,
  `pr_cascade` tinyint(4) NOT NULL,
  `pr_user` int(10) unsigned DEFAULT NULL,
  `pr_expiry` varbinary(14) DEFAULT NULL,
  PRIMARY KEY (`pr_id`),
  UNIQUE KEY `pr_pagetype` (`pr_page`,`pr_type`),
  KEY `pr_typelevel` (`pr_type`,`pr_level`),
  KEY `pr_level` (`pr_level`),
  KEY `pr_cascade` (`pr_cascade`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `page_restrictions`
--

LOCK TABLES `page_restrictions` WRITE;
/*!40000 ALTER TABLE `page_restrictions` DISABLE KEYS */;
/*!40000 ALTER TABLE `page_restrictions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagelinks`
--

DROP TABLE IF EXISTS `pagelinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pagelinks` (
  `pl_from` int(10) unsigned NOT NULL DEFAULT 0,
  `pl_from_namespace` int(11) NOT NULL DEFAULT 0,
  `pl_namespace` int(11) NOT NULL DEFAULT 0,
  `pl_title` varbinary(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`pl_from`,`pl_namespace`,`pl_title`),
  KEY `pl_namespace` (`pl_namespace`,`pl_title`,`pl_from`),
  KEY `pl_backlinks_namespace` (`pl_from_namespace`,`pl_namespace`,`pl_title`,`pl_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagelinks`
--

LOCK TABLES `pagelinks` WRITE;
/*!40000 ALTER TABLE `pagelinks` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagelinks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protected_titles`
--

DROP TABLE IF EXISTS `protected_titles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `protected_titles` (
  `pt_namespace` int(11) NOT NULL,
  `pt_title` varbinary(255) NOT NULL,
  `pt_user` int(10) unsigned NOT NULL,
  `pt_reason_id` bigint(20) unsigned NOT NULL,
  `pt_timestamp` binary(14) NOT NULL,
  `pt_expiry` varbinary(14) NOT NULL DEFAULT '',
  `pt_create_perm` varbinary(60) NOT NULL,
  PRIMARY KEY (`pt_namespace`,`pt_title`),
  KEY `pt_timestamp` (`pt_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `protected_titles`
--

LOCK TABLES `protected_titles` WRITE;
/*!40000 ALTER TABLE `protected_titles` DISABLE KEYS */;
/*!40000 ALTER TABLE `protected_titles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `querycache`
--

DROP TABLE IF EXISTS `querycache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `querycache` (
  `qc_type` varbinary(32) NOT NULL,
  `qc_value` int(10) unsigned NOT NULL DEFAULT 0,
  `qc_namespace` int(11) NOT NULL DEFAULT 0,
  `qc_title` varbinary(255) NOT NULL DEFAULT '',
  KEY `qc_type` (`qc_type`,`qc_value`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `querycache`
--

LOCK TABLES `querycache` WRITE;
/*!40000 ALTER TABLE `querycache` DISABLE KEYS */;
/*!40000 ALTER TABLE `querycache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `querycache_info`
--

DROP TABLE IF EXISTS `querycache_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `querycache_info` (
  `qci_type` varbinary(32) NOT NULL DEFAULT '',
  `qci_timestamp` binary(14) NOT NULL DEFAULT '19700101000000',
  PRIMARY KEY (`qci_type`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `querycache_info`
--

LOCK TABLES `querycache_info` WRITE;
/*!40000 ALTER TABLE `querycache_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `querycache_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `querycachetwo`
--

DROP TABLE IF EXISTS `querycachetwo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `querycachetwo` (
  `qcc_type` varbinary(32) NOT NULL,
  `qcc_value` int(10) unsigned NOT NULL DEFAULT 0,
  `qcc_namespace` int(11) NOT NULL DEFAULT 0,
  `qcc_title` varbinary(255) NOT NULL DEFAULT '',
  `qcc_namespacetwo` int(11) NOT NULL DEFAULT 0,
  `qcc_titletwo` varbinary(255) NOT NULL DEFAULT '',
  KEY `qcc_type` (`qcc_type`,`qcc_value`),
  KEY `qcc_title` (`qcc_type`,`qcc_namespace`,`qcc_title`),
  KEY `qcc_titletwo` (`qcc_type`,`qcc_namespacetwo`,`qcc_titletwo`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `querycachetwo`
--

LOCK TABLES `querycachetwo` WRITE;
/*!40000 ALTER TABLE `querycachetwo` DISABLE KEYS */;
/*!40000 ALTER TABLE `querycachetwo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recentchanges`
--

DROP TABLE IF EXISTS `recentchanges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recentchanges` (
  `rc_id` int(11) NOT NULL AUTO_INCREMENT,
  `rc_timestamp` varbinary(14) NOT NULL DEFAULT '',
  `rc_actor` bigint(20) unsigned NOT NULL,
  `rc_namespace` int(11) NOT NULL DEFAULT 0,
  `rc_title` varbinary(255) NOT NULL DEFAULT '',
  `rc_comment_id` bigint(20) unsigned NOT NULL,
  `rc_minor` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rc_bot` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rc_new` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rc_cur_id` int(10) unsigned NOT NULL DEFAULT 0,
  `rc_this_oldid` int(10) unsigned NOT NULL DEFAULT 0,
  `rc_last_oldid` int(10) unsigned NOT NULL DEFAULT 0,
  `rc_type` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rc_source` varbinary(16) NOT NULL DEFAULT '',
  `rc_patrolled` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rc_ip` varbinary(40) NOT NULL DEFAULT '',
  `rc_old_len` int(11) DEFAULT NULL,
  `rc_new_len` int(11) DEFAULT NULL,
  `rc_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rc_logid` int(10) unsigned NOT NULL DEFAULT 0,
  `rc_log_type` varbinary(255) DEFAULT NULL,
  `rc_log_action` varbinary(255) DEFAULT NULL,
  `rc_params` blob DEFAULT NULL,
  PRIMARY KEY (`rc_id`),
  KEY `rc_timestamp` (`rc_timestamp`),
  KEY `rc_namespace_title_timestamp` (`rc_namespace`,`rc_title`,`rc_timestamp`),
  KEY `rc_cur_id` (`rc_cur_id`),
  KEY `new_name_timestamp` (`rc_new`,`rc_namespace`,`rc_timestamp`),
  KEY `rc_ip` (`rc_ip`),
  KEY `rc_ns_actor` (`rc_namespace`,`rc_actor`),
  KEY `rc_actor` (`rc_actor`,`rc_timestamp`),
  KEY `rc_name_type_patrolled_timestamp` (`rc_namespace`,`rc_type`,`rc_patrolled`,`rc_timestamp`),
  KEY `rc_this_oldid` (`rc_this_oldid`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recentchanges`
--

LOCK TABLES `recentchanges` WRITE;
/*!40000 ALTER TABLE `recentchanges` DISABLE KEYS */;
INSERT INTO `recentchanges` VALUES (1,'20220204131242',5,112,'Group:Schema_properties',2,0,1,1,2,2,0,1,'mw.new',0,'127.0.0.1',0,589,0,0,NULL,'',''),(2,'20220204131246',5,112,'Group:Predefined_properties',2,0,1,1,3,3,0,1,'mw.new',0,'127.0.0.1',0,2155,0,0,NULL,'',''),(3,'20220204131246',5,8,'Smw_import_skos',3,0,1,1,4,4,0,1,'mw.new',0,'127.0.0.1',0,982,0,0,NULL,'',''),(4,'20220204131247',5,8,'Smw_import_foaf',3,0,1,1,5,5,0,1,'mw.new',0,'127.0.0.1',0,298,0,0,NULL,'',''),(5,'20220204131247',5,8,'Smw_import_owl',3,0,1,1,6,6,0,1,'mw.new',0,'127.0.0.1',0,1196,0,0,NULL,'',''),(6,'20220204131247',5,102,'Foaf:knows',3,0,1,1,7,7,0,1,'mw.new',0,'127.0.0.1',0,227,0,0,NULL,'',''),(7,'20220204131248',5,102,'Foaf:name',3,0,1,1,8,8,0,1,'mw.new',0,'127.0.0.1',0,154,0,0,NULL,'',''),(8,'20220204131248',5,102,'Foaf:homepage',3,0,1,1,9,9,0,1,'mw.new',0,'127.0.0.1',0,198,0,0,NULL,'',''),(9,'20220204131249',5,102,'Owl:differentFrom',3,0,1,1,10,10,0,1,'mw.new',0,'127.0.0.1',0,209,0,0,NULL,'','');
/*!40000 ALTER TABLE `recentchanges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `redirect`
--

DROP TABLE IF EXISTS `redirect`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `redirect` (
  `rd_from` int(10) unsigned NOT NULL DEFAULT 0,
  `rd_namespace` int(11) NOT NULL DEFAULT 0,
  `rd_title` varbinary(255) NOT NULL DEFAULT '',
  `rd_interwiki` varbinary(32) DEFAULT NULL,
  `rd_fragment` varbinary(255) DEFAULT NULL,
  PRIMARY KEY (`rd_from`),
  KEY `rd_ns_title` (`rd_namespace`,`rd_title`,`rd_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `redirect`
--

LOCK TABLES `redirect` WRITE;
/*!40000 ALTER TABLE `redirect` DISABLE KEYS */;
/*!40000 ALTER TABLE `redirect` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `revision`
--

DROP TABLE IF EXISTS `revision`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `revision` (
  `rev_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rev_page` int(10) unsigned NOT NULL,
  `rev_comment_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `rev_actor` bigint(20) unsigned NOT NULL DEFAULT 0,
  `rev_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `rev_minor_edit` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rev_deleted` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rev_len` int(10) unsigned DEFAULT NULL,
  `rev_parent_id` int(10) unsigned DEFAULT NULL,
  `rev_sha1` varbinary(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`rev_id`),
  KEY `rev_page_id` (`rev_page`,`rev_id`),
  KEY `rev_timestamp` (`rev_timestamp`),
  KEY `page_timestamp` (`rev_page`,`rev_timestamp`),
  KEY `rev_actor_timestamp` (`rev_actor`,`rev_timestamp`,`rev_id`),
  KEY `rev_page_actor_timestamp` (`rev_page`,`rev_actor`,`rev_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=binary MAX_ROWS=10000000 AVG_ROW_LENGTH=1024;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revision`
--

LOCK TABLES `revision` WRITE;
/*!40000 ALTER TABLE `revision` DISABLE KEYS */;
INSERT INTO `revision` VALUES (1,1,0,0,'20210916140536',0,0,784,0,'ezw1y7tpy380c53gmx6sikr7wj415iu'),(2,2,0,0,'20220204131242',0,0,589,0,'tmz3l2uctisu9w7umutjhmul9gs7lzh'),(3,3,0,0,'20220204131246',0,0,2155,0,'fk6zda7duib0qaof4ucwc7o8cgcsd4l'),(4,4,0,0,'20220204131246',0,0,982,0,'7uej7l6j7zibffqxeqr83oypfu8tya3'),(5,5,0,0,'20220204131247',0,0,298,0,'54j1f1u0gxrlqu4877gsk6vv72gs354'),(6,6,0,0,'20220204131247',0,0,1196,0,'mjrj8ysg8aclt8sddeabn26vvadmsuy'),(7,7,0,0,'20220204131247',0,0,227,0,'r84n0cewys8sf532il1jpkj3virql2i'),(8,8,0,0,'20220204131248',0,0,154,0,'4ywl3swqwhp0fsjdfg6poaq2d6n77xf'),(9,9,0,0,'20220204131248',0,0,198,0,'0yfosgdiyxnbl3w8m6h2o3nd5ehtb6u'),(10,10,0,0,'20220204131249',0,0,209,0,'p482vcrjzqtstzps5n3lp9i5hzy2scs');
/*!40000 ALTER TABLE `revision` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `revision_actor_temp`
--

DROP TABLE IF EXISTS `revision_actor_temp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `revision_actor_temp` (
  `revactor_rev` int(10) unsigned NOT NULL,
  `revactor_actor` bigint(20) unsigned NOT NULL,
  `revactor_timestamp` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `revactor_page` int(10) unsigned NOT NULL,
  PRIMARY KEY (`revactor_rev`,`revactor_actor`),
  UNIQUE KEY `revactor_rev` (`revactor_rev`),
  KEY `actor_timestamp` (`revactor_actor`,`revactor_timestamp`),
  KEY `page_actor_timestamp` (`revactor_page`,`revactor_actor`,`revactor_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revision_actor_temp`
--

LOCK TABLES `revision_actor_temp` WRITE;
/*!40000 ALTER TABLE `revision_actor_temp` DISABLE KEYS */;
INSERT INTO `revision_actor_temp` VALUES (1,2,'20210916140536',1),(2,5,'20220204131242',2),(3,5,'20220204131246',3),(4,5,'20220204131246',4),(5,5,'20220204131247',5),(6,5,'20220204131247',6),(7,5,'20220204131247',7),(8,5,'20220204131248',8),(9,5,'20220204131248',9),(10,5,'20220204131249',10);
/*!40000 ALTER TABLE `revision_actor_temp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `revision_comment_temp`
--

DROP TABLE IF EXISTS `revision_comment_temp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `revision_comment_temp` (
  `revcomment_rev` int(10) unsigned NOT NULL,
  `revcomment_comment_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`revcomment_rev`,`revcomment_comment_id`),
  UNIQUE KEY `revcomment_rev` (`revcomment_rev`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revision_comment_temp`
--

LOCK TABLES `revision_comment_temp` WRITE;
/*!40000 ALTER TABLE `revision_comment_temp` DISABLE KEYS */;
INSERT INTO `revision_comment_temp` VALUES (1,1),(2,2),(3,2),(4,3),(5,3),(6,3),(7,3),(8,3),(9,3),(10,3);
/*!40000 ALTER TABLE `revision_comment_temp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `searchindex`
--

DROP TABLE IF EXISTS `searchindex`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `searchindex` (
  `si_page` int(10) unsigned NOT NULL,
  `si_title` varchar(255) NOT NULL DEFAULT '',
  `si_text` mediumtext NOT NULL,
  UNIQUE KEY `si_page` (`si_page`),
  FULLTEXT KEY `si_title` (`si_title`),
  FULLTEXT KEY `si_text` (`si_text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `searchindex`
--

LOCK TABLES `searchindex` WRITE;
/*!40000 ALTER TABLE `searchindex` DISABLE KEYS */;
INSERT INTO `searchindex` VALUES (2,'group schema properties',' \"type\" \"property_group_schema\" \"groups\" \"schema_group\" \"canonical_name\" \"schema properties\" \"message_key\" \"smwu800-property-group-label-schema-group\" \"property_keys\" \"_schema_type\" \"_schema_def\" \"_schema_desc\" \"_schema_tag\" \"_schema_link\" \"_format_schema\" \"_constraint_schema\" \"_profile_schema\" \"tags\" \"group\" \"property group\" '),(3,'group predefined properties',' \"type\" \"property_group_schema\" \"groups\" \"administrative_group\" \"canonical_name\" \"adminstrative properties\" \"message_key\" \"smwu800-property-group-label-administrative-properties\" \"property_keys\" \"_mdat\" \"_cdat\" \"_newp\" \"_ledt\" \"_dtitle\" \"_chgpro\" \"_edip\" \"_errc\" \"classification_group\" \"canonical_name\" \"classification properties\" \"message_key\" \"smwu800-property-group-label-classification-properties\" \"property_keys\" \"_inst\" \"_ppgr\" \"_subp\" \"_subc\" \"content_group\" \"canonical_name\" \"content properties\" \"message_key\" \"smwu800-property-group-label-content-properties\" \"property_keys\" \"_sobj\" \"_ask\" \"_media\" \"_mime\" \"_attch_link\" \"_file_attch\" \"_cont_type\" \"_cont_author\" \"_cont_len\" \"_cont_lang\" \"_cont_title\" \"_cont_date\" \"_cont_keyw\" \"_trans\" \"_trans_source\" \"_trans_group\" \"declarative_group\" \"canonical_name\" \"declarative properties\" \"message_key\" \"smwu800-property-group-label-declarative-properties\" \"property_keys\" \"_type\" \"_unit\" \"_impo\" \"_conv\" \"_serv\" \"_pval\" \"_list\" \"_prec\" \"_pdesc\" \"_pplb\" \"_pvap\" \"_pvali\" \"_pvuc\" \"_peid\" \"_pefu\" \"tags\" \"group\" \"property group\" '),(4,'smwu800 import skos',' simple knowledge organization system skos altlabel type monolingual text broader type annotation uriu800 broadertransitive type annotation uriu800 broadmatch type annotation uriu800 changenote type text closematch type annotation uriu800 collection class concept class conceptscheme class definition type text editorialnote type text exactmatch type annotation uriu800 example type text hastopconcept type page hiddenlabel type string historynote type text inscheme type page mappingrelation type page member type page memberlist type page narrower type annotation uriu800 narrowertransitive type annotation uriu800 narrowmatch type annotation uriu800 notation type text note type text orderedcollection class preflabel type string related type annotation uriu800 relatedmatch type annotation uriu800 scopenote type text semanticrelation type page topconceptof type page category imported vocabulary '),(5,'smwu800 import foaf',' friend ofu800 au800 friend name type text homepage type urlu800 mbox type email mbox_sha1sum type text depiction type urlu800 phone type text person category organization category knows type page member type page category imported vocabulary '),(6,'smwu800 import owlu800',' webu800 ontology language owlu800 alldifferent category allvaluesfrom type page annotationproperty category backwardcompatiblewith type page cardinality type number class category comment type page complementof type page datarange category datatypeproperty category deprecatedclass category deprecatedproperty category differentfrom type page disjointwith type page distinctmembers type page equivalentclass type page equivalentproperty type page functionalproperty category hasvalue type page imports type page incompatiblewith type page intersectionof type page inversefunctionalproperty category inverseof type page isdefinedby type page label type page maxcardinality type number mincardinality type number nothing category objectproperty category oneof type page onproperty type page ontology category ontologyproperty category owlu800 type page priorversion type page restriction category sameas type page seealso type page somevaluesfrom type page symmetricproperty category thing category transitiveproperty category unionof type page versioninfo type page category imported vocabulary '),(7,'foaf knows',' * imported from foaf knows * property description au800 person known byu800 this person indicating some level ofu800 reciprocated interaction between theu800 parties . enu800 category imported vocabulary displaytitle foaf knows '),(8,'foaf name',' * imported from foaf name * property description au800 name foru800 some thing oru800 agent. enu800 category imported vocabulary displaytitle foaf name '),(9,'foaf homepage',' * imported from foaf homepage * property description urlu800 ofu800 theu800 homepage ofu800 something which isu800 au800 general webu800 resource. enu800 category imported vocabulary displaytitle foaf homepage '),(10,'owlu800 differentfrom',' * imported from owlu800 differentfrom * property description theu800 property that determines that twou800 given individuals areu800 different. enu800 category imported vocabulary displaytitle owlu800 differentfrom ');
/*!40000 ALTER TABLE `searchindex` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_identifiers`
--

DROP TABLE IF EXISTS `site_identifiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_identifiers` (
  `si_type` varbinary(32) NOT NULL,
  `si_key` varbinary(32) NOT NULL,
  `si_site` int(10) unsigned NOT NULL,
  PRIMARY KEY (`si_type`,`si_key`),
  KEY `site_ids_site` (`si_site`),
  KEY `site_ids_key` (`si_key`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_identifiers`
--

LOCK TABLES `site_identifiers` WRITE;
/*!40000 ALTER TABLE `site_identifiers` DISABLE KEYS */;
/*!40000 ALTER TABLE `site_identifiers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_stats`
--

DROP TABLE IF EXISTS `site_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_stats` (
  `ss_row_id` int(10) unsigned NOT NULL,
  `ss_total_edits` bigint(20) unsigned DEFAULT NULL,
  `ss_good_articles` bigint(20) unsigned DEFAULT NULL,
  `ss_total_pages` bigint(20) unsigned DEFAULT NULL,
  `ss_users` bigint(20) unsigned DEFAULT NULL,
  `ss_active_users` bigint(20) unsigned DEFAULT NULL,
  `ss_images` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`ss_row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_stats`
--

LOCK TABLES `site_stats` WRITE;
/*!40000 ALTER TABLE `site_stats` DISABLE KEYS */;
INSERT INTO `site_stats` VALUES (1,9,0,9,1,0,0);
/*!40000 ALTER TABLE `site_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sites`
--

DROP TABLE IF EXISTS `sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sites` (
  `site_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `site_global_key` varbinary(64) NOT NULL,
  `site_type` varbinary(32) NOT NULL,
  `site_group` varbinary(32) NOT NULL,
  `site_source` varbinary(32) NOT NULL,
  `site_language` varbinary(35) NOT NULL,
  `site_protocol` varbinary(32) NOT NULL,
  `site_domain` varbinary(255) NOT NULL,
  `site_data` blob NOT NULL,
  `site_forward` tinyint(1) NOT NULL,
  `site_config` blob NOT NULL,
  PRIMARY KEY (`site_id`),
  UNIQUE KEY `sites_global_key` (`site_global_key`),
  KEY `sites_type` (`site_type`),
  KEY `sites_group` (`site_group`),
  KEY `sites_source` (`site_source`),
  KEY `sites_language` (`site_language`),
  KEY `sites_protocol` (`site_protocol`),
  KEY `sites_domain` (`site_domain`),
  KEY `sites_forward` (`site_forward`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sites`
--

LOCK TABLES `sites` WRITE;
/*!40000 ALTER TABLE `sites` DISABLE KEYS */;
/*!40000 ALTER TABLE `sites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `slot_roles`
--

DROP TABLE IF EXISTS `slot_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `slot_roles` (
  `role_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `role_name` varbinary(64) NOT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `slot_roles`
--

LOCK TABLES `slot_roles` WRITE;
/*!40000 ALTER TABLE `slot_roles` DISABLE KEYS */;
INSERT INTO `slot_roles` VALUES (1,'main');
/*!40000 ALTER TABLE `slot_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `slots`
--

DROP TABLE IF EXISTS `slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `slots` (
  `slot_revision_id` bigint(20) unsigned NOT NULL,
  `slot_role_id` smallint(5) unsigned NOT NULL,
  `slot_content_id` bigint(20) unsigned NOT NULL,
  `slot_origin` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`slot_revision_id`,`slot_role_id`),
  KEY `slot_revision_origin_role` (`slot_revision_id`,`slot_origin`,`slot_role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `slots`
--

LOCK TABLES `slots` WRITE;
/*!40000 ALTER TABLE `slots` DISABLE KEYS */;
INSERT INTO `slots` VALUES (1,1,1,1),(2,1,2,2),(3,1,3,3),(4,1,4,4),(5,1,5,5),(6,1,6,6),(7,1,7,7),(8,1,8,8),(9,1,9,9),(10,1,10,10);
/*!40000 ALTER TABLE `slots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_concept_cache`
--

DROP TABLE IF EXISTS `smw_concept_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_concept_cache` (
  `s_id` int(11) unsigned NOT NULL,
  `o_id` int(11) unsigned NOT NULL,
  KEY `o_id` (`o_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_concept_cache`
--

LOCK TABLES `smw_concept_cache` WRITE;
/*!40000 ALTER TABLE `smw_concept_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_concept_cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_di_blob`
--

DROP TABLE IF EXISTS `smw_di_blob`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_di_blob` (
  `s_id` int(11) unsigned NOT NULL,
  `p_id` int(11) unsigned NOT NULL,
  `o_blob` mediumblob DEFAULT NULL,
  `o_hash` varbinary(255) DEFAULT NULL,
  KEY `s_id` (`s_id`,`p_id`),
  KEY `o_hash` (`o_hash`),
  KEY `s_id_2` (`s_id`,`o_hash`),
  KEY `p_id` (`p_id`,`o_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_di_blob`
--

LOCK TABLES `smw_di_blob` WRITE;
/*!40000 ALTER TABLE `smw_di_blob` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_di_blob` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_di_bool`
--

DROP TABLE IF EXISTS `smw_di_bool`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_di_bool` (
  `s_id` int(11) unsigned NOT NULL,
  `p_id` int(11) unsigned NOT NULL,
  `o_value` tinyint(1) DEFAULT NULL,
  KEY `s_id` (`s_id`,`p_id`),
  KEY `o_value` (`o_value`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_di_bool`
--

LOCK TABLES `smw_di_bool` WRITE;
/*!40000 ALTER TABLE `smw_di_bool` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_di_bool` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_di_coords`
--

DROP TABLE IF EXISTS `smw_di_coords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_di_coords` (
  `s_id` int(11) unsigned NOT NULL,
  `p_id` int(11) unsigned NOT NULL,
  `o_serialized` varbinary(255) DEFAULT NULL,
  `o_lat` double DEFAULT NULL,
  `o_lon` double DEFAULT NULL,
  KEY `s_id` (`s_id`,`p_id`),
  KEY `o_serialized` (`o_serialized`),
  KEY `p_id` (`p_id`,`o_serialized`),
  KEY `o_lat` (`o_lat`,`o_lon`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_di_coords`
--

LOCK TABLES `smw_di_coords` WRITE;
/*!40000 ALTER TABLE `smw_di_coords` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_di_coords` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_di_number`
--

DROP TABLE IF EXISTS `smw_di_number`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_di_number` (
  `s_id` int(11) unsigned NOT NULL,
  `p_id` int(11) unsigned NOT NULL,
  `o_serialized` varbinary(255) DEFAULT NULL,
  `o_sortkey` double DEFAULT NULL,
  KEY `s_id` (`s_id`,`p_id`),
  KEY `o_sortkey` (`o_sortkey`),
  KEY `p_id` (`p_id`,`o_serialized`),
  KEY `p_id_2` (`p_id`,`o_sortkey`),
  KEY `s_id_2` (`s_id`,`p_id`,`o_sortkey`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_di_number`
--

LOCK TABLES `smw_di_number` WRITE;
/*!40000 ALTER TABLE `smw_di_number` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_di_number` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_di_time`
--

DROP TABLE IF EXISTS `smw_di_time`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_di_time` (
  `s_id` int(11) unsigned NOT NULL,
  `p_id` int(11) unsigned NOT NULL,
  `o_serialized` varbinary(255) DEFAULT NULL,
  `o_sortkey` double DEFAULT NULL,
  KEY `s_id` (`s_id`,`p_id`),
  KEY `o_sortkey` (`o_sortkey`),
  KEY `p_id` (`p_id`,`o_serialized`),
  KEY `p_id_2` (`p_id`,`o_sortkey`),
  KEY `s_id_2` (`s_id`,`p_id`,`o_sortkey`,`o_serialized`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_di_time`
--

LOCK TABLES `smw_di_time` WRITE;
/*!40000 ALTER TABLE `smw_di_time` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_di_time` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_di_uri`
--

DROP TABLE IF EXISTS `smw_di_uri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_di_uri` (
  `s_id` int(11) unsigned NOT NULL,
  `p_id` int(11) unsigned NOT NULL,
  `o_blob` mediumblob DEFAULT NULL,
  `o_serialized` varbinary(255) DEFAULT NULL,
  KEY `s_id` (`s_id`,`p_id`),
  KEY `o_serialized` (`o_serialized`),
  KEY `p_id` (`p_id`,`o_serialized`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_di_uri`
--

LOCK TABLES `smw_di_uri` WRITE;
/*!40000 ALTER TABLE `smw_di_uri` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_di_uri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_di_wikipage`
--

DROP TABLE IF EXISTS `smw_di_wikipage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_di_wikipage` (
  `s_id` int(11) unsigned NOT NULL,
  `p_id` int(11) unsigned NOT NULL,
  `o_id` int(11) unsigned DEFAULT NULL,
  KEY `s_id` (`s_id`,`p_id`),
  KEY `o_id` (`o_id`),
  KEY `p_id` (`p_id`,`s_id`),
  KEY `s_id_2` (`s_id`,`o_id`),
  KEY `s_id_3` (`s_id`,`p_id`,`o_id`),
  KEY `o_id_2` (`o_id`,`s_id`),
  KEY `o_id_3` (`o_id`,`p_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_di_wikipage`
--

LOCK TABLES `smw_di_wikipage` WRITE;
/*!40000 ALTER TABLE `smw_di_wikipage` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_di_wikipage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_ask`
--

DROP TABLE IF EXISTS `smw_fpt_ask`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_ask` (
  `s_id` int(11) unsigned NOT NULL,
  `o_id` int(11) unsigned DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_id` (`o_id`),
  KEY `s_id_2` (`s_id`,`o_id`),
  KEY `o_id_2` (`o_id`,`s_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_ask`
--

LOCK TABLES `smw_fpt_ask` WRITE;
/*!40000 ALTER TABLE `smw_fpt_ask` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_ask` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_askde`
--

DROP TABLE IF EXISTS `smw_fpt_askde`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_askde` (
  `s_id` int(11) unsigned NOT NULL,
  `o_serialized` varbinary(255) DEFAULT NULL,
  `o_sortkey` double DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_sortkey` (`o_sortkey`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_askde`
--

LOCK TABLES `smw_fpt_askde` WRITE;
/*!40000 ALTER TABLE `smw_fpt_askde` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_askde` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_askdu`
--

DROP TABLE IF EXISTS `smw_fpt_askdu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_askdu` (
  `s_id` int(11) unsigned NOT NULL,
  `o_serialized` varbinary(255) DEFAULT NULL,
  `o_sortkey` double DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_sortkey` (`o_sortkey`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_askdu`
--

LOCK TABLES `smw_fpt_askdu` WRITE;
/*!40000 ALTER TABLE `smw_fpt_askdu` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_askdu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_askfo`
--

DROP TABLE IF EXISTS `smw_fpt_askfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_askfo` (
  `s_id` int(11) unsigned NOT NULL,
  `o_blob` mediumblob DEFAULT NULL,
  `o_hash` varbinary(255) DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_hash` (`o_hash`),
  KEY `s_id_2` (`s_id`,`o_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_askfo`
--

LOCK TABLES `smw_fpt_askfo` WRITE;
/*!40000 ALTER TABLE `smw_fpt_askfo` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_askfo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_askpa`
--

DROP TABLE IF EXISTS `smw_fpt_askpa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_askpa` (
  `s_id` int(11) unsigned NOT NULL,
  `o_blob` mediumblob DEFAULT NULL,
  `o_hash` varbinary(255) DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_hash` (`o_hash`),
  KEY `s_id_2` (`s_id`,`o_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_askpa`
--

LOCK TABLES `smw_fpt_askpa` WRITE;
/*!40000 ALTER TABLE `smw_fpt_askpa` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_askpa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_asksi`
--

DROP TABLE IF EXISTS `smw_fpt_asksi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_asksi` (
  `s_id` int(11) unsigned NOT NULL,
  `o_serialized` varbinary(255) DEFAULT NULL,
  `o_sortkey` double DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_sortkey` (`o_sortkey`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_asksi`
--

LOCK TABLES `smw_fpt_asksi` WRITE;
/*!40000 ALTER TABLE `smw_fpt_asksi` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_asksi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_askst`
--

DROP TABLE IF EXISTS `smw_fpt_askst`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_askst` (
  `s_id` int(11) unsigned NOT NULL,
  `o_blob` mediumblob DEFAULT NULL,
  `o_hash` varbinary(255) DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_hash` (`o_hash`),
  KEY `s_id_2` (`s_id`,`o_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_askst`
--

LOCK TABLES `smw_fpt_askst` WRITE;
/*!40000 ALTER TABLE `smw_fpt_askst` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_askst` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_conc`
--

DROP TABLE IF EXISTS `smw_fpt_conc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_conc` (
  `s_id` int(11) unsigned NOT NULL,
  `concept_txt` mediumblob DEFAULT NULL,
  `concept_docu` mediumblob DEFAULT NULL,
  `concept_features` int(11) DEFAULT NULL,
  `concept_size` int(11) DEFAULT NULL,
  `concept_depth` int(11) DEFAULT NULL,
  `cache_date` int(8) unsigned DEFAULT NULL,
  `cache_count` int(8) unsigned DEFAULT NULL,
  KEY `s_id` (`s_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_conc`
--

LOCK TABLES `smw_fpt_conc` WRITE;
/*!40000 ALTER TABLE `smw_fpt_conc` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_conc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_conv`
--

DROP TABLE IF EXISTS `smw_fpt_conv`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_conv` (
  `s_id` int(11) unsigned NOT NULL,
  `o_blob` mediumblob DEFAULT NULL,
  `o_hash` varbinary(255) DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_hash` (`o_hash`),
  KEY `s_id_2` (`s_id`,`o_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_conv`
--

LOCK TABLES `smw_fpt_conv` WRITE;
/*!40000 ALTER TABLE `smw_fpt_conv` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_conv` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_dtitle`
--

DROP TABLE IF EXISTS `smw_fpt_dtitle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_dtitle` (
  `s_id` int(11) unsigned NOT NULL,
  `o_blob` mediumblob DEFAULT NULL,
  `o_hash` varbinary(255) DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_hash` (`o_hash`),
  KEY `s_id_2` (`s_id`,`o_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_dtitle`
--

LOCK TABLES `smw_fpt_dtitle` WRITE;
/*!40000 ALTER TABLE `smw_fpt_dtitle` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_dtitle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_impo`
--

DROP TABLE IF EXISTS `smw_fpt_impo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_impo` (
  `s_id` int(11) unsigned NOT NULL,
  `o_blob` mediumblob DEFAULT NULL,
  `o_hash` varbinary(255) DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_hash` (`o_hash`),
  KEY `s_id_2` (`s_id`,`o_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_impo`
--

LOCK TABLES `smw_fpt_impo` WRITE;
/*!40000 ALTER TABLE `smw_fpt_impo` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_impo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_inst`
--

DROP TABLE IF EXISTS `smw_fpt_inst`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_inst` (
  `s_id` int(11) unsigned NOT NULL,
  `o_id` int(11) unsigned DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_id` (`o_id`),
  KEY `s_id_2` (`s_id`,`o_id`),
  KEY `o_id_2` (`o_id`,`s_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_inst`
--

LOCK TABLES `smw_fpt_inst` WRITE;
/*!40000 ALTER TABLE `smw_fpt_inst` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_inst` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_lcode`
--

DROP TABLE IF EXISTS `smw_fpt_lcode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_lcode` (
  `s_id` int(11) unsigned NOT NULL,
  `o_blob` mediumblob DEFAULT NULL,
  `o_hash` varbinary(255) DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_hash` (`o_hash`),
  KEY `s_id_2` (`s_id`,`o_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_lcode`
--

LOCK TABLES `smw_fpt_lcode` WRITE;
/*!40000 ALTER TABLE `smw_fpt_lcode` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_lcode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_list`
--

DROP TABLE IF EXISTS `smw_fpt_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_list` (
  `s_id` int(11) unsigned NOT NULL,
  `o_blob` mediumblob DEFAULT NULL,
  `o_hash` varbinary(255) DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_hash` (`o_hash`),
  KEY `s_id_2` (`s_id`,`o_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_list`
--

LOCK TABLES `smw_fpt_list` WRITE;
/*!40000 ALTER TABLE `smw_fpt_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_mdat`
--

DROP TABLE IF EXISTS `smw_fpt_mdat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_mdat` (
  `s_id` int(11) unsigned NOT NULL,
  `o_serialized` varbinary(255) DEFAULT NULL,
  `o_sortkey` double DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_sortkey` (`o_sortkey`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_mdat`
--

LOCK TABLES `smw_fpt_mdat` WRITE;
/*!40000 ALTER TABLE `smw_fpt_mdat` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_mdat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_pplb`
--

DROP TABLE IF EXISTS `smw_fpt_pplb`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_pplb` (
  `s_id` int(11) unsigned NOT NULL,
  `o_id` int(11) unsigned DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_id` (`o_id`),
  KEY `s_id_2` (`s_id`,`o_id`),
  KEY `o_id_2` (`o_id`,`s_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_pplb`
--

LOCK TABLES `smw_fpt_pplb` WRITE;
/*!40000 ALTER TABLE `smw_fpt_pplb` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_pplb` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_prec`
--

DROP TABLE IF EXISTS `smw_fpt_prec`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_prec` (
  `s_id` int(11) unsigned NOT NULL,
  `o_serialized` varbinary(255) DEFAULT NULL,
  `o_sortkey` double DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_sortkey` (`o_sortkey`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_prec`
--

LOCK TABLES `smw_fpt_prec` WRITE;
/*!40000 ALTER TABLE `smw_fpt_prec` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_prec` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_pval`
--

DROP TABLE IF EXISTS `smw_fpt_pval`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_pval` (
  `s_id` int(11) unsigned NOT NULL,
  `o_blob` mediumblob DEFAULT NULL,
  `o_hash` varbinary(255) DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_hash` (`o_hash`),
  KEY `s_id_2` (`s_id`,`o_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_pval`
--

LOCK TABLES `smw_fpt_pval` WRITE;
/*!40000 ALTER TABLE `smw_fpt_pval` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_pval` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_redi`
--

DROP TABLE IF EXISTS `smw_fpt_redi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_redi` (
  `s_title` varbinary(255) NOT NULL,
  `s_namespace` int(11) NOT NULL,
  `o_id` int(11) unsigned DEFAULT NULL,
  KEY `s_title` (`s_title`,`s_namespace`,`o_id`),
  KEY `o_id` (`o_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_redi`
--

LOCK TABLES `smw_fpt_redi` WRITE;
/*!40000 ALTER TABLE `smw_fpt_redi` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_redi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_serv`
--

DROP TABLE IF EXISTS `smw_fpt_serv`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_serv` (
  `s_id` int(11) unsigned NOT NULL,
  `o_blob` mediumblob DEFAULT NULL,
  `o_hash` varbinary(255) DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_hash` (`o_hash`),
  KEY `s_id_2` (`s_id`,`o_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_serv`
--

LOCK TABLES `smw_fpt_serv` WRITE;
/*!40000 ALTER TABLE `smw_fpt_serv` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_serv` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_sobj`
--

DROP TABLE IF EXISTS `smw_fpt_sobj`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_sobj` (
  `s_id` int(11) unsigned NOT NULL,
  `o_id` int(11) unsigned DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_id` (`o_id`),
  KEY `s_id_2` (`s_id`,`o_id`),
  KEY `o_id_2` (`o_id`,`s_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_sobj`
--

LOCK TABLES `smw_fpt_sobj` WRITE;
/*!40000 ALTER TABLE `smw_fpt_sobj` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_sobj` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_subc`
--

DROP TABLE IF EXISTS `smw_fpt_subc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_subc` (
  `s_id` int(11) unsigned NOT NULL,
  `o_id` int(11) unsigned DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_id` (`o_id`),
  KEY `s_id_2` (`s_id`,`o_id`),
  KEY `o_id_2` (`o_id`,`s_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_subc`
--

LOCK TABLES `smw_fpt_subc` WRITE;
/*!40000 ALTER TABLE `smw_fpt_subc` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_subc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_subp`
--

DROP TABLE IF EXISTS `smw_fpt_subp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_subp` (
  `s_id` int(11) unsigned NOT NULL,
  `o_id` int(11) unsigned DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_id` (`o_id`),
  KEY `s_id_2` (`s_id`,`o_id`),
  KEY `o_id_2` (`o_id`,`s_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_subp`
--

LOCK TABLES `smw_fpt_subp` WRITE;
/*!40000 ALTER TABLE `smw_fpt_subp` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_subp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_text`
--

DROP TABLE IF EXISTS `smw_fpt_text`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_text` (
  `s_id` int(11) unsigned NOT NULL,
  `o_blob` mediumblob DEFAULT NULL,
  `o_hash` varbinary(255) DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_hash` (`o_hash`),
  KEY `s_id_2` (`s_id`,`o_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_text`
--

LOCK TABLES `smw_fpt_text` WRITE;
/*!40000 ALTER TABLE `smw_fpt_text` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_text` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_type`
--

DROP TABLE IF EXISTS `smw_fpt_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_type` (
  `s_id` int(11) unsigned NOT NULL,
  `o_blob` mediumblob DEFAULT NULL,
  `o_serialized` varbinary(255) DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_serialized` (`o_serialized`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_type`
--

LOCK TABLES `smw_fpt_type` WRITE;
/*!40000 ALTER TABLE `smw_fpt_type` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_unit`
--

DROP TABLE IF EXISTS `smw_fpt_unit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_unit` (
  `s_id` int(11) unsigned NOT NULL,
  `o_blob` mediumblob DEFAULT NULL,
  `o_hash` varbinary(255) DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_hash` (`o_hash`),
  KEY `s_id_2` (`s_id`,`o_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_unit`
--

LOCK TABLES `smw_fpt_unit` WRITE;
/*!40000 ALTER TABLE `smw_fpt_unit` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_unit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_fpt_uri`
--

DROP TABLE IF EXISTS `smw_fpt_uri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_fpt_uri` (
  `s_id` int(11) unsigned NOT NULL,
  `o_blob` mediumblob DEFAULT NULL,
  `o_serialized` varbinary(255) DEFAULT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_serialized` (`o_serialized`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_fpt_uri`
--

LOCK TABLES `smw_fpt_uri` WRITE;
/*!40000 ALTER TABLE `smw_fpt_uri` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_fpt_uri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_object_aux`
--

DROP TABLE IF EXISTS `smw_object_aux`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_object_aux` (
  `smw_id` int(11) unsigned NOT NULL,
  `smw_seqmap` mediumblob DEFAULT NULL,
  `smw_countmap` mediumblob DEFAULT NULL,
  PRIMARY KEY (`smw_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_object_aux`
--

LOCK TABLES `smw_object_aux` WRITE;
/*!40000 ALTER TABLE `smw_object_aux` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_object_aux` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_object_ids`
--

DROP TABLE IF EXISTS `smw_object_ids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_object_ids` (
  `smw_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `smw_namespace` int(11) NOT NULL,
  `smw_title` varbinary(255) NOT NULL,
  `smw_iw` varbinary(32) NOT NULL,
  `smw_subobject` varbinary(255) NOT NULL,
  `smw_sortkey` varbinary(255) NOT NULL,
  `smw_sort` varbinary(255) DEFAULT NULL,
  `smw_proptable_hash` mediumblob DEFAULT NULL,
  `smw_hash` varbinary(40) DEFAULT NULL,
  `smw_rev` int(11) unsigned DEFAULT NULL,
  `smw_touched` binary(14) DEFAULT NULL,
  PRIMARY KEY (`smw_id`),
  KEY `smw_id` (`smw_id`,`smw_sortkey`),
  KEY `smw_hash` (`smw_hash`,`smw_id`),
  KEY `smw_iw` (`smw_iw`),
  KEY `smw_iw_2` (`smw_iw`,`smw_id`),
  KEY `smw_title` (`smw_title`,`smw_namespace`,`smw_iw`,`smw_subobject`),
  KEY `smw_sortkey` (`smw_sortkey`),
  KEY `smw_sort` (`smw_sort`,`smw_id`),
  KEY `smw_namespace` (`smw_namespace`,`smw_sortkey`),
  KEY `smw_rev` (`smw_rev`,`smw_id`),
  KEY `smw_touched` (`smw_touched`,`smw_id`)
) ENGINE=InnoDB AUTO_INCREMENT=505 DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_object_ids`
--

LOCK TABLES `smw_object_ids` WRITE;
/*!40000 ALTER TABLE `smw_object_ids` DISABLE KEYS */;
INSERT INTO `smw_object_ids` VALUES (1,102,'_TYPE','','','Has type','Has type',NULL,'048e091da0a2cf40c490256d750e2d3e2083d659',NULL,'19700101000000'),(2,102,'_URI','','','Equivalent URI','Equivalent URI',NULL,'a7b847775b86f3527469ca7f27c4f85f6911f60f',NULL,'19700101000000'),(4,102,'_INST',':smw-intprop','','','',NULL,'eac3684cbe0a08de88176820b2231f4a51de287f',NULL,'19700101000000'),(7,102,'_UNIT','','','Display units','Display units',NULL,'23fe069841052dcdb05c01e9a74590553b7e625c',NULL,'19700101000000'),(8,102,'_IMPO','','','Imported from','Imported from',NULL,'fbf88e8e884aa8e115b3abe22e60a633ddfb0090',NULL,'19700101000000'),(9,102,'_PPLB','','','Preferred property label','Preferred property label',NULL,'6a8c04b8b7a0450609456addeed69af6e155f84b',NULL,'19700101000000'),(10,102,'_PDESC','','','Property description','Property description',NULL,'d1f53cc00576b1f7d7e8949c5dea2a65bac86e90',NULL,'19700101000000'),(11,102,'_PREC','','','Display precision of','Display precision of',NULL,'dd7602ed69ee728ecd524279ffc64643e185d068',NULL,'19700101000000'),(12,102,'_CONV','','','Corresponds to','Corresponds to',NULL,'56dd99aca77adce01454ad90ea011188c2f81d9f',NULL,'19700101000000'),(13,102,'_SERV','','','Provides service','Provides service',NULL,'b86ec3f7523dcdd1d1af8f0450543cf35f66fe78',NULL,'19700101000000'),(14,102,'_PVAL','','','Allows value','Allows value',NULL,'23a025d7115652ba45531d3c4ad8fba03b459ff4',NULL,'19700101000000'),(15,102,'_REDI',':smw-intprop','','','',NULL,'0fc44b3804b06d08f6a9a0f8c5b84934c5b66d97',NULL,'19700101000000'),(16,102,'_DTITLE','','','Display title of','Display title of',NULL,'6ad919faca8c7a5093a708769bbe8e7dc068b45d',NULL,'19700101000000'),(17,102,'_SUBP','','','Subproperty of','Subproperty of',NULL,'5274be42c64c631887fb9743b1982bee79e90a51',NULL,'19700101000000'),(18,102,'_SUBC','','','Subcategory of','Subcategory of',NULL,'308a9b9e1ab6bc8663a8a3df2e74a2ec874e1132',NULL,'19700101000000'),(19,102,'_CONC',':smw-intprop','','','',NULL,'24e97ee06c0e98a9b20fb9d8f8bf4e869a1463f7',NULL,'19700101000000'),(22,102,'_ERRP','','','Has improper value for','Has improper value for',NULL,'00a182de71315e7560d87cfe31374ee846c9e4dd',NULL,'19700101000000'),(28,102,'_LIST','','','Has fields','Has fields',NULL,'1788336295b59e8a83c451a57c799ac0b9184257',NULL,'19700101000000'),(29,102,'_MDAT','','','Modification date','Modification date',NULL,'670186d9da6d1f44ac46cedf92668143eb9d8b1a',NULL,'19700101000000'),(30,102,'_CDAT','','','Creation date','Creation date',NULL,'42585aef8402e3bc09602b3cad5f984eb086fd91',NULL,'19700101000000'),(31,102,'_NEWP','','','Is a new page','Is a new page',NULL,'507ff5f6d6690950effa58ec456c6cc6b36c7086',NULL,'19700101000000'),(32,102,'_LEDT','','','Last editor is','Last editor is',NULL,'ce69fed63b0b07f1d807eb13be97c96ddedd8943',NULL,'19700101000000'),(33,102,'_ASK','','','Has query','Has query',NULL,'ea129a82625b44673217ce6d5bac4d1d7d4e9ce9',NULL,'19700101000000'),(34,102,'_ASKST','','','Query string','Query string',NULL,'2eb655b48b24a9d1bc72304a70a14a6eff003754',NULL,'19700101000000'),(35,102,'_ASKFO','','','Query format','Query format',NULL,'f7867e263e4aa82c0cc21fbe78d0178d638491c3',NULL,'19700101000000'),(36,102,'_ASKSI','','','Query size','Query size',NULL,'47c17afe4c02704222edb4bdb774f01dbf8148a1',NULL,'19700101000000'),(37,102,'_ASKDE','','','Query depth','Query depth',NULL,'51e88e5f50e7cfc5c8fcd3a3fc38e90c4021b3bc',NULL,'19700101000000'),(38,102,'_ASKPA','','','Query parameters','Query parameters',NULL,'cf0030894bc5b3a5593738edd4607e1193a053ae',NULL,'19700101000000'),(39,102,'_ASKSC','','','Query source','Query source',NULL,'b547b4601b1183b7e9ab343ea419eee83418a944',NULL,'19700101000000'),(40,102,'_LCODE','','','Language code','Language code',NULL,'fd726db3b3f93f836fda789d452c2ce274d555ec',NULL,'19700101000000'),(41,102,'_TEXT','','','Text','Text',NULL,'eacdc1e21f609e8bcb6e8508621c3de04a2bd0da',NULL,'19700101000000'),(60,102,'_ATTCH_LINK','','','Attachment link','Attachment link',NULL,'9aad948bdc3e7285989dec2d1c23ff416bc38095',NULL,'19700101000000'),(500,0,'',':smw-border','','',NULL,NULL,'a2ebb68592cf2e5baf923f386b431dfd7694317f',NULL,NULL);
/*!40000 ALTER TABLE `smw_object_ids` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_prop_stats`
--

DROP TABLE IF EXISTS `smw_prop_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_prop_stats` (
  `p_id` int(11) unsigned DEFAULT NULL,
  `usage_count` int(8) unsigned DEFAULT NULL,
  `null_count` int(8) unsigned DEFAULT NULL,
  UNIQUE KEY `p_id` (`p_id`),
  KEY `usage_count` (`usage_count`),
  KEY `null_count` (`null_count`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_prop_stats`
--

LOCK TABLES `smw_prop_stats` WRITE;
/*!40000 ALTER TABLE `smw_prop_stats` DISABLE KEYS */;
INSERT INTO `smw_prop_stats` VALUES (1,0,0),(2,0,0),(4,0,0),(7,0,0),(8,0,0),(12,0,0),(13,0,0),(14,0,0),(15,0,0),(17,0,0),(18,0,0),(19,0,0),(29,0,0),(30,0,0),(31,0,0),(32,0,0),(22,0,0),(28,0,0),(33,0,0),(34,0,0),(35,0,0),(36,0,0),(37,0,0),(39,0,0),(38,0,0),(11,0,0),(40,0,0),(41,0,0),(10,0,0),(16,0,0),(9,0,0),(60,0,0);
/*!40000 ALTER TABLE `smw_prop_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `smw_query_links`
--

DROP TABLE IF EXISTS `smw_query_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smw_query_links` (
  `s_id` int(11) unsigned NOT NULL,
  `o_id` int(11) unsigned NOT NULL,
  KEY `s_id` (`s_id`),
  KEY `o_id` (`o_id`),
  KEY `s_id_2` (`s_id`,`o_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `smw_query_links`
--

LOCK TABLES `smw_query_links` WRITE;
/*!40000 ALTER TABLE `smw_query_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `smw_query_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `templatelinks`
--

DROP TABLE IF EXISTS `templatelinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `templatelinks` (
  `tl_from` int(10) unsigned NOT NULL DEFAULT 0,
  `tl_from_namespace` int(11) NOT NULL DEFAULT 0,
  `tl_namespace` int(11) NOT NULL DEFAULT 0,
  `tl_title` varbinary(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`tl_from`,`tl_namespace`,`tl_title`),
  KEY `tl_namespace` (`tl_namespace`,`tl_title`,`tl_from`),
  KEY `tl_backlinks_namespace` (`tl_from_namespace`,`tl_namespace`,`tl_title`,`tl_from`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `templatelinks`
--

LOCK TABLES `templatelinks` WRITE;
/*!40000 ALTER TABLE `templatelinks` DISABLE KEYS */;
/*!40000 ALTER TABLE `templatelinks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `text`
--

DROP TABLE IF EXISTS `text`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `text` (
  `old_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `old_text` mediumblob NOT NULL,
  `old_flags` tinyblob NOT NULL,
  PRIMARY KEY (`old_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=binary MAX_ROWS=10000000 AVG_ROW_LENGTH=10240;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `text`
--

LOCK TABLES `text` WRITE;
/*!40000 ALTER TABLE `text` DISABLE KEYS */;
INSERT INTO `text` VALUES (1,'<strong>MediaWiki wurde installiert.</strong>\n\nHilfe zur Benutzung und Konfiguration der Wiki-Software findest du im [https://www.mediawiki.org/wiki/Special:MyLanguage/Help:Contents Benutzerhandbuch].\n\n== Starthilfen ==\n\n* [https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings Liste der Konfigurationsvariablen]\n* [https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ MediaWiki-FAQ]\n* [https://lists.wikimedia.org/mailman/listinfo/mediawiki-announce Mailingliste neuer MediaWiki-Versionen]\n* [https://www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources Übersetze MediaWiki für deine Sprache]\n* [https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Combating_spam Erfahre, wie du Spam auf deinem Wiki bekämpfen kannst]','utf-8'),(2,'{\n    \"type\": \"PROPERTY_GROUP_SCHEMA\",\n    \"groups\": {\n        \"schema_group\": {\n            \"canonical_name\": \"Schema properties\",\n            \"message_key\": \"smw-property-group-label-schema-group\",\n            \"property_keys\": [\n                \"_SCHEMA_TYPE\",\n                \"_SCHEMA_DEF\",\n                \"_SCHEMA_DESC\",\n                \"_SCHEMA_TAG\",\n                \"_SCHEMA_LINK\",\n                \"_FORMAT_SCHEMA\",\n                \"_CONSTRAINT_SCHEMA\",\n                \"_PROFILE_SCHEMA\"\n            ]\n        }\n    },\n    \"tags\": [\n        \"group\",\n        \"property group\"\n    ]\n}','utf-8'),(3,'{\n    \"type\": \"PROPERTY_GROUP_SCHEMA\",\n    \"groups\": {\n        \"administrative_group\": {\n            \"canonical_name\": \"Adminstrative properties\",\n            \"message_key\": \"smw-property-group-label-administrative-properties\",\n            \"property_keys\": [\n                \"_MDAT\",\n                \"_CDAT\",\n                \"_NEWP\",\n                \"_LEDT\",\n                \"_DTITLE\",\n                \"_CHGPRO\",\n                \"_EDIP\",\n                \"_ERRC\"\n            ]\n        },\n        \"classification_group\": {\n            \"canonical_name\": \"Classification properties\",\n            \"message_key\": \"smw-property-group-label-classification-properties\",\n            \"property_keys\": [\n                \"_INST\",\n                \"_PPGR\",\n                \"_SUBP\",\n                \"_SUBC\"\n            ]\n        },\n        \"content_group\": {\n            \"canonical_name\": \"Content properties\",\n            \"message_key\": \"smw-property-group-label-content-properties\",\n            \"property_keys\": [\n                \"_SOBJ\",\n                \"_ASK\",\n                \"_MEDIA\",\n                \"_MIME\",\n                \"_ATTCH_LINK\",\n                \"_FILE_ATTCH\",\n                \"_CONT_TYPE\",\n                \"_CONT_AUTHOR\",\n                \"_CONT_LEN\",\n                \"_CONT_LANG\",\n                \"_CONT_TITLE\",\n                \"_CONT_DATE\",\n                \"_CONT_KEYW\",\n                \"_TRANS\",\n                \"_TRANS_SOURCE\",\n                \"_TRANS_GROUP\"\n            ]\n        },\n        \"declarative_group\": {\n            \"canonical_name\": \"Declarative properties\",\n            \"message_key\": \"smw-property-group-label-declarative-properties\",\n            \"property_keys\": [\n                \"_TYPE\",\n                \"_UNIT\",\n                \"_IMPO\",\n                \"_CONV\",\n                \"_SERV\",\n                \"_PVAL\",\n                \"_LIST\",\n                \"_PREC\",\n                \"_PDESC\",\n                \"_PPLB\",\n                \"_PVAP\",\n                \"_PVALI\",\n                \"_PVUC\",\n                \"_PEID\",\n                \"_PEFU\"\n            ]\n        }\n    },\n    \"tags\": [\n        \"group\",\n        \"property group\"\n    ]\n}','utf-8'),(4,'http://www.w3.org/2004/02/skos/core#|[http://www.w3.org/TR/skos-reference/skos.rdf Simple Knowledge Organization System (SKOS)]\n altLabel|Type:Monolingual text\n broader|Type:Annotation URI\n broaderTransitive|Type:Annotation URI\n broadMatch|Type:Annotation URI\n changeNote|Type:Text\n closeMatch|Type:Annotation URI\n Collection|Class\n Concept|Class\n ConceptScheme|Class\n definition|Type:Text\n editorialNote|Type:Text\n exactMatch|Type:Annotation URI\n example|Type:Text\n hasTopConcept|Type:Page\n hiddenLabel|Type:String\n historyNote|Type:Text\n inScheme|Type:Page\n mappingRelation|Type:Page\n member|Type:Page\n memberList|Type:Page\n narrower|Type:Annotation URI\n narrowerTransitive|Type:Annotation URI\n narrowMatch|Type:Annotation URI\n notation|Type:Text\n note|Type:Text\n OrderedCollection|Class\n prefLabel|Type:String\n related|Type:Annotation URI\n relatedMatch|Type:Annotation URI\n scopeNote|Type:Text\n semanticRelation|Type:Page\n topConceptOf|Type:Page\n\n[[Category:Imported vocabulary]]','utf-8'),(5,'http://xmlns.com/foaf/0.1/|[http://www.foaf-project.org/ Friend Of A Friend]\n name|Type:Text\n homepage|Type:URL\n mbox|Type:Email\n mbox_sha1sum|Type:Text\n depiction|Type:URL\n phone|Type:Text\n Person|Category\n Organization|Category\n knows|Type:Page\n member|Type:Page\n\n[[Category:Imported vocabulary]]','utf-8'),(6,'http://www.w3.org/2002/07/owl#|[http://www.w3.org/2002/07/owl Web Ontology Language (OWL)]\n AllDifferent|Category\n allValuesFrom|Type:Page\n AnnotationProperty|Category\n backwardCompatibleWith|Type:Page\n cardinality|Type:Number\n Class|Category\n comment|Type:Page\n complementOf|Type:Page\n DataRange|Category\n DatatypeProperty|Category\n DeprecatedClass|Category\n DeprecatedProperty|Category\n differentFrom|Type:Page\n disjointWith|Type:Page\n distinctMembers|Type:Page\n equivalentClass|Type:Page\n equivalentProperty|Type:Page\n FunctionalProperty|Category\n hasValue|Type:Page\n imports|Type:Page\n incompatibleWith|Type:Page\n intersectionOf|Type:Page\n InverseFunctionalProperty|Category\n inverseOf|Type:Page\n isDefinedBy|Type:Page\n label|Type:Page\n maxCardinality|Type:Number\n minCardinality|Type:Number\n Nothing|Category\n ObjectProperty|Category\n oneOf|Type:Page\n onProperty|Type:Page\n Ontology|Category\n OntologyProperty|Category\n owl|Type:Page\n priorVersion|Type:Page\n Restriction|Category\n sameAs|Type:Page\n seeAlso|Type:Page\n someValuesFrom|Type:Page\n SymmetricProperty|Category\n Thing|Category\n TransitiveProperty|Category\n unionOf|Type:Page\n versionInfo|Type:Page\n\n[[Category:Imported vocabulary]]','utf-8'),(7,'* [[Imported from::foaf:knows]]\n* [[Property description::A person known by this person (indicating some level of reciprocated interaction between the parties).@en]]\n\n[[Category:Imported vocabulary]] {{DISPLAYTITLE:foaf:knows}}','utf-8'),(8,'* [[Imported from::foaf:name]]\n* [[Property description::A name for some thing or agent.@en]]\n\n[[Category:Imported vocabulary]] {{DISPLAYTITLE:foaf:name}}','utf-8'),(9,'* [[Imported from::foaf:homepage]]\n* [[Property description::URL of the homepage of something, which is a general web resource.@en]] \n\n[[Category:Imported vocabulary]] {{DISPLAYTITLE:foaf:homepage}}','utf-8'),(10,'* [[Imported from::owl:differentFrom]]\n* [[Property description::The property that determines that two given individuals are different.@en]]\n\n[[Category:Imported vocabulary]] {{DISPLAYTITLE:owl:differentFrom}}','utf-8');
/*!40000 ALTER TABLE `text` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `updatelog`
--

DROP TABLE IF EXISTS `updatelog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `updatelog` (
  `ul_key` varbinary(255) NOT NULL,
  `ul_value` blob DEFAULT NULL,
  PRIMARY KEY (`ul_key`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `updatelog`
--

LOCK TABLES `updatelog` WRITE;
/*!40000 ALTER TABLE `updatelog` DISABLE KEYS */;
INSERT INTO `updatelog` VALUES ('AddRFCandPMIDInterwiki',NULL),('DeduplicateArchiveRevId',NULL),('DeleteDefaultMessages',NULL),('FixDefaultJsonContentPages',NULL),('MigrateActors',NULL),('MigrateComments',NULL),('PopulateChangeTagDef',NULL),('PopulateContentTables',NULL),('RefreshExternallinksIndex v1+IDN',NULL),('actor-actor_name-patch-actor-actor_name-varbinary.sql',NULL),('cl_fields_update',NULL),('cleanup empty categories',NULL),('externallinks-el_index_60-patch-externallinks-el_index_60-drop-default.sql',NULL),('filearchive-fa_major_mime-patch-fa_major_mime-chemical.sql',NULL),('fix protocol-relative URLs in externallinks',NULL),('image-img_major_mime-patch-img_major_mime-chemical.sql',NULL),('image-img_media_type-patch-add-3d.sql',NULL),('iwlinks-iwl_prefix-patch-extend-iwlinks-iwl_prefix.sql',NULL),('job-patch-job-params-mediumblob.sql',NULL),('mime_minor_length',NULL),('oldimage-oi_major_mime-patch-oi_major_mime-chemical.sql',NULL),('page-page_restrictions-patch-page_restrictions-null.sql',NULL),('populate *_from_namespace',NULL),('populate category',NULL),('populate externallinks.el_index_60',NULL),('populate fa_sha1',NULL),('populate img_sha1',NULL),('populate ip_changes',NULL),('populate log_search',NULL),('populate pp_sortkey',NULL),('populate rev_len and ar_len',NULL),('populate rev_parent_id',NULL),('populate rev_sha1',NULL),('recentchanges-rc_ip-patch-rc_ip_modify.sql',NULL),('site_stats-patch-site_stats-modify.sql',NULL),('sites-site_global_key-patch-sites-site_global_key.sql',NULL),('user_former_groups-ufg_group-patch-ufg_group-length-increase-255.sql',NULL),('user_groups-ug_group-patch-ug_group-length-increase-255.sql',NULL),('user_properties-up_property-patch-up_property.sql',NULL);
/*!40000 ALTER TABLE `updatelog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uploadstash`
--

DROP TABLE IF EXISTS `uploadstash`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploadstash` (
  `us_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `us_user` int(10) unsigned NOT NULL,
  `us_key` varbinary(255) NOT NULL,
  `us_orig_path` varbinary(255) NOT NULL,
  `us_path` varbinary(255) NOT NULL,
  `us_source_type` varbinary(50) DEFAULT NULL,
  `us_timestamp` varbinary(14) NOT NULL,
  `us_status` varbinary(50) NOT NULL,
  `us_chunk_inx` int(10) unsigned DEFAULT NULL,
  `us_props` blob DEFAULT NULL,
  `us_size` int(10) unsigned NOT NULL,
  `us_sha1` varbinary(31) NOT NULL,
  `us_mime` varbinary(255) DEFAULT NULL,
  `us_media_type` enum('UNKNOWN','BITMAP','DRAWING','AUDIO','VIDEO','MULTIMEDIA','OFFICE','TEXT','EXECUTABLE','ARCHIVE','3D') DEFAULT NULL,
  `us_image_width` int(10) unsigned DEFAULT NULL,
  `us_image_height` int(10) unsigned DEFAULT NULL,
  `us_image_bits` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`us_id`),
  UNIQUE KEY `us_key` (`us_key`),
  KEY `us_user` (`us_user`),
  KEY `us_timestamp` (`us_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uploadstash`
--

LOCK TABLES `uploadstash` WRITE;
/*!40000 ALTER TABLE `uploadstash` DISABLE KEYS */;
/*!40000 ALTER TABLE `uploadstash` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varbinary(255) NOT NULL DEFAULT '',
  `user_real_name` varbinary(255) NOT NULL DEFAULT '',
  `user_password` tinyblob NOT NULL,
  `user_newpassword` tinyblob NOT NULL,
  `user_newpass_time` binary(14) DEFAULT NULL,
  `user_email` tinyblob NOT NULL,
  `user_touched` binary(14) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `user_token` binary(32) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `user_email_authenticated` binary(14) DEFAULT NULL,
  `user_email_token` binary(32) DEFAULT NULL,
  `user_email_token_expires` binary(14) DEFAULT NULL,
  `user_registration` binary(14) DEFAULT NULL,
  `user_editcount` int(11) DEFAULT NULL,
  `user_password_expires` varbinary(14) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `user_email_token` (`user_email_token`),
  KEY `user_email` (`user_email`(50))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'WikiSysop','',':pbkdf2:sha512:30000:64:Bkc6x7fWlXfKvvpVQk7H5w==:QTJoAT2boIkJ/pq1IBE0hy+CqobJTivDJI8wY7BfJ8lEc1M3H7PZwQS0JWcr9kZEw4Me4du3uHws/YWPg2oCqA==','',NULL,'','20210916140536','1bc7a86ff234b8f6863dab7d9c0b8865',NULL,'\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',NULL,'20210916140535',0,NULL),(2,'MediaWiki default','','','',NULL,'','20210916140536','*** INVALID ***\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',NULL,NULL,NULL,'20210916140536',0,NULL);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_former_groups`
--

DROP TABLE IF EXISTS `user_former_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_former_groups` (
  `ufg_user` int(10) unsigned NOT NULL DEFAULT 0,
  `ufg_group` varbinary(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ufg_user`,`ufg_group`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_former_groups`
--

LOCK TABLES `user_former_groups` WRITE;
/*!40000 ALTER TABLE `user_former_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_former_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_groups`
--

DROP TABLE IF EXISTS `user_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_groups` (
  `ug_user` int(10) unsigned NOT NULL DEFAULT 0,
  `ug_group` varbinary(255) NOT NULL DEFAULT '',
  `ug_expiry` varbinary(14) DEFAULT NULL,
  PRIMARY KEY (`ug_user`,`ug_group`),
  KEY `ug_group` (`ug_group`),
  KEY `ug_expiry` (`ug_expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_groups`
--

LOCK TABLES `user_groups` WRITE;
/*!40000 ALTER TABLE `user_groups` DISABLE KEYS */;
INSERT INTO `user_groups` VALUES (1,'bureaucrat',NULL),(1,'interface-admin',NULL),(1,'sysop',NULL);
/*!40000 ALTER TABLE `user_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_newtalk`
--

DROP TABLE IF EXISTS `user_newtalk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_newtalk` (
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `user_ip` varbinary(40) NOT NULL DEFAULT '',
  `user_last_timestamp` varbinary(14) DEFAULT NULL,
  KEY `un_user_id` (`user_id`),
  KEY `un_user_ip` (`user_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_newtalk`
--

LOCK TABLES `user_newtalk` WRITE;
/*!40000 ALTER TABLE `user_newtalk` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_newtalk` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_properties`
--

DROP TABLE IF EXISTS `user_properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_properties` (
  `up_user` int(10) unsigned NOT NULL,
  `up_property` varbinary(255) NOT NULL,
  `up_value` blob DEFAULT NULL,
  PRIMARY KEY (`up_user`,`up_property`),
  KEY `user_properties_property` (`up_property`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_properties`
--

LOCK TABLES `user_properties` WRITE;
/*!40000 ALTER TABLE `user_properties` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_properties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `watchlist`
--

DROP TABLE IF EXISTS `watchlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `watchlist` (
  `wl_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wl_user` int(10) unsigned NOT NULL,
  `wl_namespace` int(11) NOT NULL DEFAULT 0,
  `wl_title` varbinary(255) NOT NULL DEFAULT '',
  `wl_notificationtimestamp` varbinary(14) DEFAULT NULL,
  PRIMARY KEY (`wl_id`),
  UNIQUE KEY `wl_user` (`wl_user`,`wl_namespace`,`wl_title`),
  KEY `namespace_title` (`wl_namespace`,`wl_title`),
  KEY `wl_user_notificationtimestamp` (`wl_user`,`wl_notificationtimestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `watchlist`
--

LOCK TABLES `watchlist` WRITE;
/*!40000 ALTER TABLE `watchlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `watchlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `watchlist_expiry`
--

DROP TABLE IF EXISTS `watchlist_expiry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `watchlist_expiry` (
  `we_item` int(10) unsigned NOT NULL,
  `we_expiry` binary(14) NOT NULL,
  PRIMARY KEY (`we_item`),
  KEY `we_expiry` (`we_expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `watchlist_expiry`
--

LOCK TABLES `watchlist_expiry` WRITE;
/*!40000 ALTER TABLE `watchlist_expiry` DISABLE KEYS */;
/*!40000 ALTER TABLE `watchlist_expiry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_farm`
--

DROP TABLE IF EXISTS `wiki_farm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_farm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wiki_name` varchar(255) NOT NULL,
  `fk_created_by` int(10) unsigned NOT NULL,
  `wiki_status` varchar(16) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_created_by` (`fk_created_by`),
  CONSTRAINT `wiki_farm_ibfk_1` FOREIGN KEY (`fk_created_by`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_farm`
--

LOCK TABLES `wiki_farm` WRITE;
/*!40000 ALTER TABLE `wiki_farm` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_farm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_farm_user`
--

DROP TABLE IF EXISTS `wiki_farm_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_farm_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user_id` int(10) unsigned NOT NULL,
  `fk_wiki_id` int(11) NOT NULL,
  `status_enum` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_user_id` (`fk_user_id`),
  KEY `fk_wiki_id` (`fk_wiki_id`),
  CONSTRAINT `wiki_farm_user_ibfk_1` FOREIGN KEY (`fk_user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `wiki_farm_user_ibfk_2` FOREIGN KEY (`fk_wiki_id`) REFERENCES `wiki_farm` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_farm_user`
--

LOCK TABLES `wiki_farm_user` WRITE;
/*!40000 ALTER TABLE `wiki_farm_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_farm_user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-02-04 13:13:37
