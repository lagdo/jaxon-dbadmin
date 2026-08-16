create table dbadmin_users (
    id serial primary key,
    username varchar(150) not null,
    unique(username)
);

create table dbadmin_executions (
    id serial primary key,
    session_id uuid default null,
    request_id uuid default null,
    driver varchar(30) not null,
    options json not null,
    query text not null,
    query_hash char(64) not null,
    completed boolean not null default true,
    error_code varchar(10) default null,
    error_message text default null,
    rows_affected integer default null,
    rows_returned integer default null,
    client_ip varchar(64) not null default '',
    user_agent varchar(512) not null default '',
    started_at timestamp not null,
    duration bigint not null,
    category smallint not null,
    last_update timestamp not null,
    user_id integer not null,
    foreign key(user_id) references dbadmin_users(id)
);

create table dbadmin_commands (
    id serial primary key,
    title varchar(150) not null default '',
    query text not null,
    driver varchar(30) not null,
    last_update timestamp not null,
    user_id integer not null,
    foreign key(user_id) references dbadmin_users(id)
);

create table dbadmin_tags (
    id serial primary key,
    title varchar(150) not null,
    user_id integer not null,
    unique(title, user_id),
    foreign key(user_id) references dbadmin_users(id)
);

create table dbadmin_command_tag (
    command_id integer not null,
    tag_id integer not null,
    foreign key(command_id) references dbadmin_commands(id),
    foreign key(tag_id) references dbadmin_tags(id),
    unique(command_id, tag_id)
);
