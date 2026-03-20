import express, { Request, Response } from 'express';
import session from 'express-session';
import path from 'path';
import mysql from 'mysql2/promise';
import dotenv from 'dotenv';

dotenv.config();

// Database configuration
const pool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || '',
  database: process.env.DB_NAME || 'escr_dqms',
  waitForConnections: true,
  connectionLimit: 10,
});

const app = express();

// 1. EJS Setup
app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, '../../views'));

// 2. Static Files
app.use(express.static(path.join(__dirname, '../../public')));
app.use(express.static(path.join(__dirname, '../../')));

// 3. Body Parser
app.use(express.urlencoded({ extended: true }));
app.use(express.json());

// 4. Session
app.use(session({
  secret: process.env.SESSION_SECRET || 'escr-secret-key',
  resave: false,
  saveUninitialized: false,
  cookie: { httpOnly: true, maxAge: 1000 * 60 * 30 }
}));

// Helper functions
function requireAuth(req: Request, res: Response, next: Function) {
  if (!(req.session as any).user_id) {
    return res.redirect('/login?error=please_login');
  }
  next();
}

function requireRole(...roles: string[]) {
  return (req: Request, res: Response, next: Function) => {
    const sessionRole = (req.session as any).role;
    if (!(req.session as any).user_id || !roles.includes(sessionRole)) {
      return res.redirect('/login?error=unauthorized');
    }
    next();
  };
}

// ==================== ROUTES ====================

// Home - redirect to login
app.get('/', (req: Request, res: Response) => {
  res.redirect('/login');
});

// Login Page
app.get('/login', (req: Request, res: Response) => {
  if ((req.session as any).user_id) {
    const role = (req.session as any).role;
    if (role === 'admin' || role === 'staff') {
      return res.redirect('/admin-selection');
    }
    return res.redirect('/landing');
  }
  res.render('login', { error: req.query.error, message: req.query.message });
});

// Login POST
app.post('/login', async (req: Request, res: Response) => {
  const { username, password } = req.body;
  
  try {
    const [rows]: any = await pool.query(
      "SELECT id, username, password, role FROM users WHERE username = ? OR email = ?",
      [username, username]
    );
    
    if (rows.length === 0) {
      return res.render('login', { error: 'Account not found.' });
    }
    
    const user = rows[0];
    const bcrypt = require('bcryptjs');
    
    if (!bcrypt.compareSync(password, user.password)) {
      return res.render('login', { error: 'Invalid password.' });
    }
    
    (req.session as any).user_id = user.id;
    (req.session as any).username = user.username;
    (req.session as any).role = user.role;
    
    if (user.role === 'admin' || user.role === 'staff') {
      return res.redirect('/admin-selection');
    }
    return res.redirect('/landing');
  } catch (err: any) {
    console.error(err);
    res.render('login', { error: 'An error occurred. Please try again.' });
  }
});

// Signup Page
app.get('/signup', (req: Request, res: Response) => {
  if ((req.session as any).user_id) {
    return res.redirect('/landing');
  }
  res.render('signup', { message: req.query.message });
});

// Signup POST
app.post('/signup', async (req: Request, res: Response) => {
  const { email, username, password } = req.body;
  
  if (password.length < 8 || !/[A-Z]/.test(password) || !/[a-z]/.test(password) || !/[0-9]/.test(password)) {
    return res.render('signup', { message: 'Password must be at least 8 characters with uppercase, lowercase, and number.' });
  }
  
  try {
    const [existing]: any = await pool.query(
      "SELECT id FROM users WHERE email = ? OR username = ?",
      [email, username]
    );
    
    if (existing.length > 0) {
      return res.render('signup', { message: 'Username or Email already exists!' });
    }
    
    const bcrypt = require('bcryptjs');
    const hashedPassword = bcrypt.hashSync(password, 10);
    
    await pool.query(
      "INSERT INTO users (email, username, password, role) VALUES (?, ?, ?, 'student')",
      [email, username, hashedPassword]
    );
    
    res.redirect('/login?message=registered');
  } catch (err: any) {
    console.error(err);
    res.render('signup', { message: 'An error occurred. Please try again.' });
  }
});

