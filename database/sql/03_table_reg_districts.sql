create table reg_districts
(
    id         integer      not null
        primary key,
    regency_id integer      not null
        constraint fk_regency
            references reg_regencies,
    name       varchar(255) not null
);
