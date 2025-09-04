create table dbadmin_owners (
    id serial primary key,
    username varchar(150) not null,
    unique(username)
);

create table dbadmin_commands (
    id serial primary key,
    query text not null,
    title varchar(150) not null default '',
    category smallint not null,
    updated_at timestamp not null,
    owner_id integer not null,
    foreign key(owner_id) references dbadmin_owners(id)
);

create table dbadmin_tags (
    id serial primary key,
    title varchar(150) not null,
    owner_id integer not null,
    unique(title, owner_id),
    foreign key(owner_id) references dbadmin_owners(id)
);

create table dbadmin_command_tag (
    command_id integer not null,
    tag_id integer not null,
    foreign key(command_id) references dbadmin_commands(id),
    foreign key(tag_id) references dbadmin_tags(id),
    unique(command_id, tag_id)
);