// Forgot Password Page
app.get('/forgot-password', (req: Request, res: Response) => {
  res.render('forgot-password', { message: req.query.message });
});

// Forgot Password - Handle form submission
app.post('/forgot-password', async (req: Request, res: Response) => {
  const { email } = req.body;
  
  if (!email) {
    return res.render('forgot-password', { error: 'Please enter your email address' });
  }
  
  try {
    // Check if email exists in database
    const [users] = await pool.query(
      'SELECT id, username FROM users WHERE email = ?',
      [email]
    );
    
    if ((users as any[]).length === 0) {
      // Don't reveal if email exists or not for security
      return res.render('forgot-password', { message: 'sent' });
    }
    
    // Generate a simple reset token
    const resetToken = Buffer.from(`${Date.now()}-${(users as any[])[0].id}`).toString('base64');
    
    console.log(`Password reset requested for ${email}. Token: ${resetToken}`);
    
    return res.render('forgot-password', { message: 'sent' });
    
  } catch (error) {
    console.error('Forgot password error:', error);
    return res.render('forgot-password', { error: 'An error occurred. Please try again.' });
  }
});
    
// Logout
app.get('/logout', (req: Request, res: Response) => {
  req.session.destroy(() => {
    res.redirect('/login?message=logged_out');
  });
});

// Track Queue Page (for students to track their position)
app.get('/track-queue', (req: Request, res: Response) => {
  res.render('track-queue');
});

// API: Get queue status for a specific ticket
app.get('/api/queue-status', async (req: Request, res: Response) => {
  const ticket = req.query.ticket as string;
  
  if (!ticket) {
    return res.json({ found: false });
  }
  
  try {
    // Get queue without joining windows table (since it may not exist)
    const [rows] = await pool.query(
      `SELECT queue_number, status, window_id 
       FROM queue 
       WHERE queue_number = ? AND DATE(created_at) = CURDATE() 
       ORDER BY id DESC LIMIT 1`,
      [ticket]
    );
    
    const queue = (rows as any[])[0];
    
    if (!queue) {
      return res.json({ found: false });
    }
    
    // Get position in queue
    const [positionRows] = await pool.query(
      `SELECT COUNT(*) as pos FROM queue 
       WHERE status = 'waiting' AND id < 
       (SELECT id FROM queue WHERE queue_number = ? AND DATE(created_at) = CURDATE() LIMIT 1)`,
      [ticket]
    );
    
    const position = (positionRows as any[])[0].pos + 1;
    
    // Get window name from window_id if available
    let windowName = null;
    if (queue.window_id) {
      try {
        const [windowRows] = await pool.query(
          `SELECT window_name FROM windows WHERE id = ?`,
          [queue.window_id]
        );
        if ((windowRows as any[]).length > 0) {
          windowName = (windowRows as any[])[0].window_name;
        }
      } catch (e) {
        // windows table doesn't exist, use window_id as fallback
        windowName = String(queue.window_id);
      }
    }
    
    res.json({
      found: true,
      ticket: ticket,
      status: queue.status,
      position: position,
      window: windowName
    });
    
  } catch (error) {
    console.error('Queue status error:', error);
    res.json({ found: false });
  }
});

// Landing Page
app.get('/landing', requireAuth, (req: Request, res: Response) => {
  const role = (req.session as any).role;
  if (role === 'admin' || role === 'staff') {
    return res.redirect('/admin-selection');
  }
  res.render('landing');
});

// Admin Selection
app.get('/admin-selection', requireAuth, requireRole('admin', 'staff'), (req: Request, res: Response) => {
  res.render('admin-selection');
});

// Window Selection
app.get('/window-selection', requireAuth, requireRole('admin', 'staff'), (req: Request, res: Response) => {
  const windowId = (req.session as any).active_window || 1;
  res.render('window-selection', { windowId });
});

// Set Window
app.post('/window-selection', requireAuth, requireRole('admin', 'staff'), (req: Request, res: Response) => {
  (req.session as any).active_window = parseInt(req.body.window) || 1;
  (req.session as any).additional_types = [];
  res.redirect('/staff-dashboard');
});

