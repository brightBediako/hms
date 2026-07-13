# Restore HMS from a backup dump

Restore is a **manual** procedure. The Backup UI can create and download dumps; it does not overwrite the live database in one click.

## Prerequisites

- A dump file from `storage/backups/` (e.g. `hms_20260713_153000.sql`)
- MySQL client tools (`mysql` / XAMPP `mysql.exe`)
- Access to the HMS database credentials from `.env`

## Steps (XAMPP / Windows)

1. Sign out all staff and stop accepting bookings (optional maintenance window).
2. Download the dump from **Settings → Backups**, or copy it from `storage/backups/` on the server.
3. Open a shell and go to the MySQL bin folder if needed:

```bat
cd C:\xampp\mysql\bin
```

4. Restore into the `hms` database (adjust user/password/host to match `.env`):

```bat
mysql.exe -h 127.0.0.1 -P 3306 -u root hms < "C:\path\to\hms_YYYYMMDD_HHMMSS.sql"
```

If the dump was created with `--databases`, you can omit the database name:

```bat
mysql.exe -h 127.0.0.1 -P 3306 -u root < "C:\path\to\hms_YYYYMMDD_HHMMSS.sql"
```

5. Confirm tables exist:

```bat
mysql.exe -h 127.0.0.1 -u root -e "USE hms; SHOW TABLES;"
```

6. Open HMS in the browser, sign in, and spot-check:
   - Dashboard metrics load
   - A known reservation / invoice appears
   - Staff can still sign in

## Notes

- Prefer restoring onto a **staging** copy first when possible.
- Uploaded guest documents under `storage/uploads/` are **not** included in the SQL dump; restore those separately if needed.
- After restore, rotate the demo admin password if this dump left a shared environment.
- Optional: set `MYSQLDUMP_PATH` in `.env` if `mysqldump` is not on the default XAMPP path.
