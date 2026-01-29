  CREATE DATABASE IF NOT EXISTS `voting_system1` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  USE `voting_system1`;

  CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );

  CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) DEFAULT NULL,
    voted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );

  CREATE TABLE IF NOT EXISTS elections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATETIME DEFAULT NULL,
    end_date DATETIME DEFAULT NULL,
    status ENUM('draft','running','closed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );

  CREATE TABLE IF NOT EXISTS partylists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    abbreviation VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE
  );

  CREATE TABLE IF NOT EXISTS candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partylist_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(100) DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partylist_id) REFERENCES partylists(id) ON DELETE CASCADE
  );

  CREATE TABLE IF NOT EXISTS votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT NOT NULL,
    student_id VARCHAR(50) NOT NULL,
    choices TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ux_election_student (election_id, student_id),
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE
  );

  INSERT IGNORE INTO admins (username, password_hash) VALUES ('admin', '$2b$12$C8A3yLr1wk.pXdEIpraKeeOZ8v/hhZJrkpHqEHjOkPuDIZHz.JPkS');