// Staff Dashboard
app.get('/staff-dashboard', requireAuth, requireRole('admin', 'staff'), async (req: Request, res: Response) => {
  const windowId = (req.session as any).active_window || 1;
  const typeMap: { [key: number]: string } = { 1: 'Assessments', 2: 'Enrollment', 3: 'Payments', 4: 'Other Concerns' };
  const defaultType = typeMap[windowId] || 'Assessments';
  
  // Get additional types the staff wants to serve
  const additionalTypes = (req.session as any).additional_types || [];
  const activeTypes = [defaultType, ...additionalTypes];
  
  try {
    // Build placeholders for the query
    const placeholders = activeTypes.map(() => '?').join(',');
    
    // Fetch the student currently being served - filtered by active transaction types
    const [serving]: any = await pool.query(
      `SELECT id, queue_number, student_name, student_id, blk_course, year, document_type FROM queue WHERE status = 'Serving' AND document_type IN (${placeholders}) LIMIT 1`,
      activeTypes
    );
    
    // Fetch waiting students count - filtered by active transaction types
    const [waitingResult]: any = await pool.query(
      `SELECT COUNT(*) as count FROM queue WHERE status = 'Pending' AND document_type IN (${placeholders})`,
      activeTypes
    );
    const waitingCount = waitingResult[0]?.count || 0;
    
    // Fetch completed today count - filtered by window
    const today = new Date().toISOString().slice(0, 10);
    const [completedResult]: any = await pool.query(
      "SELECT COUNT(*) as count FROM transaction_history WHERE DATE(served_at) = ? AND window_number = ?",
      [today, windowId]
    );
    const completedCount = completedResult[0]?.count || 0;
    
    // Fetch waiting list - filtered by active transaction types
    const [waitingList]: any = await pool.query(
      `SELECT id, queue_number, student_name, student_id, blk_course, year, document_type FROM queue WHERE status = 'Pending' AND document_type IN (${placeholders}) ORDER BY created_at ASC LIMIT 5`,
      activeTypes
    );
    
    // Fetch active windows (windows with recent activity)
    let activeWindows: number[] = [];
    try {
      const [activeWindowsResult]: any = await pool.query(
        "SELECT DISTINCT window_number FROM transaction_history WHERE DATE(served_at) = ? ORDER BY window_number",
        [today]
      );
      activeWindows = activeWindowsResult.map((w: any) => w.window_number);
    } catch (e) {
      // transaction_history table doesn't exist, use default windows
      activeWindows = [1, 2, 3, 4];
    }
    
    res.render('staff-dashboard', {
      windowId,
      defaultType,
      activeTypes,
      serving: serving[0] || null,
      waitingCount,
      completedCount,
      waitingList,
      activeWindows
    });
  } catch (err: any) {
    console.error(err);
    res.redirect('/admin-selection');
  }
});

// Toggle additional transaction types
app.get('/toggle-type', requireAuth, requireRole('admin', 'staff'), (req: Request, res: Response) => {
  const toggleType = req.query.type as string;
  const validTypes = ['Assessments', 'Enrollment', 'Payments', 'Other Concerns'];
  
  if (validTypes.includes(toggleType)) {
    let additionalTypes = (req.session as any).additional_types || [];
    
    // Toggle: if exists, remove; if not, add
    if (additionalTypes.includes(toggleType)) {
      additionalTypes = additionalTypes.filter((t: string) => t !== toggleType);
    } else {
      additionalTypes.push(toggleType);
    }
    
    (req.session as any).additional_types = additionalTypes;
  }
  
  res.redirect('/staff-dashboard');
});

// Process Next for Specific Type - MUST come before generic /process-next
app.get('/process-next/:type', requireAuth, requireRole('admin', 'staff'), async (req: Request, res: Response) => {
  const windowId = (req.session as any).active_window || 1;
  const type = req.params.type as string;
  const validTypes = ['Assessments', 'Enrollment', 'Payments', 'Other Concerns'];
  
  if (!validTypes.includes(type)) {
    return res.redirect('/staff-dashboard');
  }
  
  try {
    // Get next pending for the specific type
    const [next]: any = await pool.query(
      `SELECT id FROM queue WHERE status = 'Pending' AND document_type = ? ORDER BY id ASC LIMIT 1`,
      [type]
    );
    if (next.length > 0) {
      // Update status to Serving AND set window_id to current window
      await pool.query(
        "UPDATE queue SET status = 'Serving', window_id = ? WHERE id = ?",
        [windowId, next[0].id]
      );
    }
    res.redirect('/staff-dashboard');
  } catch (err: any) {
    console.error(err);
    res.redirect('/staff-dashboard');
  }
});

