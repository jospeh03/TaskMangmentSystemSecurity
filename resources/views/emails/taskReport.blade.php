<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Report</title>
</head>
<body>
    <h1>Hello, {{ $user->name }}</h1>
    <p>Here is your requested task report:</p>
    {!! $report !!}
</body>
</html>
