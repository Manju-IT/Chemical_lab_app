# Chemical Laboratory Management Application

<img width="1893" height="956" alt="image" src="https://github.com/user-attachments/assets/1a975e30-cc54-4a2d-bde5-3ef1ba2b2713" />


# Algorithm Explanation

This document describes the core algorithms used in the Chemical Laboratory Inventory Management System.

## 1. Low Stock Rule

A chemical is considered **low stock** when the current stock is less than or equal to the average consumption over 7 days.

**Formula:**

current_stock <= usage_rate × 7

This gives a week's buffer before stockout.

## 2. Reorder Rule

A reorder alert is triggered when the predicted stock reaches zero within the next 10 days, based on the current usage rate.

**Formula:**

expiry_date <= CURDATE() + INTERVAL 30 DAY


## 4. Simulation Logic

The simulation predicts stock levels for each chemical over a 30‑day period, day by day.

**Algorithm:**
1. Start with current stock and daily usage rate.
2. For each day from 1 to 30:
   - Subtract the daily usage rate from stock (stock cannot go negative).
   - If the stock becomes zero, stop further simulation for that chemical.
   - If the expiry date is reached before the simulation day, mark as expired and stop.
   - Generate alerts:
     - Low stock: when stock ≤ usage_rate × 7
     - Reorder: when stock - usage_rate × 10 ≤ 0 (checked before consumption)
     - Expired: when current day passes expiry date

The simulation runs for all chemicals and produces a day‑by‑day report.

## 5. Audit Logging

All significant actions (login, add, edit, delete, CSV upload) are recorded in the `audit_logs` table with user ID, timestamp, and details.

## 6. Error Handling

System errors (e.g., database exceptions) are caught and logged into `error_logs` with file name, line number, and user context (if logged in). This helps in debugging without exposing sensitive information to users.
