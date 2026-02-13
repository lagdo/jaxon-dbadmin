create table dbadmin_profiles (
    id integer not null primary key autoincrement,
    title varchar(50) not null,
    description varchar(500) not null default '',
    user_id integer not null,
    foreign key(user_id) references dbadmin_users(id)
);

create table dbadmin_preferences (
    id integer not null primary key autoincrement,
    category smallint not null,
    content json not null,
    last_update timestamp not null,
    profile_id integer not null,
    foreign key(profile_id) references dbadmin_profiles(id)
);
create unique index dbadmin_preferences_unique on dbadmin_preferences(id, category, profile_id);
