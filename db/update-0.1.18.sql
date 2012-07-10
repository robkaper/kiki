drop table twitter_users;
drop table facebook_users;

create table publications (
  publication_id int unsigned not null auto_increment,
  primary key(publication_id),
  ctime datetime not null,
  object_id bigint unsigned not null,
  connection_id bigint unsigned not null,
  external_id bigint unsigned not null,
  body text not null,
  response text not null
);

insert into publications(ctime,object_id,connection_id,external_id, body, response)
select a.ctime, a.object_id, uc.external_id, substring_index(a.facebook_url, '/', -1), '', ''
from articles a, users u, users_connections uc
where a.user_id=u.id and u.id=uc.user_id and uc.service='User_Facebook' and facebook_url!='';

insert into publications(ctime,object_id,connection_id,external_id, body, response)
select a.ctime, a.object_id, uc.external_id, substring_index(a.twitter_url, '/', -1), '', ''
from articles a, users u, users_connections uc
where a.user_id=u.id and u.id=uc.user_id and uc.service='User_Twitter' and twitter_url!='';

alter table articles drop facebook_url;
alter table articles drop twitter_url;

alter table events drop facebook_url;
alter table events drop twitter_url;

rename table users_connections to connections;

# select p.*,c.id FROM publications p, connections c where p.connection_id=c.external_id