// Process Next - Get next pending ticket from allowed types
app.get('/process-next', requireAuth, requireRole('admin', 'staff'), async (req: Request, res: Response) => {
  const windowId = (req.session as any).active_window || 1;
  const typeMap: { [key: number]: string } = { 1: 'Assessments', 2: 'Enrollment', 3: 'Payments', 4: 'Other Concerns' };
  const defaultType = typeMap[windowId] || 'Assessments';
  const additionalTypes = (req.session as any).additional_types || [];
  const activeTypes = [defaultType, ...additionalTypes];
  
  try {
    const placeholders = activeTypes.map(() => '?').join(',');
    const [next]: any = await pool.query(
      `SELECT id FROM queue WHERE status = 'Pending' AND document_type IN (${placeholders}) ORDER BY id ASC LIMIT 1`,
      activeTypes
    );
    if (next.length > 0) {
      // Update status to Serving AND set window_id to current window
      await pool.query(
        "UPDATE queue SET status = 'Serving', window_id = ? WHERE id = ?",
        [windowId, next[0].id]
      );
    }
    res.redirect('/staff-dashboard');
  } catch (err: any) {
    console.error(err);
    res.redirect('/staff-dashboard');
  }
});

// Complete
app.get('/complete/:id', requireAuth, requireRole('admin', 'staff'), async (req: Request, res: Response) => {
  const windowId = (req.session as any).active_window || 1;
  try {
    const [items]: any = await pool.query("SELECT * FROM queue WHERE id = ?", [req.params.id]);
    if (items.length > 0) {
      const item = items[0];
      await pool.query(
        "INSERT INTO transaction_history (student_name, student_id, blk_course, queue_number, transaction_type, window_number, served_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
        [item.student_name, item.student_id, item.blk_course, item.queue_number, item.document_type, windowId]
      );
      await pool.query("DELETE FROM queue WHERE id = ?", [req.params.id]);
    }
    res.redirect('/staff-dashboard');
  } catch (err: any) {
    console.error(err);
    res.redirect('/staff-dashboard');
  }
});

// Transaction Selection
app.get('/transaction-selection', requireAuth, requireRole('student'), (req: Request, res: Response) => {
  res.render('transaction-selection', {
    ticket: req.query.ticket,
    name: req.query.name,
    blkCourse: req.query.blk_course,
    category: req.query.category,
    year: req.query.year,
    position: req.query.position,
    window: req.query.window
  });
});

// Generate Ticket
app.post('/generate-ticket', requireAuth, requireRole('student'), async (req: Request, res: Response) => {
  const { category, student_name, blk_course, year } = req.body;
  
  // Handle array case (when duplicate field names exist)
  const yearValue = Array.isArray(year) ? year[0] : year;
  const blkCourseValue = Array.isArray(blk_course) ? blk_course[0] : blk_course;
  
  const userId = (req.session as any).user_id || 1;
  
  const prefixes: { [key: string]: string } = { 'Assessments': 'A', 'Enrollment': 'E', 'Payments': 'P', 'Other Concerns': 'O' };
  const prefix = prefixes[category] || 'X';
  
  try {
    const [counters]: any = await pool.query("SELECT last_number FROM queue_counters WHERE category = ?", [category]);
    
    let num: number;
    if (counters.length > 0) {
      num = counters[0].last_number + 1;
      await pool.query("UPDATE queue_counters SET last_number = ? WHERE category = ?", [num, category]);
    } else {
      num = 1;
      await pool.query("INSERT INTO queue_counters (category, last_number) VALUES (?, 1)", [category]);
    }
    
    const queueNumber = prefix + String(num).padStart(3, '0');
    
    const insertValues = [userId, student_name || '', blkCourseValue || '', yearValue || '', queueNumber, category, 'Pending'];
    await pool.query(
      "INSERT INTO queue (user_id, student_name, blk_course, year, queue_number, document_type, status) VALUES (?, ?, ?, ?, ?, ?, ?)",
      insertValues
    );
    
    const [positionResult]: any = await pool.query(
      "SELECT COUNT(*) as count FROM queue WHERE document_type = ? AND status = 'Pending' AND id < (SELECT id FROM queue WHERE queue_number = ?)",
      [category, queueNumber]
    );
    const position = positionResult[0].count + 1;
    
    const windowMap: { [key: string]: number } = { 'Assessments': 1, 'Enrollment': 2, 'Payments': 3, 'Other Concerns': 4 };
    const window = windowMap[category] || 1;
    
    res.redirect(`/transaction-selection?ticket=${queueNumber}&name=${encodeURIComponent(student_name)}&blk_course=${encodeURIComponent(blkCourseValue)}&category=${encodeURIComponent(category)}&year=${encodeURIComponent(yearValue)}&position=${position}&window=${window}`);
  } catch (err: any) {
    console.error(err);
    res.redirect('/transaction-selection');
  }
});

