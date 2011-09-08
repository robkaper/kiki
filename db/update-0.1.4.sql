drop table if exists router_base_uris;
create table router_base_uris (
  id int unsigned not null auto_increment,
  primary key(id),
  base_uri varchar(255) not null,
  unique key(base_uri),
  type varchar(255) not null,
  instance_id int unsigned not null
);

insert into router_base_uris( base_uri, type, instance_id ) select base_uri, 'articles', id from sections;
# alter table sections drop base_uri;
