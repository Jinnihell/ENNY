# ESCR DQMS - User Flow Guide

## 🚶 STUDENT USER FLOW

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         STUDENT FLOW                                        │
└─────────────────────────────────────────────────────────────────────────────┘

1️⃣  ACCESS SYSTEM
    │
    │   http://localhost:3000/
    │
    └──────▶ Login Page (/login)
                │
    ┌───────────┴───────────┐
    │                       │
  Login              Sign Up (new users)
    │                       │
    └───────┬───────────────┘
            │
            ▼
2️⃣  AUTHENTICATE
    │
    │   Enter username/email + password
    │
    └──────▶ Validate credentials → Create session
                │
                │ (if admin/staff, redirect to admin-selection)
                │
                ▼
3️⃣  LANDING PAGE (/landing)
    │
    │   Display: ESCR Digital Queueing System
    │   Buttons: Get Started, About
    │
    └──────▶ Click "Get Started"
                │
                ▼
4️⃣  TRANSACTION SELECTION (/transaction-selection)
    │
    │   Select category:
    │   • Assessments (Window 1) - Prefix: A
    │   • Enrollment (Window 2) - Prefix: E
    │   • Payments (Window 3) - Prefix: P
    │   • Other Concerns (Window 4) - Prefix: O
    │
    │   Fill form:
    │   • Full Name
    │   • Block & Course
    │   • Year Level
    │
    └──────▶ Click "Get Ticket"
                │
                ▼
5️⃣  GET TICKET (Display)
    │
    │   Shows:
    │   • Ticket Number (e.g., A001, E015)
    │   • Position in Queue
    │   • Assigned Window
    │
    └──────▶ Proceed to assigned window
                │
                ▼
6️⃣  WAIT FOR TURN
    │
    │   Check /monitor for queue status
    │
    └──────▶ When called → Go to window
                │
                ▼
7️⃣  SERVICE COMPLETE
    │
    │   (Staff marks as complete)
    │
    └──────▶ Can get another ticket or logout


──────────────────────────────────────────────────────────────────────────────
```

---

## 👨‍💼 STAFF/ADMIN USER FLOW

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                      STAFF/ADMIN FLOW                                      │
└─────────────────────────────────────────────────────────────────────────────┘

1️⃣  ACCESS SYSTEM
    │
    │   http://localhost:3000/
    │
    └──────▶ Login Page (/login)
                │
    ┌───────────┴───────────┐
    │                       │
  Login              Sign Up (if allowed)
    │                       │
    └───────┬───────────────┘
            │
            ▼
2️⃣  AUTHENTICATE
    │
    │   Enter username/email + password
    │
    └──────▶ Validate credentials → Check role
                │
                │ (if student, redirect to landing)
                │
                ▼
3️⃣  ADMIN SELECTION (/admin-selection)
    │
    │   Three options:
    │   • User Interface → Window Selection
    │   • Reports & Settings → View statistics
    │   • Logout
    │
    └──────▶ Click "User Interface"
                │
                ▼
4️⃣  WINDOW SELECTION (/window-selection)
    │
    │   Select your window:
    │   • Window 1 - Assessments
    │   • Window 2 - Enrollment
    │   • Window 3 - Payments
    │   • Window 4 - Other Concerns
    │
    └──────▶ Click window button
                │
                ▼
5️⃣  STAFF DASHBOARD (/staff-dashboard)
    │
    │   Display shows:
    │   ┌─────────────────────────────────────┐
    │   │  CURRENTLY SERVING                 │
    │   │  ╔═══════════════════════════════╗  │
    │   │  ║     A001                      ║  │
    │   │  ╚═══════════════════════════════╝  │
    │   │  Student Name: John Doe           │
    │   │  Course: BSIT                     │
    │   │  Year: 3rd Year                  │
    │   └─────────────────────────────────────┘
    │
    │   Stats: [ Waiting: 5 ] [ Completed: 12 ]
    │
    │   Next in Line: [A002] [A003] [A004]
    │
    │   Actions:
    │   [ Call Next ] [ Complete ] [ View Active Queues ]
    │
    └──────▶ Click "Call Next"
                │
                ▼
6️⃣  CALL NEXT STUDENT
    │
    │   System:
    │   1. Marks current student as complete (moves to history)
    │   2. Calls next student from queue
    │   3. Updates status to "Serving"
    │
    └──────▶ Dashboard refreshes with new student
                │
                ▼
7️⃣  COMPLETE TRANSACTION
    │
    │   Click "Complete" when service done
    │
    └──────▶ Student moved to transaction_history
                │
                ▼
8️⃣  VIEW ACTIVE QUEUES (/active-queues)
    │
    │   Table showing all pending students
    │   Queue# | Name | Course | Year | Status | Time
    │
    └──────▶ Can manually call specific student


──────────────────────────────────────────────────────────────────────────────
```

---

## 📊 ADDITIONAL FEATURES

### Public Monitor Flow
```
/monitor (public - no login needed)
    │
    ├── Shows all 4 windows with current serving number
    ├── Shows next 10 waiting tickets
    ├── Auto-refreshes every 3 seconds
    └── Plays sound when new number is called
```

### Reports Flow
```
/reports-settings (admin only)
    │
    ├── Total Users count
    ├── Current Queue count
    ├── Served Today count
    └── System overview
```

---

## 🔑 KEY DIFFERENCES

| Feature | Student | Staff/Admin |
|---------|---------|--------------|
| Login | ✅ | ✅ |
| Get Ticket | ✅ | ❌ |
| Call Next | ❌ | ✅ |
| Complete Transaction | ❌ | ✅ |
| View Active Queues | ❌ | ✅ |
| View History | ❌ | ✅ |
| View Reports | ❌ | ✅ (admin) |
| Switch Windows | ❌ | ✅ |

---

## 🎯 QUICK REFERENCE

### Routes Available:
- `/login` - Login page
- `/signup` - Registration
- `/landing` - Student home
- `/transaction-selection` - Get ticket
- `/admin-selection` - Admin menu
- `/window-selection` - Select window
- `/staff-dashboard` - Staff work area
- `/active-queues` - View all queues
- `/history` - Transaction history
- `/reports-settings` - Admin reports
- `/monitor` - Public display
- `/api/monitor-data` - JSON data for monitor

### Database Roles:
- `student` - Can get tickets
- `staff` - Can serve customers
- `admin` - Full access + reports
