CREATE DATABASE bookingsystem;

USE bookingsystem;

CREATE TABLE room (
    room_id INT not null PRIMARY KEY AUTO_INCREMENT,
    created_at DATETIME DEFAULT current_timestamp
);

CREATE TABLE users (
    user_id INT not null PRIMARY KEY AUTO_INCREMENT,
    created_at DATETIME not Null
);

CREATE TABLE booking (
    booking_id INT not null PRIMARY KEY AUTO_INCREMENT,
    room_id INT not null,
    user_id INT not null,
    time_from DATETIME not Null,
    time_to DATETIME not Null,
    FOREIGN KEY (room_id) REFERENCES room(room_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);