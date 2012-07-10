drop table twitter_users;
drop table facebook_users;

create table publications (
  publication_id int unsigned not null auto_increment,
  primary key(publication_id),
  object_id bigint unsigned not null,
  external_id bigint unsigned not null,
  service varchar(32) not null,
  body text not null,
  response text not null
);
