#!/bin/bash

# Ensure sessions directory exists
mkdir -p /app/tmp/sessions

# Start the main backend process in the background
echo "Starting mainproc..."
./mainproc &

# Give mainproc a moment to initialize (e.g., set up message queues)
sleep 2

# Start the Web Server in the foreground on port 80
echo "Starting http server on port 80..."
./http 80
