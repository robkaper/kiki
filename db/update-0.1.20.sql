alter table articles drop key cname;
alter table articles add unique key(section_id,cname);
