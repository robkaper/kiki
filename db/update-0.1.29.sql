alter table objects add section_id int unsigned not null default 0;
alter table objects add user_id int unsigned not null default 0;
alter table objects add visible boolean not null default false;
alter table social_updates add object_id bigint unsigned not null;
UPDATE objects o, articles a SET o.ctime=a.ctime, o.mtime=a.mtime, o.user_id = a.user_id, o.section_id=a.section_id, o.visible=a.visible WHERE o.object_id=a.object_id;
UPDATE objects o, events e SET o.ctime=e.ctime, o.mtime=e.mtime, o.user_id = e.user_id, o.visible=e.visible WHERE o.object_id=e.object_id;
UPDATE objects o, comments c SET o.user_id = c.user_id, o.visible=1 WHERE o.object_id=c.object_id;
UPDATE objects SET section_id=2,visible=1,user_id=1 WHERE TYPE = 'socialupdate';
UPDATE objects o, users u SET o.ctime=u.ctime, o.mtime=u.mtime, visible=1 WHERE o.object_id=u.object_id;
UPDATE objects o, publications p SET o.ctime=p.ctime, visible=1 WHERE o.object_id=p.object_id;
alter table articles drop ctime;
alter table articles drop mtime;
alter table articles drop user_id;
alter table articles drop key section_id;
alter table articles add key(cname);
alter table articles drop section_id;
alter table articles drop visible;
alter table events drop ctime;
alter table events drop mtime;
alter table events drop user_id;
alter table events drop visible;
alter table comments drop user_id;
alter table social_updates drop ctime;
alter table users drop ctime;
alter table users drop mtime;
alter table publications drop ctime;
