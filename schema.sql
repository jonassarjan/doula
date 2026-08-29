-- Death Doula Registry — Database Schema
-- MySQL 8.0+  |  charset: utf8mb4

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- --------------------------------------------------------
-- admins
-- --------------------------------------------------------
CREATE TABLE `admins` (
    `id`         BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(255)        NOT NULL,
    `password`   VARCHAR(255)        NOT NULL,
    `created_at` TIMESTAMP           NULL DEFAULT NULL,
    `updated_at` TIMESTAMP           NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- languages
-- --------------------------------------------------------
CREATE TABLE `languages` (
    `id`   SMALLINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `code` CHAR(2)             NOT NULL COMMENT 'ISO 639-1',
    `name` VARCHAR(100)        NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `languages_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `languages` (`code`, `name`) VALUES
('af', 'Afrikaans'),
('ar', 'Arabic'),
('bn', 'Bengali'),
('cs', 'Czech'),
('da', 'Danish'),
('de', 'German'),
('el', 'Greek'),
('en', 'English'),
('es', 'Spanish'),
('et', 'Estonian'),
('fa', 'Persian'),
('fi', 'Finnish'),
('fr', 'French'),
('he', 'Hebrew'),
('hi', 'Hindi'),
('hr', 'Croatian'),
('hu', 'Hungarian'),
('id', 'Indonesian'),
('it', 'Italian'),
('ja', 'Japanese'),
('ko', 'Korean'),
('lt', 'Lithuanian'),
('lv', 'Latvian'),
('ms', 'Malay'),
('nl', 'Dutch'),
('no', 'Norwegian'),
('pl', 'Polish'),
('pt', 'Portuguese'),
('ro', 'Romanian'),
('ru', 'Russian'),
('sk', 'Slovak'),
('sl', 'Slovenian'),
('sq', 'Albanian'),
('sr', 'Serbian'),
('sv', 'Swedish'),
('sw', 'Swahili'),
('th', 'Thai'),
('tr', 'Turkish'),
('uk', 'Ukrainian'),
('ur', 'Urdu'),
('vi', 'Vietnamese'),
('zh', 'Chinese');

-- --------------------------------------------------------
-- doulas
-- --------------------------------------------------------
CREATE TABLE `doulas` (
    `id`               BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `name`             VARCHAR(255)        NOT NULL,
    `bio`              TEXT                NULL,
    `photo`            VARCHAR(500)        NULL,
    `certifications`   TEXT                NULL,
    `years_experience` TINYINT UNSIGNED    NULL,
    `email`            VARCHAR(255)        NULL,
    `phone`            VARCHAR(50)         NULL,
    `website`          VARCHAR(500)        NULL,
    `city`             VARCHAR(255)        NOT NULL,
    `country`          VARCHAR(255)        NOT NULL,
    `latitude`         DECIMAL(10, 7)      NOT NULL,
    `longitude`        DECIMAL(10, 7)      NOT NULL,
    `is_active`        TINYINT(1)          NOT NULL DEFAULT 1,
    `created_at`       TIMESTAMP           NULL DEFAULT NULL,
    `updated_at`       TIMESTAMP           NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `doulas_country_index` (`country`),
    KEY `doulas_city_index` (`city`),
    KEY `doulas_is_active_index` (`is_active`),
    KEY `doulas_latlng_index` (`latitude`, `longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- doula_language  (pivot)
-- --------------------------------------------------------
CREATE TABLE `doula_language` (
    `doula_id`    BIGINT UNSIGNED     NOT NULL,
    `language_id` SMALLINT UNSIGNED   NOT NULL,
    PRIMARY KEY (`doula_id`, `language_id`),
    KEY `doula_language_language_id_index` (`language_id`),
    CONSTRAINT `fk_doula_language_doula`
        FOREIGN KEY (`doula_id`)    REFERENCES `doulas`    (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_doula_language_language`
        FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
