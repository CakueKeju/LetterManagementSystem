<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Letter Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container min-vh-100 d-flex flex-column justify-content-center align-items-center">
        <div class="text-center">
            <h1 class="display-4 mb-3">Letter Management System</h1>
            <p class="lead mb-4">A centralized platform for uploading, managing, and searching official letters in your organization.</p>
            <div class="mb-4">
                <a href="{{ route('login') }}" class="btn btn-primary btn-lg me-2">Login</a>
            </div>
            <hr>
            <p class="text-muted">&copy; {{ date('Y') }} Letter Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
