# How to Install Node.js and Run the Application

## Step 1: Install Node.js

1. **Download Node.js:**
   - Go to: https://nodejs.org/
   - Download the **LTS (Long Term Support)** version (currently 18.x or 20.x)
   - Click the Windows Installer (.msi) file

2. **Run the Installer:**
   - Double-click the downloaded file
   - Click "Next" through the wizard
   - **Important:** Check the box that says "Add to PATH"
   - Click "Install" and wait for completion

3. **Verify Installation:**
   - Open Command Prompt (press Win + R, type `cmd`, press Enter)
   - Type: `node --version`
   - You should see something like `v18.x.x` or `v20.x.x`
   - Then type: `npm --version`
   - You should see a version number

---

## Step 2: Install Project Dependencies

1. **Open Command Prompt** in the project folder:
   - Press Win + R, type `cmd`, press Enter
   - Navigate to the project folder:
     ```
     cd c:\xampp\htdocs\start - Copy
     ```

2. **Install dependencies:**
   ```
   npm install
   ```
   This will download and install all required packages.

---

## Step 3: Start the Server

1. **Run the server:**
   ```
   npm start
   ```

2. **Open your browser** and go to:
   ```
   http://localhost:3000
   ```

---

## Troubleshooting

### If npm is not recognized:
- Make sure Node.js was installed with "Add to PATH" option
- Restart your computer after installation

### If port 3000 is in use:
- Change the port in `.env` file to something else like 3001

### Database connection errors:
- Make sure XAMPP MySQL is running
- Check your database credentials in `.env`

---

## Quick Video Tutorial

If you need a visual guide, search YouTube for:
- "How to install Node.js on Windows 10/11"
- "npm install express mysql2"

---

## Need Help?

If you encounter any errors, copy the error message and search for solutions online or ask for help.
