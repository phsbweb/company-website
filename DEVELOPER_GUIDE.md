# 🧑‍💻 Developer Onboarding & Local Testing Guide

Welcome to the **Priority Horizon** local development environment! Since the project currently does not have a live "Staging" URL, all pre-production testing must be done safely on your local machine using the `development` branch before it goes live to the `main` branch.

This guide will walk you through the branching strategy, how to test changes locally, and how to connect to our live cloud database.

---

## 1. 🌿 Git Branching Strategy

Our repository uses a simple two-branch system to protect the live website:

- **`main` branch (Production)**: This is the live code! Vercel automatically deploys whatever is pushed here directly to the public website. **Never push untested code directly to `main`**.
- **`development` branch (Staging/Testing)**: This is our workbench. All new features, bug fixes, and experiments happen here. Once everything works perfectly on your local machine, we merge this branch into `main`.

---

## 2. 💻 Local Testing Workflow (XAMPP)

Since we don't have a live staging server, you must test the `development` branch on your own computer using XAMPP.

### **Step-by-Step Testing Process:**

1.  **Open XAMPP** and Start your **Apache** server. (You don't _need_ MySQL running locally if you are connecting to the cloud database).
2.  **Open your Terminal** (or VS Code / Cursor terminal) inside the `htdocs/phsb` folder.
3.  Ensure you are on the development branch:
    ```bash
    git checkout development
    ```
4.  Pull the latest changes from the team:
    ```bash
    git pull origin development
    ```
5.  Open your browser and navigate to **`http://localhost:8080/phsb/api`**.
6.  _Test the website features thoroughly._

### **How to Push Your Changes Up:**

Once you've tested your features locally and they work perfectly:

```bash
git add .
git commit -m "Describe what feature you just built or fixed"
git push origin development
```

### **How to Deploy to Live (`main`):**

When the `development` branch is stable and ready for the public:

```bash
git checkout main
git pull origin main
git merge development
git push origin main
```

_Vercel will immediately detect the push to `main` and update the live website._

---

## 3. 🏁 Local Database Setup

For your local development to work correctly, you'll need the matching database tables. 

1.  Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
2.  Create a new database named **`phsb_erp`** and another named **`phsb_web`**.
3.  Go to the **`/api/database`** folder in this repository.
4.  Import **`phsb_erp.sql`** into the `phsb_erp` database and **`phsb_web.sql`** into the `phsb_web` database. 
5.  **Default Admin Logins**:
    *   **Admin Panel Username**: `phsb_adm` (Password: `admin123`)
    *   **Staff Portal (User 1) Username**: `admin` (Password: `admin123`)
    *   **Staff Portal (User 2) Username**: `employee1` (Password: `admin123`)

---

## 4. 🗄️ Connecting to the Aiven Cloud Database

Our databases (`phsb_web` and `phsb_erp`) live in the cloud on Aiven. To view the data, run queries, or debug issues, we use **HeidiSQL**.

### **How to Install HeidiSQL:**

1.  Go to [heidisql.com/download.php](https://www.heidisql.com/download.php)
2.  Download the **Installer**, run it, and complete the installation.

### **How to Connect:**

1.  Open HeidiSQL and click **New** in the bottom left corner to create a new Session.
2.  Name the session "Aiven Cloud DB".
3.  Go to the **Settings** tab and enter the following:
    - **Network type**: `MariaDB or MySQL (TCP/IP)`
    - **Hostname / IP**: `mysql-xxxx.aivencloud.com` _(Get exact URL from Aiven Dashboard)_
    - **User**: `avnadmin`
    - **Password**: _(Your Aiven Password)_
    - **Port**: **`10624`** ⚠️ _(CRITICAL: This is not the default 3306)_
4.  Go to the **SSL** tab:
    - Check ✅ **Use SSL**
    - Check ✅ **Ignore server certificate verification** _(This prevents annoying certificate errors)_
5.  Click **Open**.

You should now see `phsb_web` and `phsb_erp` in the left sidebar! You can browse tables, view user data, and manually edit records just like you did in phpMyAdmin.

---

## ⚠️ Important Database Warning

Because your local XAMPP site connects _directly to the live Aiven database_, **any data you delete or change locally will affect the real system!**

- If you create a test user, remember to delete it.
- If you need to do heavy destructive testing, consider duplicating the database on Aiven first.
