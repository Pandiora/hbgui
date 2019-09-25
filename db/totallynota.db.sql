BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "files_meta" (
	"id"	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
	"fid"	INTEGER,
	"video_codec"	TEXT NOT NULL,
	"video_res_x"	INTEGER NOT NULL,
	"video_res_y"	INTEGER NOT NULL,
	"video_framerate"	NUMERIC NOT NULL,
	"video_bitrate"	INTEGER NOT NULL,
	"audio_codec"	TEXT NOT NULL,
	"audio_samplerate"	INTEGER NOT NULL,
	"audio_bitrate"	INTEGER NOT NULL,
	"audio_channels"	NUMERIC NOT NULL,
	"playtime"	INTEGER NOT NULL,
	"bitrate"	INTEGER NOT NULL,
	"muxing_app"	TEXT NOT NULL,
	"writing_app"	TEXT NOT NULL,
	"encoder"	TEXT NOT NULL,
	"created"	TEXT NOT NULL,
	"added"	INTEGER NOT NULL
);
CREATE TABLE IF NOT EXISTS "file_status" (
	"id"	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
	"sid"	INTEGER NOT NULL UNIQUE,
	"status"	TEXT
);
CREATE TABLE IF NOT EXISTS "menus" (
	"id"	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
	"name"	TEXT NOT NULL,
	"placeholder"	TEXT
);
CREATE TABLE IF NOT EXISTS "settings" (
	"id"	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
	"sid"	INTEGER NOT NULL,
	"name"	TEXT NOT NULL UNIQUE,
	"val1"	TEXT,
	"val2"	TEXT,
	"type"	TEXT NOT NULL,
	"label"	TEXT NOT NULL,
	"placeholder"	TEXT
);
CREATE TABLE IF NOT EXISTS "files_history" (
	"id"	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
	"fid"	INTEGER,
	"status"	INTEGER,
	"msg"	TEXT NOT NULL,
	"added"	TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS "files" (
	"id"	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
	"commit_id"	NUMERIC NOT NULL,
	"file_path"	TEXT NOT NULL,
	"file_name"	TEXT NOT NULL,
	"file_dev"	NUMERIC NOT NULL,
	"file_inode"	NUMERIC NOT NULL UNIQUE,
	"file_codec"	TEXT,
	"file_progress"	INTEGER NOT NULL DEFAULT 0,
	"file_status"	INTEGER NOT NULL DEFAULT 0,
	"file_size"	REAL NOT NULL,
	"file_ext"	TEXT NOT NULL,
	"added"	NUMERIC NOT NULL,
	"updated"	NUMERIC
);
INSERT INTO "file_status" VALUES (1,10,'Scanned');
INSERT INTO "file_status" VALUES (2,11,'Scanned - Metadata');
INSERT INTO "file_status" VALUES (3,20,'Queued');
INSERT INTO "file_status" VALUES (4,21,'Working');
INSERT INTO "file_status" VALUES (5,31,'Transcoded');
INSERT INTO "file_status" VALUES (6,40,'Broken');
INSERT INTO "file_status" VALUES (7,41,'Transcode failed');
INSERT INTO "file_status" VALUES (8,42,'Deleted');
INSERT INTO "menus" VALUES (1,'Files',NULL);
INSERT INTO "menus" VALUES (2,'Queue',NULL);
INSERT INTO "menus" VALUES (3,'History',NULL);
INSERT INTO "menus" VALUES (4,'Settings',NULL);
INSERT INTO "settings" VALUES (1,10,'watch_folders','/home/Public/Filme,/home/Public/Serien/',NULL,'tags','Watch folders','comma-separated');
INSERT INTO "settings" VALUES (2,20,'watch_interval','3600',NULL,'number','Watch interval','in seconds');
INSERT INTO "settings" VALUES (3,30,'destination_folder',NULL,NULL,'text','Destination folder','blank = source');
INSERT INTO "settings" VALUES (4,40,'config_path',NULL,NULL,'text','HB config path','path to handbrake config file');
INSERT INTO "settings" VALUES (5,50,'trash_path',NULL,NULL,'text','Trash path','path where removed files get moved to');
INSERT INTO "settings" VALUES (6,60,'temp_path',NULL,NULL,'text','Temp path','path where files being moved after being processed');
INSERT INTO "settings" VALUES (7,70,'src_file_extensions','mp4,mkv,avi,mov,iso,mpeg,mpg,ts',NULL,'tags','Source file extensions','example: mp4');
INSERT INTO "settings" VALUES (8,80,'gui_auto_refresh','5',NULL,'number','GUI auto refresh','in seconds');
INSERT INTO "settings" VALUES (9,90,'exclude_by_size','50',NULL,'number','Exclude by size','greater than x in megabytes');
INSERT INTO "settings" VALUES (10,100,'cpu_threads','1',NULL,'number','Max CPU threads','number >= 1');
INSERT INTO "settings" VALUES (11,110,'auto_batch','0',NULL,'switch','Automatic Batch Transcoding',NULL);
INSERT INTO "settings" VALUES (12,120,'exclude_samples','1',NULL,'switch','Exclude sample files',NULL);
INSERT INTO "settings" VALUES (13,130,'move_to_temp','1',NULL,'switch','Move original to temp after Transcoding',NULL);
INSERT INTO "settings" VALUES (14,130,'delete_original','0',NULL,'switch','Delete original after Transcoding',NULL);
INSERT INTO "settings" VALUES (15,140,'delete_broken','0',NULL,'switch','Delete broken files automatically',NULL);
INSERT INTO "settings" VALUES (16,61,'move_broken',NULL,NULL,'text','Move broken files','blank = do not move');
INSERT INTO "settings" VALUES (17,150,'delete_bigones','1',NULL,'switch','Delete bigger transcoded files',NULL);
CREATE UNIQUE INDEX IF NOT EXISTS "files_meta_id" ON "files_meta" (
	"id"	ASC
);
CREATE UNIQUE INDEX IF NOT EXISTS "menus_id" ON "menus" (
	"id"	ASC
);
CREATE UNIQUE INDEX IF NOT EXISTS "settings_id" ON "settings" (
	"id"	ASC
);
CREATE UNIQUE INDEX IF NOT EXISTS "files_history_id" ON "files_history" (
	"id"	ASC
);
CREATE UNIQUE INDEX IF NOT EXISTS "files_compound_dev_inode" ON "files" (
	"id"	ASC,
	"file_inode"	ASC,
	"file_dev"	ASC
);
CREATE TRIGGER FilesSetUpdated 
	AFTER UPDATE 
	ON files
	WHEN
		OLD.file_path <> NEW.file_path
		OR OLD.file_name <> NEW.file_name
		OR OLD.file_progress <> NEW.file_progress
		OR NEW.file_status <> 10
		OR OLD.file_size <> NEW.file_size
		OR OLD.file_ext <> NEW.file_ext
BEGIN 
	UPDATE files 
	SET updated = strftime('%s', 'now')
	WHERE id = NEW.id;
END;
COMMIT;