// Active Queues
app.get('/active-queues', requireAuth, requireRole('admin', 'staff'), async (req: Request, res: Response) => {
  const windowId = (req.session as any).active_window || 1;
  const typeMap: { [key: number]: string } = { 1: 'Assessments', 2: 'Enrollment', 3: 'Payments', 4: 'Other Concerns' };
  const currentCategory = typeMap[windowId] || 'Assessments';
  
  try {
    const [queues]: any = await pool.query(
      "SELECT * FROM queue WHERE document_type = ? AND status = 'Pending' ORDER BY created_at ASC",
      [currentCategory]
    );
    res.render('active-queues', { windowId, currentCategory, queues });
  } catch (err: any) {
    console.error(err);
    res.redirect('/staff-dashboard');
  }
});

// History
app.get('/history', requireAuth, requireRole('admin', 'staff'), async (req: Request, res: Response) => {
  try {
    const windowId = (req.session as any).active_window || 1;
    const startDate = req.query.start_date as string || '';
    const endDate = req.query.end_date as string || '';
    const windowFilter = req.query.window as string || '';
    const search = req.query.search as string || '';
    
    // Build query with filters
    let query = "SELECT * FROM transaction_history WHERE 1=1";
    const params: any[] = [];
    
    if (startDate && endDate) {
      query += " AND served_at BETWEEN ? AND ?";
      params.push(startDate + " 00:00:00", endDate + " 23:59:59");
    }
    
    if (windowFilter) {
      query += " AND window_number = ?";
      params.push(windowFilter);
    }
    
    if (search) {
      query += " AND (student_name LIKE ? OR queue_number LIKE ?)";
      const searchParam = "%" + search + "%";
      params.push(searchParam, searchParam);
    }
    
    query += " ORDER BY served_at DESC LIMIT 100";
    
    const [history]: any = params.length > 0 
      ? await pool.query(query, params)
      : await pool.query("SELECT * FROM transaction_history ORDER BY served_at DESC LIMIT 100");
    
    res.render('history', { 
      history, 
      windowId, 
      startDate, 
      endDate,
      windowFilter,
      search
    });
  } catch (err: any) {
    console.error(err);
    res.redirect('/admin-selection');
  }
});

// Public Monitor
app.get('/monitor', (req: Request, res: Response) => {
  res.render('public-monitor');
});

