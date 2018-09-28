BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS `users` (
	`id`	TEXT NOT NULL UNIQUE,
	`name`	TEXT UNIQUE,
	`password`	TEXT UNIQUE,
	`email`	TEXT NOT NULL UNIQUE,
	`active`	INTEGER DEFAULT 0,
	`role`	TEXT DEFAULT 'user',
	`timestamp`	TEXT,
	PRIMARY KEY(`id`)
);
CREATE TABLE IF NOT EXISTS `rsvp` (
	`group_id`	TEXT,
	`event_id`	TEXT,
	`user_id`	TEXT,
	`rsvp`	TEXT,
	`timestamp`	TEXT
);
CREATE TABLE IF NOT EXISTS `groups` (
	`id`	TEXT NOT NULL,
	`name`	TEXT NOT NULL,
	`description`	TEXT,
	`timestamp`	TEXT,
	PRIMARY KEY(`id`)
);
CREATE TABLE IF NOT EXISTS `group_members` (
	`group_id`	TEXT NOT NULL,
	`user_id`	TEXT NOT NULL,
	`role`	TEXT DEFAULT 'member',
	`timestamp`	TEXT
);
CREATE TABLE IF NOT EXISTS `group_events` (
	`id`	TEXT NOT NULL,
	`group_id`	TEXT NOT NULL,
	`name`	TEXT NOT NULL,
	`description`	TEXT,
	`location`	TEXT,
	`address`	TEXT,
	`lat`	NUMERIC,
	`lon`	NUMERIC,
	`date`	TEXT,
	`limit`	INTEGER DEFAULT -1,
	`timestamp`	TEXT,
	PRIMARY KEY(`id`)
);
COMMIT;
