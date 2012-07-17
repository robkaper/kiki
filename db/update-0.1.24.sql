create table likes (
  object_id bigint(20) unsigned not null,
  user_connection_id bigint(20) unsigned not null,
  unique key object_connection(object_id, user_connection_id)
);
