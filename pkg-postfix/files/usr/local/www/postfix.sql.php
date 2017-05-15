<?php
/*
 * postfix.sql.php
 *
 * part of Unofficial packages for pfSense(R) softwate
 * Copyright (c) 2017 Marcello Coutinho
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


//sqlite postfix day database sqls array

$db_stm['sid_location']=<<<EOF
	CREATE TABLE IF NOT EXISTS "sid_date"(
		"id" INTEGER PRIMARY KEY,
		"sid" VARCHAR(11) NOT NULL,
		"db" TEXT NOT NULL
	);
	CREATE UNIQUE INDEX IF NOT EXISTS "sid_date_sid" on sid_date (sid ASC);
	CREATE INDEX IF NOT EXISTS "sid_date_db" on sid_date (db ASC);


EOF;


$db_stm['daily_db'] = <<<EOF
        CREATE TABLE IF NOT EXISTS "mail_from"(
        "id" INTEGER PRIMARY KEY,
        "sid" VARCHAR(11) NOT NULL,
    "client" TEXT NOT NULL,
    "msgid" TEXT,
        "fromm" TEXT,
    "size" INTEGER,
    "subject" TEXT,
    "date" TEXT NOT NULL,
    "server" TEXT,
    "helo" TEXT
);
        CREATE TABLE IF NOT EXISTS "mail_to"(
        "id" INTEGER PRIMARY KEY,
        "from_id" INTEGER NOT NULL,
    "too" TEXT,
    "status" INTEGER,
    "status_info" TEXT,
    "smtp" TEXT,
    "delay" TEXT,
    "relay" TEXT,
    "dsn" TEXT,
    "server" TEXT,
    "bounce" TEXT,
    FOREIGN KEY (status) REFERENCES mail_status(id),
    FOREIGN KEY (from_id) REFERENCES mail_from(id)
);


CREATE TABLE IF NOT EXISTS "mail_status"(
        "id" INTEGER PRIMARY KEY,
    "info" varchar(35) NOT NULL
);

CREATE TABLE IF NOT EXISTS "mail_noqueue"(
        "id" INTEGER PRIMARY KEY,
        "date" TEXT NOT NULL,
        "server" TEXT NOT NULL,
        "status" TEXT NOT NULL,
        "status_info" INTEGER NOT NULL,
        "fromm" TEXT NOT NULL,
        "too" TEXT NOT NULL,
        "helo" TEXT NOT NULL
);


CREATE TABLE IF NOT EXISTS "db_version"(
        "value" varchar(10),
        "info" TEXT
);

insert or ignore into db_version ('value') VALUES ('2.3.1');

CREATE UNIQUE INDEX IF NOT EXISTS "noqueue_unique" on mail_noqueue (date ASC, fromm ASC, too ASC);
CREATE INDEX IF NOT EXISTS "noqueue_helo" on mail_noqueue (helo ASC);
CREATE INDEX IF NOT EXISTS "noqueue_too" on mail_noqueue (too ASC);
CREATE INDEX IF NOT EXISTS "noqueue_fromm" on mail_noqueue (fromm ASC);
CREATE INDEX IF NOT EXISTS "noqueue_info" on mail_noqueue (status_info ASC);
CREATE INDEX IF NOT EXISTS "noqueue_status" on mail_noqueue (status ASC);
CREATE INDEX IF NOT EXISTS "noqueue_server" on mail_noqueue (server ASC);
CREATE INDEX IF NOT EXISTS "noqueue_date" on mail_noqueue (date ASC);

CREATE UNIQUE INDEX IF NOT EXISTS "status_info" on mail_status (info ASC);

CREATE UNIQUE INDEX IF NOT EXISTS "from_sid_server" on mail_from (sid ASC,server ASC);
CREATE INDEX IF NOT EXISTS "from_client" on mail_from (client ASC);
CREATE INDEX IF NOT EXISTS "from_helo" on mail_from (helo ASC);
CREATE INDEX IF NOT EXISTS "from_server" on mail_from (server ASC);
CREATE INDEX IF NOT EXISTS "from_subject" on mail_from (subject ASC);
CREATE INDEX IF NOT EXISTS "from_msgid" on mail_from (msgid ASC);
CREATE INDEX IF NOT EXISTS "from_fromm" on mail_from (fromm ASC);
CREATE INDEX IF NOT EXISTS "from_date" on mail_from (date ASC);

CREATE UNIQUE INDEX IF NOT EXISTS "mail_to_unique" on mail_to (from_id ASC, too ASC);
CREATE INDEX IF NOT EXISTS "to_bounce" on mail_to (bounce ASC);
CREATE INDEX IF NOT EXISTS "to_relay" on mail_to (relay ASC);
CREATE INDEX IF NOT EXISTS "to_smtp" on mail_to (smtp ASC);
CREATE INDEX IF NOT EXISTS "to_info" on mail_to (status_info ASC);
CREATE INDEX IF NOT EXISTS "to_status" on mail_to (status ASC);
CREATE INDEX IF NOT EXISTS "to_too" on mail_to (too ASC);

insert or ignore into mail_status (info) values ('spam');
insert or ignore into mail_status (info) values ('bounced');
insert or ignore into mail_status (info) values ('deferred');
insert or ignore into mail_status (info) values ('reject');
insert or ignore into mail_status (info) values ('sent');
insert or ignore into mail_status (info) values ('hold');
insert or ignore into mail_status (info) values ('incoming');

EOF;

?>
