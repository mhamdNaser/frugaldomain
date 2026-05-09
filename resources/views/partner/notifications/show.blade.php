<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Notification Details</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f6f7fb; color: #111827; }
        .container { max-width: 760px; margin: 40px auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; }
        .title { font-size: 24px; margin-bottom: 8px; }
        .meta { color: #6b7280; margin-bottom: 20px; font-size: 14px; }
        .message { line-height: 1.7; white-space: pre-wrap; }
        .actions { margin-top: 24px; }
        .back { display: inline-block; text-decoration: none; color: #2563eb; }
    </style>
</head>
<body>
<div class="container">
    <h1 id="title" class="title">Loading...</h1>
    <div id="meta" class="meta"></div>
    <div id="message" class="message"></div>
    <div class="actions">
        <a class="back" href="/partner/notifications">← Back to notifications</a>
    </div>
</div>

<script>
    const notificationId = @json($notificationId);
    const titleEl = document.getElementById('title');
    const metaEl = document.getElementById('meta');
    const messageEl = document.getElementById('message');

    async function markAsRead() {
        await fetch(`/api/admin/partner/notifications/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            credentials: 'same-origin'
        });
    }

    async function loadNotification() {
        const response = await fetch(`/api/admin/partner/notifications/${notificationId}`, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            titleEl.textContent = 'Notification not found';
            messageEl.textContent = 'تعذر تحميل تفاصيل الإشعار.';
            return;
        }

        const payload = await response.json();
        const item = payload.data ?? payload;

        titleEl.textContent = item.title ?? 'Notification';
        metaEl.textContent = `Created at: ${new Date(item.created_at).toLocaleString()}`;
        messageEl.textContent = item.message || item.summary || '';

        await markAsRead();
    }

    loadNotification();
</script>
</body>
</html>
