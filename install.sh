#!/bin/sh
echo "Enter mysql user name"
read USER
echo "Installing database"
mysql -u $USER -p <create.sql
echo "Installed"
