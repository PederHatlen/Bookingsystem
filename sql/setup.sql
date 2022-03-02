CREATE DATABASE bookingsystem;

USE bookingsystem;

CREATE TABLE room (
    room_id INT not null PRIMARY KEY AUTO_INCREMENT,
    room_name VARCHAR(255) not null,
    schedule_from Datetime,
    schedule_to Datetime
);

CREATE TABLE users (
    user_id INT not null PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) not null unique,
    name VARCHAR(255) not null,
    surname VARCHAR(255) not null,
    password VARCHAR(255) not null,
    created_at DATETIME DEFAULT current_timestamp
);

CREATE TABLE booking (
    booking_id INT not null PRIMARY KEY AUTO_INCREMENT,
    room_id INT not null,
    user_id INT not null,
    color VARCHAR(7) not null,
    time_from DATETIME not Null,
    time_to DATETIME not Null,
    created_at Datetime DEFAULT current_timestamp,
    FOREIGN KEY (room_id) REFERENCES room(room_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

insert into room(room_name) VALUES("Grupperom 1");
insert into room(room_name) VALUES("Grupperom 2");
insert into room(room_name) VALUES("Samtalerom");