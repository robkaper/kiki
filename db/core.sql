drop table if exists twitter_users;
create table twitter_users (
  id bigint unsigned not null,
  primary key(id),
  ctime datetime not null,
  mtime datetime not null,
  access_token text default null,
  secret text default null,
  name varchar(255) default null,
  screen_name varchar(255) default null,
  picture varchar(255) default null
);

drop table if exists facebook_users;
create table facebook_users (
  id bigint unsigned not null,
  primary key(id),
  ctime datetime not null,
  mtime datetime not null,
  access_token text default null,
  name varchar(255) default null
);

drop table if exists facebook_user_perms;
create table facebook_user_perms (
  facebook_user_id int unsigned not null,
  perm_key varchar(255) not null,
  perm_value boolean not null default false
);

drop table if exists users;
create table users (
  id bigint unsigned not null auto_increment,
  primary key(id),
  ctime datetime not null,
  mtime datetime not null,
  auth_token varchar(40) not null,
  facebook_user_id bigint unsigned default NULL,
  twitter_user_id bigint unsigned default NULL,
  unique key( facebook_user_id ),
  unique key( twitter_user_id )
);

drop table if exists comments;
create table comments (
  id bigint unsigned not null auto_increment,
  primary key(id),
  ctime datetime not null,
  mtime datetime not null,
  ip_addr varchar(15),
  object_id bigint unsigned not null,
  user_id bigint unsigned not null,
  body text not null
);

drop table if exists sections;
create table sections (
  id bigint unsigned not null auto_increment,
  primary key(id),
  title varchar(255) not null,
  base_uri varchar(255) not null,
  unique key(base_uri)
);

drop table if exists articles;
create table articles (
  id bigint unsigned not null auto_increment,
  primary key(id),
  ctime datetime not null,
  mtime datetime not null,
  ip_addr varchar(15),
  section_id bigint unsigned not null,
  user_id bigint unsigned not null,
  title text not null,
  cname varchar(255) not null,
  unique key(cname),
  body text not null,
  visible boolean not null default false,
  facebook_url varchar(255) default null,
  twitter_url varchar(255) default null
);

drop table if exists tinyurl;
create table tinyurl (
  id bigint unsigned not null auto_increment,
  primary key(id),
  url varchar(255) not null,
  unique key(url)
);

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
  sortorder tinyint unsigned not null
);

drop table if exists storage;
create table storage (
  id int unsigned not null auto_increment,
  primary key(id),
  hash varchar(40) not null,
  original_name varchar(255) not null,
  extension varchar(255) not null,
  size int unsigned not null default 0
);
