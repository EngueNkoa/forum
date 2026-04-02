-- =============================================
-- Simple Community Forum — Database Schema
-- Run this in phpMyAdmin or MySQL CLI
-- =============================================

CREATE DATABASE IF NOT EXISTS forum_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE forum_db;

-- Users table
CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,          -- stores bcrypt hash
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- Threads table
CREATE TABLE threads (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NOT NULL,
    title      VARCHAR(200) NOT NULL,
    content    TEXT         NOT NULL,
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Replies table
CREATE TABLE replies (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    thread_id  INT  NOT NULL,
    user_id    INT  NOT NULL,
    content    TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES threads(id),
    FOREIGN KEY (user_id)   REFERENCES users(id)
);

-- Upvotes table (handles both thread votes and reply votes)
CREATE TABLE upvotes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT  NOT NULL,
    thread_id  INT  DEFAULT NULL,   -- set when voting on a thread
    reply_id   INT  DEFAULT NULL,   -- set when voting on a reply
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(id),
    FOREIGN KEY (thread_id) REFERENCES threads(id),
    FOREIGN KEY (reply_id)  REFERENCES replies(id),
    -- One vote per user per item (thread or reply)
    UNIQUE KEY one_vote (user_id, thread_id, reply_id)
);
