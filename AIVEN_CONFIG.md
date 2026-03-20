# Aiven MySQL Connection Settings

## Where to Get Connection Details

1. **Login to Aiven Console:** https://console.aiven.io/
2. **Go to your MySQL service**
3. **Click "Connection Info"** tab

## Required Settings for .env

```
DB_HOST=your-aiven-host.aivencloud.com
DB_NAME=your_database_name
DB_USER=your_username
DB_PASSWORD=your_password
DB_PORT=your_port (usually 13092 for SSL)
```

## Important: Enable SSL

Aiven requires SSL connections. Make sure your database config has SSL enabled.

### Option 1: Update src/database/config.js

```javascript
const pool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || '',
  database: process.env.DB_NAME || 'escr_dqms',
  port: process.env.DB_PORT || 3306,
  ssl: {
    rejectUnauthorized: true
  },
  waitForConnections: true,
  connectionLimit: 10,
});
```

### Option 2: Environment Variables for Vercel

Add these to Vercel:
```
DB_HOST=your-aiven-host.aivencloud.com
DB_NAME=escr_dqms
DB_USER=avnadmin
DB_PASS=your_password
DB_PORT=13092
```

## Quick Test

Test your connection locally:
```javascript
// Add this to server.js temporarily to test
const mysql = require('mysql2/promise');
async function test() {
  const conn = await mysql.createConnection({
    host: 'YOUR_AIVEN_HOST',
    user: 'avnadmin',
    password: 'YOUR_PASSWORD',
    database: 'escr_dqms',
    port: 13092,
    ssl: { rejectUnauthorized: true }
  });
  console.log('Connected!');
  await conn.end();
}
test();
```

## Firewall Setup

In Aiven Console:
1. Go to **Advanced Settings** → **IP Whitelist**
2. Add Vercel's IP ranges or use `0.0.0.0/0` for development

## Need to Import Your Database?

1. Export from phpMyAdmin (XAMPP):
   - Export `escr_dqms` database as SQL file

2. Import to Aiven:
   - Use MySQL Workbench or command line:
   ```bash
   mysql -h your-aiven-host.aivencloud.com -P 13092 -u avnadmin -p escr_dqms < backup.sql
   ```