// API Monitor Data - Shows which window is actually serving which transaction type
app.get('/api/monitor-data', async (req: Request, res: Response) => {
  try {
    const typeMap: { [key: number]: string } = { 1: 'Assessments', 2: 'Enrollment', 3: 'Payments', 4: 'Other Concerns' };
    const reverseTypeMap: { [key: string]: number } = { 'Assessments': 1, 'Enrollment': 2, 'Payments': 3, 'Other Concerns': 4 };
    
    // Get all serving tickets with their window_id
    const [servingTickets]: any = await pool.query(
      "SELECT queue_number, document_type, window_id FROM queue WHERE status = 'Serving'"
    );
    
    // Get all waiting tickets grouped by document_type
    const [waitingTickets]: any = await pool.query(
      "SELECT queue_number, document_type FROM queue WHERE status = 'Pending' ORDER BY document_type, created_at"
    );
    
    // Organize waiting tickets by type
    const waitingByType: { [key: string]: string[] } = {};
    waitingTickets.forEach((t: any) => {
      if (!waitingByType[t.document_type]) {
        waitingByType[t.document_type] = [];
      }
      if (waitingByType[t.document_type].length < 10) {
        waitingByType[t.document_type].push(t.queue_number);
      }
    });
    
    // Build response for each window
    const data = [];
    for (const windowNum of [1, 2, 3, 4]) {
      const category = typeMap[windowNum];
      
      // Find which ticket this window is serving
      const serving = servingTickets.find((t: any) => t.window_id == windowNum);
      
      data.push({
        window: windowNum,
        number: serving ? serving.queue_number : '---',
        category: category,
        waiting: waitingByType[category] || []
      });
    }
    
    res.json(data);
  } catch (err: any) {
    console.error(err);
    res.status(500).json({ error: 'Failed to fetch data' });
  }
});

// Reports & Settings Menu
app.get('/reports-settings', requireAuth, requireRole('admin', 'staff'), (req: Request, res: Response) => {
  const windowId = (req.session as any).active_window || 1;
  res.render('reports-settings', { windowId });
});

// Admin Reports
app.get('/admin-reports', requireAuth, requireRole('admin', 'staff'), async (req: Request, res: Response) => {
  try {
    const windowId = (req.session as any).active_window || 1;
    const today = new Date().toISOString().slice(0, 10);
    const startDate = req.query.start_date as string || '';
    const endDate = req.query.end_date as string || '';
    
    const [todayCount]: any = await pool.query(
      "SELECT COUNT(*) as count FROM transaction_history WHERE DATE(served_at) = ?",
      [today]
    );
    const [queueCount]: any = await pool.query("SELECT COUNT(*) as count FROM queue WHERE status = 'Pending'");
    const [userCount]: any = await pool.query("SELECT COUNT(*) as count FROM users");
    
    // Get chart data - counts by transaction type
    const typeCounts: any = await pool.query(
      "SELECT transaction_type, COUNT(*) as count FROM transaction_history WHERE DATE(served_at) = ? GROUP BY transaction_type",
      [today]
    );
    
    const catLabels = (typeCounts[0] || []).map((t: any) => t.transaction_type || 'Other');
    const catVals = (typeCounts[0] || []).map((t: any) => parseInt(t.count));
    
    // Get top window
    const topWindowResult: any = await pool.query(
      "SELECT window_number, COUNT(*) as count FROM transaction_history WHERE DATE(served_at) = ? AND window_number IS NOT NULL GROUP BY window_number ORDER BY count DESC LIMIT 1",
      [today]
    );
    
    // Get hourly data
    const hourData: any = await pool.query(
      "SELECT HOUR(served_at) as hour, COUNT(*) as count FROM transaction_history WHERE DATE(served_at) = ? GROUP BY HOUR(served_at)",
      [today]
    );
    
    const hourVals = Array(10).fill(0);
    (hourData[0] || []).forEach((h: any) => {
      const hour = parseInt(h.hour);
      if (hour >= 8 && hour <= 17) {
        hourVals[hour - 8] = parseInt(h.count);
      }
    });
    
    res.render('admin-reports', {
      todayCount: todayCount[0]?.count || 0,
      queueCount: queueCount[0]?.count || 0,
      userCount: userCount[0]?.count || 0,
      totalServed: todayCount[0]?.count || 0,
      topWindow: topWindowResult[0]?.[0]?.window_number || '-',
      topWindowCount: topWindowResult[0]?.[0]?.count || 0,
      catLabels,
      catVals,
      hourVals,
      windowId,
      startDate,
      endDate
    });
  } catch (err: any) {
    console.error(err);
    res.redirect('/admin-selection');
  }
});

