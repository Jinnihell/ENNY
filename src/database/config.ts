import 'dotenv/config';
import mysql, { Pool, PoolConnection, RowDataPacket, ResultSetHeader } from 'mysql2/promise';

export interface DatabaseConfig {
  host: string;
  user: string;
  password: string;
  database: string;
  port: number;
}

const config: DatabaseConfig = {
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || '',
  database: process.env.DB_NAME || 'escr_dqms',
  port: parseInt(process.env.DB_PORT || '3306', 10),
};

let pool: Pool | null = null;

export function getPool(): Pool {
  if (!pool) {
    const needsSSL = process.env.DB_SSL === 'true' || 
                     (process.env.DB_HOST && process.env.DB_HOST.includes('aivencloud.com'));

    const sslOptions = needsSSL ? {
      rejectUnauthorized: true
    } : undefined;

    pool = mysql.createPool({
      host: config.host,
      user: config.user,
      password: config.password,
      database: config.database,
      port: config.port,
      ssl: sslOptions,
      waitForConnections: true,
      connectionLimit: 10,
      queueLimit: 0,
    });
  }
  return pool;
}

export async function query<T extends RowDataPacket[]>(sql: string, params: any[] = []): Promise<T> {
  const pool = getPool();
  const [rows] = await pool.query<T>(sql, params);
  return rows;
}

export async function execute(sql: string, params: any[] = []): Promise<ResultSetHeader> {
  const pool = getPool();
  const [result] = await pool.execute<ResultSetHeader>(sql, params);
  return result;
}

export { config };
