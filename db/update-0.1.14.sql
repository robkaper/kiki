create table events (
  id bigint unsigned not null auto_increment,
  primary key(id),
  object_id bigint unsigned default null,
  unique key(object_id),
  ctime datetime not null,
  mtime datetime not null,
  start datetime not null,
  end datetime not null,
  user_id bigint unsigned not null,
  title text not null,
  cname varchar(255) not null,
  unique key(cname),
  description text not null,
  location text not null,
  header_image bigint unsigned not null,
  featured boolean not null default false,
  visible boolean not null default false,
  facebook_url varchar(255) default null,
  twitter_url varchar(255) default null,
  hashtags varchar(255) not null,
  album_id int unsigned not null
  );
