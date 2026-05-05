<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>New Contact Message</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #111827;">
    <h2 style="margin-bottom: 16px;">New Contact Message</h2>

    <p><strong>Name:</strong> {{ $contactMessage->name }}</p>
    <p><strong>Email:</strong> {{ $contactMessage->email }}</p>
    <p><strong>Subject:</strong> {{ $contactMessage->subject }}</p>
    <p><strong>Submitted At:</strong> {{ optional($contactMessage->created_at)->toDateTimeString() }}</p>
    <p><strong>IP Address:</strong> {{ $contactMessage->ip_address ?? '-' }}</p>

    <hr style="margin: 16px 0;">

    <p style="white-space: pre-wrap;">{{ $contactMessage->message }}</p>
</body>
</html>

