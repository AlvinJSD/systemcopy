<?php

echo "Student: " . password_hash("Student@123", PASSWORD_BCRYPT) . "<br><br>";

echo "Adviser: " . password_hash("Adviser@123", PASSWORD_BCRYPT) . "<br><br>";

echo "Admin: " . password_hash("Admin@123", PASSWORD_BCRYPT) . "<br><br>";