// Admin Settings
app.get('/admin-settings', requireAuth, requireRole('admin', 'staff'), async (req: Request, res: Response) => {
  try {
    const windowId = (req.session as any).active_window || 1;
    const [settings]: any = await pool.query("SELECT * FROM settings LIMIT 1");
    res.render('admin-settings', {
      settings: settings[0] || {},
      windowId,
      message: req.query.message || ''
    });
  } catch (err: any) {
    console.error(err);
    res.redirect('/admin-selection');
  }
});

// Reset queue (Stop/Reset button) - clears pending and serving tickets
app.get('/reset-queue', requireAuth, requireRole('admin', 'staff'), async (req: Request, res: Response) => {
  try {
    // Delete all Pending tickets
    await pool.query("DELETE FROM queue WHERE status = 'Pending'");
    
    // Delete all Serving tickets
    await pool.query("DELETE FROM queue WHERE status = 'Serving'");
    
    // Reset all counters to 0
    const categories = ['Assessments', 'Enrollment', 'Payments', 'Other Concerns'];
    for (const cat of categories) {
      await pool.query(
        "UPDATE queue_counters SET last_number = 0 WHERE category = ?",
        [cat]
      );
    }
    
    res.redirect('/staff-dashboard?message=Queue reset successfully');
  } catch (err: any) {
    console.error(err);
    res.redirect('/staff-dashboard?message=Error resetting queue');
  }
});

// Reset all queues (for new day) - admin only
app.post('/reset-all-queues', requireAuth, requireRole('admin'), async (req: Request, res: Response) => {
  try {
    await pool.query("DELETE FROM queue WHERE status = 'Pending'");
    res.json({ success: true, message: 'All active queues cleared successfully!' });
  } catch (err: any) {
    console.error(err);
    res.json({ success: false, message: 'Error clearing queues' });
  }
});

// Update settings
app.post('/update-settings', requireAuth, requireRole('admin'), async (req: Request, res: Response) => {
  try {
    const { language, theme, sound_enabled, notifications_enabled } = req.body;
    await pool.query(
      "UPDATE settings SET language = ?, theme = ?, sound_enabled = ?, notifications_enabled = ? WHERE id = 1",
      [language || 'en', theme || 'light', sound_enabled ? 1 : 0, notifications_enabled ? 1 : 0]
    );
    res.redirect('/admin-settings?message=Settings updated successfully');
  } catch (err: any) {
    console.error(err);
    res.redirect('/admin-settings?message=Error updating settings');
  }
});

// Download database backup
app.get('/download-backup', requireAuth, requireRole('admin'), async (req: Request, res: Response) => {
  try {
    const [history]: any = await pool.query("SELECT * FROM transaction_history ORDER BY served_at DESC");
    const [queue]: any = await pool.query("SELECT * FROM queue");
    
    let sql = "-- ESCR DQMS Database Backup\n";
    sql += `-- Generated: ${new Date().toISOString()}\n\n`;
    
    // Insert statements for transaction_history
    for (const row of history) {
      sql += `INSERT INTO transaction_history (student_name, student_id, blk_course, transaction_type, window_number, served_at) VALUES (`;
      sql += `'${row.student_name}', '${row.student_id || ''}', '${row.blk_course || ''}', '${row.transaction_type}', ${row.window_number}, '${row.served_at}');\n`;
    }
    
    sql += "\n-- Queue Data\n";
    for (const row of queue) {
      sql += `INSERT INTO queue (student_name, student_id, blk_course, year, queue_number, document_type, status) VALUES (`;
      sql += `'${row.student_name}', '${row.student_id || ''}', '${row.blk_course || ''}', '${row.year || ''}', '${row.queue_number}', '${row.document_type}', '${row.status}');\n`;
    }
    
    res.setHeader('Content-Type', 'text/plain');
    res.setHeader('Content-Disposition', `attachment; filename=escr_backup_${new Date().toISOString().slice(0,10)}.sql`);
    res.send(sql);
  } catch (err: any) {
    console.error(err);
    res.status(500).send('Error generating backup');
  }
});

// Start Server - only for local development
const PORT = process.env.PORT || 3000;
if (!process.env.VERCEL) {
  app.listen(PORT, () => {
    console.log(`🚀 ESCR DQMS Server running at http://localhost:${PORT}`);
    console.log(`Default admin: username=admin, password=Admin@123`);
  });
}

export default app;
