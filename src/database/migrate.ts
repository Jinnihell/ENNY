import mysql, { Pool, RowDataPacket, ResultSetHeader } from 'mysql2/promise';
import dotenv from 'dotenv';

dotenv.config();

interface MigrationConfig {
  host: string;
  user: string;
  password: string;
  database: string;
}

const config: MigrationConfig = {
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || '',
  database: process.env.DB_NAME || 'escr_dqms',
};

async function createDatabase(): Promise<void> {
  const connection = await mysql.createConnection({
    host: config.host,
    user: config.user,
    password: config.password,
  });

  await connection.execute(`CREATE DATABASE IF NOT EXISTS ${config.database}`);
  console.log(`Database '${config.database}' created or already exists.`);
  await connection.end();
}

async function runMigrations(): Promise<void> {
  const pool = mysql.createPool({
    host: config.host,
    user: config.user,
    password: config.password,
    database: config.database,
    waitForConnections: true,
    connectionLimit: 10,
  });

  // Create users table
  await pool.execute(`
    CREATE TABLE IF NOT EXISTS users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      username VARCHAR(100) NOT NULL UNIQUE,
      email VARCHAR(150) NOT NULL UNIQUE,
      password VARCHAR(255) NOT NULL,
      role ENUM('admin', 'staff', 'student') DEFAULT 'student',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
  `);
  console.log('Users table created.');

  // Create queue table
  await pool.execute(`
    CREATE TABLE IF NOT EXISTS queue (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT,
      student_name VARCHAR(150) NOT NULL,
      student_id VARCHAR(50),
      blk_course VARCHAR(100),
      year VARCHAR(20),
      queue_number VARCHAR(20) NOT NULL UNIQUE,
      document_type VARCHAR(50) NOT NULL,
      status ENUM('Pending', 'Serving', 'Completed', 'Cancelled') DEFAULT 'Pending',
      window_id INT DEFAULT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )
  `);
  console.log('Queue table created.');

  // Add window_id column if it doesn't exist (for existing databases)
  try {
    await pool.execute("ALTER TABLE queue ADD COLUMN window_id INT DEFAULT NULL AFTER status");
    console.log('Added window_id column to queue table.');
  } catch (e: any) {
    if (e.code === 'ER_DUP_FIELDNAME') {
      console.log('window_id column already exists.');
    } else {
      console.log('window_id column addition skipped:', e.message);
    }
  }

  // Create transaction_history table
  await pool.execute(`
    CREATE TABLE IF NOT EXISTS transaction_history (
      id INT AUTO_INCREMENT PRIMARY KEY,
      student_name VARCHAR(150) NOT NULL,
      student_id VARCHAR(50),
      blk_course VARCHAR(100),
      year VARCHAR(20),
      transaction_type VARCHAR(50) NOT NULL,
      window_number INT NOT NULL,
      served_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
  `);
  console.log('Transaction history table created.');

  // Create queue_counters table
  await pool.execute(`
    CREATE TABLE IF NOT EXISTS queue_counters (
      id INT AUTO_INCREMENT PRIMARY KEY,
      category VARCHAR(50) UNIQUE NOT NULL,
      last_number INT DEFAULT 0,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
  `);
  console.log('Queue counters table created.');

  // Create settings table
  await pool.execute(`
    CREATE TABLE IF NOT EXISTS settings (
      id INT AUTO_INCREMENT PRIMARY KEY,
      language VARCHAR(20) DEFAULT 'en',
      theme VARCHAR(20) DEFAULT 'light',
      sound_enabled TINYINT(1) DEFAULT 1,
      notifications_enabled TINYINT(1) DEFAULT 1,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
  `);
  console.log('Settings table created.');

  // Insert default settings
  const [existingSettings]: [RowDataPacket[], any] = await pool.query(
    "SELECT id FROM settings LIMIT 1"
  );
  if (existingSettings.length === 0) {
    await pool.execute(
      "INSERT INTO settings (language, theme, sound_enabled, notifications_enabled) VALUES ('en', 'light', 1, 1)"
    );
    console.log('Default settings created.');
  }

  // Insert default admin user (password: Admin@123)
  const [existingAdmin] = await pool.query<RowDataPacket[]>(
    "SELECT id FROM users WHERE username = 'admin'"
  );

  if (existingAdmin.length === 0) {
    const bcrypt = require('bcryptjs');
    const hashedPassword = await bcrypt.hash('Admin@123', 10);
    
    await pool.execute(
      "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)",
      ['admin', 'admin@escr.edu', hashedPassword, 'admin']
    );
    console.log('Default admin user created (username: admin, password: Admin@123)');
  }

  await pool.end();
  console.log('All migrations completed successfully!');
}

async function main(): Promise<void> {
  try {
    console.log('Starting database migration...');
    await createDatabase();
    await runMigrations();
    console.log('Migration finished!');
    process.exit(0);
  } catch (error) {
    console.error('Migration failed:', error);
    process.exit(1);
  }
}

main();
