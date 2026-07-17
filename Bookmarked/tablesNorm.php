<?php
require 'connection.php';

$tables = [
    "users" => "
        CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            birthdate DATE,
            profile_picture VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

    "genres" => "
        CREATE TABLE IF NOT EXISTS genres (
            genre_id INT AUTO_INCREMENT PRIMARY KEY,
            genre_name VARCHAR(50) UNIQUE NOT NULL
        )",

    "books" => "
        CREATE TABLE IF NOT EXISTS books (
            book_id INT AUTO_INCREMENT PRIMARY KEY,
            seller_id INT NOT NULL,
            title VARCHAR(150) NOT NULL,
            author VARCHAR(100) NOT NULL,
            synopsis TEXT,
            price DECIMAL(10,2) NOT NULL,
            cover_image VARCHAR(255),
            status ENUM('available','sold') DEFAULT 'available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (seller_id) REFERENCES users(user_id)
        )",

    "collections" => "
        CREATE TABLE IF NOT EXISTS collections (
            collection_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            collection_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        )",

    "book_genres" => "
        CREATE TABLE IF NOT EXISTS book_genres (
            book_id INT NOT NULL,
            genre_id INT NOT NULL,
            PRIMARY KEY (book_id, genre_id),
            FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
            FOREIGN KEY (genre_id) REFERENCES genres(genre_id)
        )",

    "wishlist" => "
        CREATE TABLE IF NOT EXISTS wishlist (
            user_id INT NOT NULL,
            book_id INT NOT NULL,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, book_id),
            FOREIGN KEY (user_id) REFERENCES users(user_id),
            FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
        )",

    "collection_books" => "
        CREATE TABLE IF NOT EXISTS collection_books (
            collection_id INT NOT NULL,
            book_id INT NOT NULL,
            PRIMARY KEY (collection_id, book_id),
            FOREIGN KEY (collection_id) REFERENCES collections(collection_id) ON DELETE CASCADE,
            FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
        )",

    "purchases" => "
        CREATE TABLE IF NOT EXISTS purchases (
            purchase_id INT AUTO_INCREMENT PRIMARY KEY,
            book_id INT NULL,
            buyer_id INT NOT NULL,
            seller_id INT NOT NULL,
            book_title VARCHAR(150) NOT NULL,
            author VARCHAR(100) NOT NULL,
            cover_image VARCHAR(255),
            price_paid DECIMAL(10,2) NOT NULL,
            purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE SET NULL,
            FOREIGN KEY (buyer_id) REFERENCES users(user_id),
            FOREIGN KEY (seller_id) REFERENCES users(user_id)
        )",

    "reviews" => "
        CREATE TABLE IF NOT EXISTS reviews (
            review_id INT AUTO_INCREMENT PRIMARY KEY,
            purchase_id INT NOT NULL UNIQUE,
            book_id INT NOT NULL,
            rating TINYINT NOT NULL CHECK (rating BETWEEN 0 AND 5),
            review_text TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (purchase_id) REFERENCES purchases(purchase_id),
            FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
        )",

    "remember_tokens" => "
        CREATE TABLE IF NOT EXISTS remember_tokens (
            selector VARCHAR(24) PRIMARY KEY,
            validator_hash CHAR(64) NOT NULL,
            user_id INT NOT NULL,
            expires_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )"
];

foreach ($tables as $name => $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "Table '$name' created successfully.<br>";
    } else {
        echo "Error creating table '$name': " . mysqli_error($conn) . "<br>";
    }
}
?>