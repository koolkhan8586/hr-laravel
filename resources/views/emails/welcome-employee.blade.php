<h2>Welcome to HR Management System</h2>

<p>Dear {{ $user->name }},</p>

<p>Your account has been created successfully.</p>

<p><strong>Login URL:</strong> {{ url('/') }}</p>
<p><strong>Email:</strong> {{ $user->email }}</p>
<p><strong>Password:</strong> {{ $password }}</p>

<p>Please login and change your password immediately.</p>

<p>Best Regards,<br>
HR Department</p>
