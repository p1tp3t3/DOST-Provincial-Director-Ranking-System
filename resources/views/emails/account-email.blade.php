<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <h1>Hello {{ $name }},</h1>
    <p>Your account has been created successfully. Here are your account details:</p>
    <ul>
        <li><strong>Username:</strong> {{ $username }}</li>
        <li><strong>Email:</strong> {{ $email }}</li>
        <li><strong>DOST ID:</strong> {{ $dost_id }}</li>
        <li><strong>Password:</strong> {{ $password }}</li>
    </ul>
    <p>Please log in to the Director Ranking Information System and change your password as soon as possible.</p>
</body>
</html>