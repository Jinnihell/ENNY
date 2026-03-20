# Vercel Deployment Guide

## ⚠️ Important: Database Requirement

**Vercel does NOT provide MySQL database.** To deploy on Vercel, you need an external MySQL database:

### Recommended Options:
1. **PlanetScale** (Free tier available) - https://planetscale.com
2. **ClearDB** (Heroku add-on) - https://www.cleardb.com
3. **A2 Hosting** - https://a2hosting.com
4. **Remote MySQL** from your existing XAMPP/hosting

---

## 🚀 Deployment Steps

### Step 1: Prepare Your Database

If using external MySQL:
1. Create a new MySQL database
2. Import your `escr_dqms.sql` file
3. Get the connection details:
   - Host
   - Database name
   - Username
   - Password

### Step 2: Deploy to Vercel

1. **Push to GitHub:**
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   # Create repository on GitHub, then:
   git remote add origin https://github.com/yourusername/escr-dqms.git
   git push -u origin main
   ```

2. **Import to Vercel:**
   - Go to https://vercel.com
   - Click "Add New..." → "Project"
   - Import your GitHub repository

3. **Configure Environment Variables:**
   In Vercel dashboard → Settings → Environment Variables:
   ```
   DB_HOST=your_mysql_host
   DB_NAME=escr_dqms
   DB_USER=your_username
   DB_PASS=your_password
   SESSION_SECRET=random_string_here
   ```

4. **Deploy:**
   - Click "Deploy"
   - Wait for build to complete

---

## 🔧 Alternative: Keep Using Local Database

If you want to keep using XAMPP MySQL:
- Keep the app running locally with `npm start`
- Use **ngrok** to create a public URL:
  ```bash
  ngrok http 3000
  ```
- Share the ngrok URL with others

---

## 📊 Current Vercel Compatibility

| Feature | Status | Notes |
|---------|--------|-------|
| Express.js Server | ✅ Compatible | Works with @vercel/node |
| EJS Templates | ✅ Compatible | Rendered server-side |
| MySQL Database | ⚠️ External Needed | Vercel doesn't provide MySQL |
| Static Files | ✅ Compatible | CSS, images work |
| Sessions | ⚠️ Limited | Use external session store for scale |

---

## 🔒 Security Notes for Production

1. **Change SESSION_SECRET** to a strong random string
2. **Use HTTPS** (automatic on Vercel)
3. **Configure CORS** if needed
4. **Use environment variables** for all secrets

---

## ❓ Troubleshooting

### "Connection Refused" Error
→ Database host is wrong or not accessible. Make sure your MySQL allows external connections.

### "Module Not Found" Error
→ Run `npm install` locally first, then push to GitHub.

### "Port Not Defined" Error
→ Vercel sets PORT automatically. Code uses `process.env.PORT || 3000`.
