# 🚖 CabZi - Cab Booking System

**CabZi** is a web-based cab booking platform built using PHP and MySQL. It supports both online and offline bookings with role-based access for users and cab owners.

---

## 🔧 Installation Instructions

1. **Clone or Download** the project folder.
2. Create a new **MySQL database** (e.g., `cabzi`).
3. Import the `cabzi.sql` file into your database.
4. Update your database connection in `db.php`:
   ```php
   $con = new mysqli("localhost", "root", "", "cabzi");
   ```
5. Run the project in a **local server** (XAMPP, WAMP, MAMP).

---

## 🧩 Modules Description

### 👤 User Module
- **User Registration & Login**
- **Dashboard with Actions**:
  - Search cabs (self/rental)
  - Book available cars
  - View and manage booking history
  - Get notifications
  - Edit profile
- **Notifications**: Booking status updates shown in dashboard modal
- **Logout**: Secure logout with confirmation

### 👨‍🔧 Owner Module
- **Owner Login**
- **Car Management**:
  - Add, update, delete cars
- **Booking Management**:
  - View all online/offline bookings
  - Confirm, reject, or update booking status and cost
- **Offline Booking Form**:
  - For walk-in customers
  - Add new or choose existing offline customers
- **Reporting (owner_reports.php)**:
  - Total online/offline bookings
  - Most booked car
  - Peak booking dates
  - Booking type breakdown (self vs rental)
  - Graphs and icons for better visualization

### 📢 Notification System
- **User Notifications**: Sent after booking or status changes
- **Owner Notifications**: Triggered on new booking requests

---

## 📞 Contact Developer

For queries or support:  
👉 [Contact Developer](https://naveenshankar.in)

---

## 🆓 License

This project is free to use for learning and academic purposes.
