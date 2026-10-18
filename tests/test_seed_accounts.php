<?php
$h1 = '$2y$10$dfsUFLafgWRrSwvtG8QoRuotX9iTWh7KYEqKJkhqN/B.t8BTxFTsa';
$h2 = '$2y$10$BB2YDJViQZX6lWd2TJ899.j7gjHG6hDLT8QMQlNzg3fJwxyrxJ/cu';
$h3 = '$2y$10$dP.HcyiIITqAifWHuk4ciOejHFTB.IFl0TQSYPwfJ0KyQdlokNSxe';

if (!password_verify('Admin@12345!', $h1)) {
    die("Admin@12345! verification failed\n");
}
if (!password_verify('User@1234', $h2)) {
    die("User@1234 verification failed\n");
}
if (!password_verify('admin', $h3)) {
    die("admin answer verification failed\n");
}

echo "All hash verifications passed successfully!\n";
