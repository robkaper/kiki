# Full (MySQL/MariaDB) database scheme for Kiki CMS
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

insert into config (`key`, value) values( 'dbVersion', '0.1.30' );

drop table if exists facebook_user_perms;
create table facebook_user_perms (
  facebook_user_id bigint unsigned not null,
  perm_key varchar(255) not null,
  perm_value boolean not null default false,
  unique key (facebook_user_id, perm_key)
) default charset=utf8;

drop table if exists users;
create table users (
  id bigint unsigned not null auto_increment,
  primary key(id),
  object_id bigint unsigned default null,
  name varchar(255) not null default '',
  email varchar(255) default null,
  auth_token varchar(40) not null default '',
  mail_auth_token varchar(40) not null default '',
  admin boolean not null default false,
  unique key(email)
) default charset=utf8;

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

drop table if exists articles;
create table articles (
  id bigint unsigned not null auto_increment,
  primary key(id),
  object_id bigint unsigned default null,
  unique key(object_id),
  ip_addr varchar(15),
  title text not null,
  cname varchar(255) not null,
  unique key(section_id, cname),
  body text not null,
  featured boolean not null default false,
  hashtags varchar(255) not null,
  album_id int unsigned not null
) default charset=utf8;

drop table if exists tinyurl;
create table tinyurl (
  id bigint unsigned not null auto_increment,
  primary key(id),
  url varchar(255) not null,
  unique key(url)
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

drop table if exists storage;
create table storage (
  id int unsigned not null auto_increment,
  primary key(id),
  hash varchar(40) not null,
  original_name varchar(255) not null,
  extension varchar(255) not null,
  size int unsigned not null default 0
) default charset=utf8;

drop table if exists albums;
create table albums (
  id int unsigned not null auto_increment,
  primary key(id),
  title varchar(255) not null,
  system boolean default false
) default charset=utf8;

drop table if exists pictures;
create table pictures (
  id int unsigned not null auto_increment,
  primary key(id),
  title varchar(255) not null,
  description text not null,
  storage_id int unsigned not null
) default charset=utf8;

drop table if exists album_pictures;
create table album_pictures (
  album_id int unsigned not null,
  picture_id int unsigned not null,
  sortorder tinyint unsigned not null,
  key(album_id),
  key(picture_id),
  key(album_id,picture_id)
) default charset=utf8;

drop table if exists social_updates;
create table social_updates (
  id int unsigned not null auto_increment,
  primary key(id),
  object_id bigint unsigned not null,
  network varchar(255) not null,
  post text not null,
  response text not null
) default charset=utf8;

drop table if exists mail_queue;
create table mail_queue (
  id int unsigned not null auto_increment,
  primary key(id),
  msg_id varchar(255) not null,
  unique key(msg_id),
  ctime datetime not null,
  mtime datetime not null,
  lock_id varchar(255) default null,
  priority smallint unsigned not null default 0,
  sent boolean not null default false,
  subject text not null,
  `from` text not null,
  `to` text not null,
  headers text not null,
  body text not null
) default charset=utf8;

drop table if exists connections;
create table connections (
  id int unsigned not null auto_increment,
  primary key(id),
  user_id int unsigned not null,
  external_id bigint unsigned not null,
  service varchar(32) not null,
  ctime datetime not null,
  mtime datetime not null,
  token text default null,
  secret text default null,
  name varchar(255) default null,
  screenname varchar(255) default null,
  picture varchar(255) default null,
  key(external_id),
  key(user_id)
) default charset=utf8;

drop table if exists objects;
create table objects (
  object_id bigint unsigned not null auto_increment,
  primary key(object_id),
  type varchar(32) not null,
  ctime datetime not null,
  mtime datetime not null,
	section_id int unsigned not null default 0,
	user_id int unsigned not null default 0,
	visible boolean not null default false
) default charset=utf8;

drop table if exists events;
create table events (
  id bigint unsigned not null auto_increment,
  primary key(id),
  object_id bigint unsigned default null,
  unique key(object_id),
  start datetime not null,
  end datetime not null,
  title text not null,
  cname varchar(255) not null,
  unique key(cname),
  description text not null,
  location text not null,
  featured boolean not null default false,
  hashtags varchar(255) not null,
  album_id int unsigned not null
) default charset=utf8;

drop table if exists publications;
create table publications (
  publication_id int unsigned not null auto_increment,
  primary key(publication_id),
	ctime datetime not null,
  object_id bigint unsigned not null,
  connection_id bigint unsigned not null,
  external_id bigint unsigned not null,
  body text not null,
  response text not null,
  key(object_id)
) default charset=utf8;


drop table if exists likes;
create table likes (
  object_id bigint(20) unsigned not null,
  user_connection_id bigint(20) unsigned not null,
  ctime datetime not null,
  unique key object_connection(object_id, user_connection_id)
);
