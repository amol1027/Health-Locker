<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Locker Reminder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #4CAF50;
            color: #ffffff;
            padding: 10px;
            text-align: center;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .content {
            padding: 20px;
            text-align: left;
            color: #333333;
        }
        .footer {
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #777777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Health Locker Reminder</h1>
        </div>
        <div class="content">
            <p>Hi <?php echo htmlspecialchars($user_name); ?>,</p>
            <p>This is a reminder for you:</p>
            <p><strong><?php echo htmlspecialchars($reminder_text); ?></strong></p>
            <p>Please take care of your health.</p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Health Locker. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
