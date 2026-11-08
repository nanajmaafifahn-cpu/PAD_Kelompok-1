
CREATE DATABASE IF NOT EXISTS `campus_space_xampp` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `campus_space_xampp`;

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_units_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `academic_units` WRITE;
/*!40000 ALTER TABLE `academic_units` DISABLE KEYS */;
INSERT INTO `academic_units` VALUES (1,'FRI','Fakultas Rekayasa Industri',NULL,'2026-11-08 16:03:45','2026-11-08 16:03:45'),(2,'FIF','Fakultas Informatika',NULL,'2026-11-08 16:03:45','2026-11-08 16:03:45'),(3,'FTE','Fakultas Teknik Elektro',NULL,'2026-11-08 16:03:45','2026-11-08 16:03:45');
/*!40000 ALTER TABLE `academic_units` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `requester_name` varchar(150) DEFAULT NULL,
  `requester_role` varchar(100) DEFAULT NULL,
  `purpose` varchar(255) NOT NULL,
  `participant_count` int(10) unsigned NOT NULL DEFAULT 1,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `qr_token` varchar(64) DEFAULT NULL,
  `checked_in_at` datetime DEFAULT NULL,
  `checked_out_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bookings_qr_token_unique` (`qr_token`),
  KEY `bookings_user_id_foreign` (`user_id`),
  KEY `bookings_room_id_start_time_end_time_index` (`room_id`,`start_time`,`end_time`),
  CONSTRAINT `bookings_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `bookings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (1,1,1,'Andi Pratama (Mahasiswa)','Mahasiswa / Dosen','Kerja kelompok tugas besar',25,'2026-10-26 10:00:00','2026-10-26 12:00:00','completed','9z8sUgN6fNsUhk2Ga04jahmi5VFX6wKu','2026-10-26 10:05:00','2026-10-26 12:00:00',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(2,1,2,'Dr. Sinta Rahmawati (Dosen)','Mahasiswa / Dosen','Latihan simulasi Arena',18,'2026-10-26 13:00:00','2026-10-26 15:00:00','completed','irEMrKQqzZR5fZSMotwDHSphOTxWdbfC','2026-10-26 13:00:00','2026-10-26 14:30:00',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(3,1,1,'Andi Pratama (Mahasiswa)','Mahasiswa / Dosen','Pelatihan Excel anggota HMTI',30,'2026-10-27 08:00:00','2026-10-27 10:00:00','no_show','6Hn0GFbJn2DMHPmb98HDsvPIdqsfJwId',NULL,NULL,NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(4,1,1,'Andi Pratama (Mahasiswa)','Mahasiswa / Dosen','Pengolahan data skripsi',22,'2026-10-28 13:00:00','2026-10-28 16:00:00','completed','JaohshILfRo1AhQGwpSbYLeviQxS9YE5','2026-10-28 13:12:00','2026-10-28 16:00:00',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(5,1,2,'Dr. Sinta Rahmawati (Dosen)','Mahasiswa / Dosen','Asistensi praktikum',15,'2026-10-30 13:00:00','2026-10-30 15:00:00','no_show','Mce16E0yUl1k6HOfXPjHZnUjJXYMdWDa',NULL,NULL,NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(6,2,1,'Andi Pratama (Mahasiswa)','Mahasiswa / Dosen','Praktikum pengganti PPIC',28,'2026-10-26 08:00:00','2026-10-26 11:00:00','completed','9W0rvZcJPDSDZt8BOHhVUsE9iBN2tUQq','2026-10-26 08:00:00','2026-10-26 11:00:00',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(7,2,1,'Andi Pratama (Mahasiswa)','Mahasiswa / Dosen','Workshop lean manufacturing',30,'2026-10-28 08:00:00','2026-10-28 12:00:00','completed','Bk78skgD31HJv6Scen9HsqTfdbDr3Fmb','2026-10-28 08:03:00','2026-10-28 12:00:00',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(8,2,2,'Dr. Sinta Rahmawati (Dosen)','Mahasiswa / Dosen','Pengambilan data penelitian',26,'2026-10-29 13:00:00','2026-10-29 16:00:00','completed','UqPio07vpXUisEFsu2TEp8pr17qhbhzo','2026-10-29 13:00:00','2026-10-29 15:45:00',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(9,4,1,'Andi Pratama (Mahasiswa)','Mahasiswa / Dosen','Rapat pengurus HMTI',8,'2026-10-27 08:00:00','2026-10-27 10:00:00','completed','NI4Z1fcJAKGn09SSrAL8W1XNbKQHhLhD','2026-10-27 08:20:00','2026-10-27 09:30:00',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(10,4,1,'Andi Pratama (Mahasiswa)','Mahasiswa / Dosen','Belajar kelompok',6,'2026-10-29 09:00:00','2026-10-29 11:00:00','no_show','lHravU5aQnj7b97b8AloNLv22dkX9RfO',NULL,NULL,NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(11,1,1,'Andi Pratama (Mahasiswa)','Mahasiswa / Dosen','Kerja kelompok tugas besar PAD',8,'2026-11-09 10:00:00','2026-11-09 12:00:00','approved','mt7fR3wpA2NnQwvSlgbQBlZslXRk9ee5',NULL,NULL,NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(12,2,2,'Dr. Sinta Rahmawati (Dosen)','Mahasiswa / Dosen','Asistensi praktikum PPIC',20,'2026-11-10 13:00:00','2026-11-10 15:00:00','pending',NULL,NULL,NULL,NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(13,3,1,'Andi Pratama (Mahasiswa)','Mahasiswa / Dosen','Seminar himpunan',50,'2026-11-09 08:00:00','2026-11-09 10:00:00','rejected',NULL,NULL,NULL,'Pengajuan masuk kurang dari 24 jam sebelum kegiatan.','2026-11-08 16:03:46','2026-11-08 16:03:46'),(14,7,1,'Andi Pratama (Mahasiswa)','Mahasiswa / Dosen','Kerja kelompok tugas besar PAD',20,'2026-11-08 23:13:00','2026-11-09 01:13:00','approved','QHZgfh17JZDjFElx5OYos9eXQHT3IfG6p09krLEX',NULL,NULL,NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(15,11,2,'Dr. Sinta Rahmawati (Dosen)','Mahasiswa / Dosen','Asistensi praktikum Basis Data',35,'2026-11-08 22:33:00','2026-11-09 00:33:00','checked_in','MeM1VHRYF76373IXwSaGVFYsMWxFpk6LRd6Jowq1','2026-11-08 22:38:00',NULL,NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(16,13,1,'Andi Pratama (Mahasiswa)','Mahasiswa / Dosen','Latihan presentasi seminar',15,'2026-11-09 09:00:00','2026-11-09 11:00:00','pending',NULL,NULL,NULL,NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(17,14,2,'Dr. Sinta Rahmawati (Dosen)','Mahasiswa / Dosen','Kuliah pengganti Statistika',40,'2026-11-09 13:00:00','2026-11-09 15:00:00','approved','b2G1UK9HjSkFjlCS8ndfcHi5iBdRGecQfMnOHFxL',NULL,NULL,NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(18,13,1,'Andi Pratama (Mahasiswa)','Mahasiswa / Dosen','Rapat himpunan',45,'2026-11-10 08:00:00','2026-11-10 10:00:00','rejected',NULL,NULL,NULL,'Ruang dipakai ujian praktikum pada jam tersebut.','2026-11-08 16:03:46','2026-11-08 16:03:46'),(19,14,2,'Dr. Sinta Rahmawati (Dosen)','Mahasiswa / Dosen','Bimbingan tugas akhir',25,'2026-11-10 10:00:00','2026-11-10 12:00:00','cancelled',NULL,NULL,NULL,NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(20,7,1,'Andi Pratama (Mahasiswa)','Mahasiswa / Dosen','Pengolahan data penelitian',18,'2026-11-07 09:00:00','2026-11-07 11:00:00','completed','ikuIMNPgk9mPQlLm85CdPIYe76P5YhwmwYWAnzeE','2026-11-07 09:05:00','2026-11-07 10:50:00',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(21,11,2,'Dr. Sinta Rahmawati (Dosen)','Mahasiswa / Dosen','Pelatihan software simulasi',30,'2026-11-07 13:00:00','2026-11-07 15:00:00','no_show','jQoBpXuKQ9XBWYgNMXX6nX8lQTmogTcq0OdxZcg5',NULL,NULL,NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facilities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` bigint(20) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `status` varchar(30) NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `facilities_room_id_foreign` (`room_id`),
  CONSTRAINT `facilities_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `facilities` WRITE;
/*!40000 ALTER TABLE `facilities` DISABLE KEYS */;
INSERT INTO `facilities` VALUES (1,1,'PC Core i5 RAM 16 GB',40,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(2,1,'Proyektor',1,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(3,2,'Mesin CNC mini',2,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(4,2,'Conveyor simulasi lini produksi',1,'damaged','2026-11-08 16:03:46','2026-11-08 16:03:46'),(5,2,'Meja kerja',10,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(6,3,'Proyektor',1,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(7,4,'Proyektor',1,'maintenance','2026-11-08 16:03:46','2026-11-08 16:03:46'),(8,5,'AC',2,'maintenance','2026-11-08 16:03:46','2026-11-08 16:03:46'),(9,6,'PC Komputer',48,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(10,6,'Proyektor',1,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(11,6,'AC',2,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(12,7,'PC Komputer',48,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(13,7,'Proyektor',1,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(14,7,'AC',2,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(15,8,'Proyektor',1,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(16,8,'AC',2,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(17,9,'PC Komputer',48,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(18,9,'Proyektor',1,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(19,9,'AC',2,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(20,10,'Proyektor',1,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(21,10,'AC',2,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(22,11,'PC Komputer',48,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(23,11,'Proyektor',1,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(24,11,'AC',2,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(25,12,'Proyektor',1,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(26,12,'AC',2,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(27,13,'PC Komputer',48,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(28,13,'Proyektor',1,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(29,13,'AC',2,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(30,14,'PC Komputer',48,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(31,14,'Proyektor',1,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(32,14,'AC',2,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(33,15,'Proyektor',1,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(34,15,'AC',2,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(35,16,'PC Komputer',48,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(36,16,'Proyektor',1,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(37,16,'AC',2,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(38,17,'Proyektor',1,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(39,17,'AC',2,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(40,18,'PC Komputer',48,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(41,18,'Proyektor',1,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(42,18,'AC',2,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(43,19,'Proyektor',1,'available','2026-11-08 16:03:46','2026-11-08 16:03:46'),(44,19,'AC',2,'maintenance','2026-11-08 16:03:46','2026-11-08 16:03:46');
/*!40000 ALTER TABLE `facilities` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_10_18_144433_create_academic_units_table',1),(5,'2026_10_18_144434_create_rooms_table',1),(6,'2026_10_18_144435_create_facilities_table',1),(7,'2026_10_18_144436_create_schedules_table',1),(8,'2026_10_18_144437_create_bookings_table',1),(9,'2026_10_20_080000_add_role_to_users_table',1),(10,'2026_10_20_080100_add_type_to_rooms_table',1),(11,'2026_10_20_080200_add_attendance_columns_to_bookings_table',1),(12,'2026_10_20_090000_add_requester_fields_to_bookings_table',1),(13,'2026_11_07_100000_add_qr_token_to_rooms_table',1),(14,'2026_11_07_110000_add_photo_path_to_rooms_table',1),(15,'2026_11_07_110100_add_is_active_to_users_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rooms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `academic_unit_id` bigint(20) unsigned NOT NULL,
  `code` varchar(30) NOT NULL,
  `qr_token` varchar(64) DEFAULT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'classroom',
  `name` varchar(150) NOT NULL,
  `building` varchar(100) NOT NULL,
  `floor` varchar(30) DEFAULT NULL,
  `capacity` int(10) unsigned NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'available',
  `description` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rooms_code_unique` (`code`),
  UNIQUE KEY `rooms_qr_token_unique` (`qr_token`),
  KEY `rooms_academic_unit_id_foreign` (`academic_unit_id`),
  CONSTRAINT `rooms_academic_unit_id_foreign` FOREIGN KEY (`academic_unit_id`) REFERENCES `academic_units` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (1,1,'LAB-KOM-01','cYgr62JmBEPYTQllr3hJ5kD3meVEWorZfmxbLOnY','laboratory','Laboratorium Komputer 1','Gedung FRI','2',40,'available','Lab praktikum pemrograman dan simulasi.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(2,1,'LAB-MAN-01','qUA6v8j8ISLftaUgFvuZsW8a3fLR09oxTZEmbqDV','laboratory','Laboratorium Manufaktur','Gedung FRI','1',30,'available','Lab praktikum sistem produksi dan PPIC.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(3,1,'KLS-201','lJCgbQ4WnWtADkpez3Ar7f2T8EGK7BFgVBnhQYYt','classroom','Ruang Kelas 201','Gedung FRI','2',60,'available','Ruang kuliah reguler.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(4,1,'KLS-202','RkOQ2r4ZZQVsrqZ8I2ngKJmxNikV17lCPa2TptNn','classroom','Ruang Kelas 202','Gedung FRI','2',40,'available','Ruang kuliah reguler.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(5,1,'KLS-203','sJEdT40IfXLZnNpPZyBZdcHEprbQ0pym5rMiQJBL','classroom','Ruang Kelas 203','Gedung FRI','2',40,'maintenance','Sedang perbaikan AC.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(6,1,'TULT-0805','NPAaCPpG0xmOFuBrIp8tUnCi41ColhuKCwAB64sk','laboratory','R1','Telkom University Landmark Tower (TULT)','8',48,'available','Laboratorium komputer untuk praktikum dan kerja kelompok.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(7,1,'TULT-0807','jRNcLaHqIspP8RnQdElniQBdQQXma7wYbEjbmUEf','laboratory','R2','Telkom University Landmark Tower (TULT)','8',48,'available','Laboratorium komputer untuk praktikum dan kerja kelompok.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(8,1,'TULT-0819','oqVCAvS92tu30i8OasseZPat1cnsvmBLVcGL502m','classroom','R3','Telkom University Landmark Tower (TULT)','8',50,'available','Ruang kelas untuk perkuliahan dan diskusi.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(9,1,'TULT-0906','lBViq3phgHLwHlNBm2t7JZkIb8fARHr29PcvLObh','laboratory','R5','Telkom University Landmark Tower (TULT)','9',48,'available','Laboratorium komputer untuk praktikum dan kerja kelompok.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(10,1,'TULT-0912','qYmiZAupezNyYWTg36gbdkXI5uPm6TudtmZuHw6H','classroom','R6','Telkom University Landmark Tower (TULT)','9',48,'available','Ruang kelas untuk perkuliahan dan diskusi.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(11,1,'TULT-0913','Ga0anWF5NRc0603SQvOiDcYpH4AL2u7Q2xDRqokR','laboratory','R7','Telkom University Landmark Tower (TULT)','9',48,'available','Laboratorium komputer untuk praktikum dan kerja kelompok.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(12,1,'TULT-0914','lxfmdpICAAkzxkrHecai22DAbHSPjND1xio3WMEs','classroom','R8','Telkom University Landmark Tower (TULT)','9',50,'available','Ruang kelas untuk perkuliahan dan diskusi.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(13,1,'TULT-1005','fdxYX2DjljjX9K6u8o2Hz3rksfijVI7GmiDJUJ1o','laboratory','R9','Telkom University Landmark Tower (TULT)','10',48,'available','Laboratorium komputer untuk praktikum dan kerja kelompok.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(14,1,'TULT-1007','90IMeq1ZHnzUKzgDuFjBE9VnXFx3TGjzravewLwK','laboratory','R10','Telkom University Landmark Tower (TULT)','10',48,'available','Laboratorium komputer untuk praktikum dan kerja kelompok.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(15,1,'TULT-1019','85sqoehNX57sdzOym2LcbGz7Wo9KdVoykoieyLmf','classroom','R11','Telkom University Landmark Tower (TULT)','10',50,'available','Ruang kelas untuk perkuliahan dan diskusi.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(16,1,'TULT-1106','PAoX3eGyWiIANJtvvdyWGJGAUxatV28nw2tu4n7X','laboratory','R12','Telkom University Landmark Tower (TULT)','11',48,'available','Laboratorium komputer untuk praktikum dan kerja kelompok.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(17,1,'TULT-1112','Hx8UqDtawHvb2z7XduqmqxusMpJ1XgQRgGcKC55V','classroom','R13','Telkom University Landmark Tower (TULT)','11',49,'available','Ruang kelas untuk perkuliahan dan diskusi.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(18,1,'TULT-1113','DvXjAwD69krQLrVJDjybYvUQgZBgrI59d51MJvaN','laboratory','R14','Telkom University Landmark Tower (TULT)','11',48,'available','Laboratorium komputer untuk praktikum dan kerja kelompok.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46'),(19,1,'TULT-1114','19hOTnsY0mF57UpBLmR6x059lA0vXwLoCTZJdlze','classroom','R15','Telkom University Landmark Tower (TULT)','11',50,'maintenance','Ruang kelas untuk perkuliahan dan diskusi.',NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46');
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` bigint(20) unsigned NOT NULL,
  `title` varchar(150) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `schedules_room_id_foreign` (`room_id`),
  CONSTRAINT `schedules_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `schedules` WRITE;
/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
INSERT INTO `schedules` VALUES (1,3,'Perancangan Aplikasi Dinamis','2026-10-26 07:30:00','2026-10-26 10:00:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(2,3,'Statistika Industri','2026-10-28 10:30:00','2026-10-28 13:00:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(3,4,'Basis Data','2026-10-27 13:00:00','2026-10-27 15:30:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(4,1,'Praktikum Pemrograman','2026-10-29 07:30:00','2026-10-29 10:30:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(5,2,'Praktikum PPIC','2026-10-27 13:00:00','2026-10-27 16:00:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(6,8,'Statistika Industri','2026-11-02 08:00:00','2026-11-02 10:00:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(7,8,'Statistika Industri','2026-11-03 08:00:00','2026-11-03 10:00:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(8,8,'Statistika Industri','2026-11-04 08:00:00','2026-11-04 10:00:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(9,6,'Praktikum Basis Data','2026-11-02 10:00:00','2026-11-02 12:00:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(10,6,'Praktikum Basis Data','2026-11-03 10:00:00','2026-11-03 12:00:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(11,6,'Praktikum Basis Data','2026-11-06 10:00:00','2026-11-06 12:00:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(12,10,'Perancangan Aplikasi Dinamis','2026-11-02 13:00:00','2026-11-02 15:00:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(13,10,'Perancangan Aplikasi Dinamis','2026-11-05 13:00:00','2026-11-05 15:00:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(14,10,'Perancangan Aplikasi Dinamis','2026-11-06 13:00:00','2026-11-06 15:00:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(15,9,'Praktikum Pemrograman','2026-11-04 15:00:00','2026-11-04 17:00:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(16,9,'Praktikum Pemrograman','2026-11-05 15:00:00','2026-11-05 17:00:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(17,9,'Praktikum Pemrograman','2026-11-06 15:00:00','2026-11-06 17:00:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(18,12,'Sistem Produksi','2026-11-03 07:30:00','2026-11-03 09:30:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(19,12,'Sistem Produksi','2026-11-04 07:30:00','2026-11-04 09:30:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46'),(20,12,'Sistem Produksi','2026-11-05 07:30:00','2026-11-05 09:30:00','scheduled','2026-11-08 16:03:46','2026-11-08 16:03:46');
/*!40000 ALTER TABLE `schedules` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(30) NOT NULL DEFAULT 'borrower',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Andi Pratama (Mahasiswa)','mahasiswa@campus.test',NULL,'$2y$12$mEMZyfXn/j0iQk1zUboPm.XYJjxxTK8pIlpGmSLXNd27OnWULGO6i','borrower',1,NULL,'2026-11-08 16:03:45','2026-11-08 16:03:45'),(2,'Dr. Sinta Rahmawati (Dosen)','dosen@campus.test',NULL,'$2y$12$CdrkaG2wJCeSIUek9QE.COmUOQ5Vkmhnc1t8Gox5lHDChK2xCZT6W','borrower',1,NULL,'2026-11-08 16:03:45','2026-11-08 16:03:45'),(3,'Kepala Laboratorium FRI','pengelola@campus.test',NULL,'$2y$12$yp2rFu34pMxotR/8w4D5JuO3l8Gial2UA7ubeFySuV4ul9gQRivsW','lab_head',1,NULL,'2026-11-08 16:03:45','2026-11-08 16:03:45'),(4,'Admin Akademik FRI','admin@campus.test',NULL,'$2y$12$OhzdnSY2zbxu9kT334Ndc.B13Ib.lBOcuYVOSJaQ2F0xdbINYqm.a','admin',1,NULL,'2026-11-08 16:03:45','2026-11-08 16:03:45'),(5,'Wakil Dekan II FRI','pimpinan@campus.test',NULL,'$2y$12$FPEhySwxO4sXGct7J5Og2OWE5aYqchYU8lkDY7KBx6QAgF.hnoSjm','leader',1,NULL,'2026-11-08 16:03:46','2026-11-08 16:03:46');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
