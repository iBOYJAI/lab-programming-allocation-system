<?php
echo "Admin@123: " . password_hash("Admin@123", PASSWORD_BCRYPT) . "\n";
echo "Password@123: " . password_hash("Password@123", PASSWORD_BCRYPT) . "\n";
