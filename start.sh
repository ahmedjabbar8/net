#!/bin/bash

MYSQL_DATA="$HOME/mysql_data"
MYSQL_RUN="$HOME/mysql_run"
MYSQL_LOG="$HOME/mysql_log"
MYSQL_SOCK="$MYSQL_RUN/mysql.sock"

mkdir -p "$MYSQL_DATA" "$MYSQL_RUN" "$MYSQL_LOG"

pkill -f mysqld 2>/dev/null
sleep 1

if [ ! -f "$MYSQL_DATA/ibdata1" ]; then
    echo "Initializing MariaDB data directory..."
fi

mysqld --datadir="$MYSQL_DATA" \
       --socket="$MYSQL_SOCK" \
       --port=3306 \
       --skip-grant-tables \
       --bind-address=127.0.0.1 \
       --log-error="$MYSQL_LOG/error.log" \
       --innodb-buffer-pool-size=64M \
       --max-connections=50 &

MYSQL_PID=$!
echo "MariaDB starting (PID: $MYSQL_PID)..."

for i in $(seq 1 30); do
    if mysql -h 127.0.0.1 -P 3306 -e "SELECT 1" > /dev/null 2>&1; then
        echo "MariaDB is ready!"
        break
    fi
    sleep 1
done

if ! mysql -h 127.0.0.1 -P 3306 -e "SELECT 1" > /dev/null 2>&1; then
    echo "ERROR: MariaDB failed to start. Check $MYSQL_LOG/error.log"
    cat "$MYSQL_LOG/error.log" | tail -20
    exit 1
fi

DB_EXISTS=$(mysql -h 127.0.0.1 -P 3306 -N -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='HospitalSystem'" 2>/dev/null)
if [ -z "$DB_EXISTS" ]; then
    echo "Setting up HospitalSystem database..."
    mysql -h 127.0.0.1 -P 3306 < setup_database.sql
    echo "Database setup complete!"
else
    echo "Database HospitalSystem already exists."
fi

mkdir -p _suite/uploads/patients

echo "Starting PHP server on 0.0.0.0:5000..."
php -S 0.0.0.0:5000 router.php
