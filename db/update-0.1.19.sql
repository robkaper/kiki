alter table comments add in_reply_to_id int unsigned not null default 0;
update comments set in_reply_to_id=object_id, object_id=0;
insert into objects(type,ctime,mtime) select 'Comment', ctime, mtime from comments;
update comments set object_id=(select object_id from objects where ctime=comments.ctime and mtime=comments.mtime and type='Comment');
alter table comments drop mtime;
alter table comments drop ctime;
