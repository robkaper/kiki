# Full (MySQL/MariaDB) database scheme for Kiki.
#
# Although a new installation can easily be updated from the status page
# (just reload it after installation), it is desired that this file is kept
# up-to-date with the latest scheme changes so that the latest revision can
# be installed right away.

drop table if exists config;
create table config (
  id bigint unsigned not null auto_increment,
  primary key(id),
  `key` varchar(255) default null,
  unique key(`key`),
  value varchar(255) default null
) default charset=utf8;

insert into config (`key`, value) values( 'dbVersion', '0.1.33' );

drop table if exists comments;
create table comments (
  id bigint unsigned not null auto_increment,
  primary key(id),
  object_id bigint unsigned not null,
  ip_addr varchar(15),
  in_reply_to_id bigint unsigned not null,
  user_connection_id int unsigned not null,
  external_id varchar(255) not null,
  body text not null
) default charset=utf8;

drop table if exists articles;
drop table if exists sections;
create table sections (
  id bigint unsigned not null auto_increment,
  primary key(id),
  base_uri varchar(255) not null,
  unique key(base_uri),
  title varchar(255) not null,
  type varchar(32) not null,
  key type_title(type, title)
) default charset=utf8;

create table articles (
  id bigint unsigned not null auto_increment,
  primary key(id),
  object_id bigint unsigned default null,
  unique key(object_id),
  section_id bigint unsigned not null references `sections`(`id`),
  cname varchar(255) not null,
  ip_addr varchar(15),
  title text not null,
  body text not null,
  featured boolean not null default false,
  hashtags varchar(255) not null,
  album_id int unsigned default null references `albums`(`id`)
) default charset=utf8;

drop table if exists menu_items;
create table menu_items (
  id bigint unsigned not null auto_increment,
  primary key(id),
  title varchar(255) not null,
  url varchar(255) not null,
  level tinyint unsigned not null,
  context varchar(255) not null,
  admin boolean default false,
  class varchar(255) default null,
  icon varchar(255) default null,
  sortorder tinyint unsigned not null,
  key level_context_sortorder(level,context,sortorder)
) default charset=utf8;
