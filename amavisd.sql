CREATE TABLE users (
  id         int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  priority   integer      NOT NULL DEFAULT '7',
  policy_id  integer unsigned NOT NULL DEFAULT '1',
  email      varbinary(255) NOT NULL UNIQUE,
  fullname   varchar(255) DEFAULT NULL,
  local      char(1)
);

CREATE TABLE mailaddr (
  id         int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  priority   integer      NOT NULL DEFAULT '7',
  email      varbinary(255) NOT NULL UNIQUE
);

CREATE TABLE wblist (
  rid        integer unsigned NOT NULL,
  sid        integer unsigned NOT NULL,
  wb         varchar(10)  NOT NULL,
  PRIMARY KEY (rid,sid)
);

CREATE TABLE policy (
  id  int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  policy_name      varchar(32),
  virus_lover           char(1) default NULL,
  spam_lover            char(1) default NULL,
  unchecked_lover       char(1) default NULL,
  banned_files_lover    char(1) default NULL,
  bad_header_lover      char(1) default NULL,
  bypass_virus_checks   char(1) default NULL,
  bypass_spam_checks    char(1) default NULL,
  bypass_banned_checks  char(1) default NULL,
  bypass_header_checks  char(1) default NULL,
  virus_quarantine_to      varchar(64) default NULL,
  spam_quarantine_to       varchar(64) default NULL,
  banned_quarantine_to     varchar(64) default NULL,
  unchecked_quarantine_to  varchar(64) default NULL,
  bad_header_quarantine_to varchar(64) default NULL,
  clean_quarantine_to      varchar(64) default NULL,
  archive_quarantine_to    varchar(64) default NULL,
  spam_tag_level  float default NULL,
  spam_tag2_level float default NULL,
  spam_tag3_level float default NULL,
  spam_kill_level float default NULL,
  spam_dsn_cutoff_level        float default NULL,
  spam_quarantine_cutoff_level float default NULL,
  addr_extension_virus      varchar(64) default NULL,
  addr_extension_spam       varchar(64) default NULL,
  addr_extension_banned     varchar(64) default NULL,
  addr_extension_bad_header varchar(64) default NULL,
  warnvirusrecip      char(1)     default NULL,
  warnbannedrecip     char(1)     default NULL,
  warnbadhrecip       char(1)     default NULL,
  newvirus_admin      varchar(64) default NULL,
  virus_admin         varchar(64) default NULL,
  banned_admin        varchar(64) default NULL,
  bad_header_admin    varchar(64) default NULL,
  spam_admin          varchar(64) default NULL,
  spam_subject_tag    varchar(64) default NULL,
  spam_subject_tag2   varchar(64) default NULL,
  spam_subject_tag3   varchar(64) default NULL,
  message_size_limit  integer     default NULL,
  banned_rulenames    varchar(64) default NULL,
  disclaimer_options  varchar(64) default NULL,
  forward_method      varchar(64) default NULL,
  sa_userconf         varchar(64) default NULL,
  sa_username         varchar(64) default NULL
);

CREATE TABLE maddr (
  partition_tag integer      DEFAULT 0,
  id         bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email      varbinary(255)  NOT NULL,
  domain     varchar(255)    NOT NULL,
  CONSTRAINT part_email UNIQUE (partition_tag,email)
) ENGINE=InnoDB;

CREATE TABLE msgs (
  partition_tag integer     DEFAULT 0,
  mail_id     varbinary(16) NOT NULL,
  secret_id   varbinary(16) DEFAULT '',
  am_id       varchar(20)   NOT NULL,
  time_num    integer unsigned NOT NULL,
  time_iso    char(16)      NOT NULL,
  sid         bigint unsigned NOT NULL,
  policy      varchar(255)  DEFAULT '',
  client_addr varchar(255)  DEFAULT '',
  size        integer unsigned NOT NULL,
  originating char(1) DEFAULT ' ' NOT NULL,
  content     char(1),
  quar_type  char(1),
  quar_loc   varbinary(255) DEFAULT '',
  dsn_sent   char(1),
  spam_level float,
  message_id varchar(255)  DEFAULT '',
  from_addr  varchar(255)  CHARACTER SET utf8 COLLATE utf8_bin  DEFAULT '',
  subject    varchar(255)  CHARACTER SET utf8 COLLATE utf8_bin  DEFAULT '',
  host       varchar(255)  NOT NULL,
  PRIMARY KEY (partition_tag,mail_id)
) ENGINE=InnoDB;

CREATE TABLE msgrcpt (
  partition_tag integer    DEFAULT 0,
  mail_id    varbinary(16) NOT NULL,
  rseqnum    integer  DEFAULT 0   NOT NULL,
  rid        bigint unsigned NOT NULL,
  is_local   char(1)  DEFAULT ' ' NOT NULL,
  content    char(1)  DEFAULT ' ' NOT NULL,
  ds         char(1)  NOT NULL,
  rs         char(1)  NOT NULL,
  bl         char(1)  DEFAULT ' ',
  wl         char(1)  DEFAULT ' ',
  bspam_level float,
  smtp_resp  varchar(255)  DEFAULT '',
  PRIMARY KEY (partition_tag,mail_id,rseqnum)
) ENGINE=InnoDB;

CREATE TABLE quarantine (
  partition_tag integer    DEFAULT 0,
  mail_id    varbinary(16) NOT NULL,
  chunk_ind  integer unsigned NOT NULL,
  mail_text  blob          NOT NULL,
  PRIMARY KEY (partition_tag,mail_id,chunk_ind)
) ENGINE=InnoDB;

CREATE INDEX msgs_idx_sid      ON msgs (sid);
CREATE INDEX msgs_idx_mess_id  ON msgs (message_id);
CREATE INDEX msgs_idx_time_num ON msgs (time_num);
CREATE INDEX msgrcpt_idx_mail_id  ON msgrcpt (mail_id);
CREATE INDEX msgrcpt_idx_rid      ON msgrcpt (rid);
