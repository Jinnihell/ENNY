# ESCR Digital Queue Management System (Node.js Version)

This is a Node.js/Express.js conversion of the original PHP-based ESCR DQMS.

## Features

- User authentication (login, signup)
- Role-based access (admin, staff, student)
- Queue management with ticket generation
- Staff dashboard for serving customers
- Public monitor display
- Transaction history
- Admin reports

## Requirements

- Node.js 18+
- MySQL 5.7+

## Installation

1. **Install dependencies:**
   ```bash
   npm install
   ```

2. **Configure database:**
   
   Edit `.env` file with your database credentials:
   ```
   DB_HOST=localhost
   DB_NAME=escr_dqms
   DB_USER=root
   DB_PASS=
   ```

3. **Run database migration:**
   ```bash
   npm run migrate
   ```

4. **Start the server:**
   ```bash
   npm start
   ```

5. **Access the application:**
   
   Open http://localhost:3000 in your browser.

## Default Admin Account

- Username: `admin`
- Password: `Admin@123`

## Project Structure

```
├── server.js           # Main Express server
├── migrate.js          # Database migration script
├── package.json        # Dependencies
├── .env               # Environment variables
├── src/
│   └── database/
│       └── config.js  # Database configuration
├── views/             # EJS templates
│   ├── login.ejs
│   ├── signup.ejs
│   ├── landing.ejs
│   ├── admin-selection.ejs
│   ├── staff-dashboard.ejs
│   ├── transaction-selection.ejs
│   ├── public-monitor.ejs
│   └── ...
└── public/
    └── images/        # Static images (place logo here)
```

## Routes

- `/` - Redirect to login
- `/login` - Login page
- `/signup` - Signup page
- `/landing` - Student landing page
- `/admin-selection` - Admin role selection
- `/window-selection` - Staff window selection
- `/staff-dashboard` - Staff dashboard
- `/active-queues` - Active queue management
- `/transaction-selection` - Student ticket generation
- `/monitor` - Public queue monitor
- `/api/monitor-data` - API for monitor data
- `/reports-settings` - Admin reports
- `/history` - Transaction history

## Queue Categories

- Window 1: Assessments (Prefix: A)
- Window 2: Enrollment (Prefix: E)
- Window 3: Payments (Prefix: P)
- Window 4: Other Concerns (Prefix: O)

## License

MIT
