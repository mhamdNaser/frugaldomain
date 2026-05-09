<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Partner Notifications</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f6f7fb; }
        .topbar { display: flex; justify-content: flex-end; align-items: center; padding: 16px 24px; background: #fff; border-bottom: 1px solid #e5e7eb; position: relative; }
        .bell-btn { border: 1px solid #d1d5db; background: #fff; border-radius: 10px; padding: 8px 12px; cursor: pointer; position: relative; }
        .badge { position: absolute; top: -6px; right: -6px; background: #dc2626; color: #fff; font-size: 12px; border-radius: 999px; min-width: 20px; text-align: center; padding: 2px 6px; }
        .dropdown { position: absolute; top: 60px; right: 24px; width: 380px; max-height: 420px; overflow: auto; border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; box-shadow: 0 10px 25px rgba(0, 0, 0, .1); display: none; }
        .dropdown.open { display: block; }
        .item { display: block; padding: 12px 14px; border-bottom: 1px solid #f3f4f6; text-decoration: none; color: #111827; }
        .item:hover { background: #f9fafb; }
        .item-title { font-weight: 700; margin-bottom: 4px; }
        .item-summary { color: #4b5563; font-size: 14px; }
        .item-meta { margin-top: 6px; color: #9ca3af; font-size: 12px; }
        .item.unread .item-title::before { content: "• "; color: #2563eb; }
        .empty { padding: 14px; color: #6b7280; text-align: center; }
        .content { padding: 24px; color: #374151; }
    </style>
</head>
<body>
<div class="topbar">
    <button id="bellBtn" class="bell-btn" type="button">
        🔔
        <span id="unreadBadge" class="badge" style="display:none;">0</span>
    </button>
    <div id="dropdown" class="dropdown"></div>
</div>

<div class="content">
    <h2>Partner Dashboard</h2>
    <p>اضغط على زر الجرس لمشاهدة ملخص الإشعارات.</p>
</div>

<script>
    const bellBtn = document.getElementById('bellBtn');
    const dropdown = document.getElementById('dropdown');
    const unreadBadge = document.getElementById('unreadBadge');

    async function fetchNotifications() {
        const response = await fetch('/api/admin/partner/notifications?per_page=10', {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            dropdown.innerHTML = '<div class="empty">تعذر تحميل الإشعارات.</div>';
            return;
        }

        const payload = await response.json();
        const notifications = payload.data || [];
        const unreadCount = payload.meta?.unread_count || 0;

        if (unreadCount > 0) {
            unreadBadge.style.display = 'inline-block';
            unreadBadge.textContent = unreadCount;
        } else {
            unreadBadge.style.display = 'none';
        }

        if (!notifications.length) {
            dropdown.innerHTML = '<div class="empty">لا يوجد إشعارات حالياً.</div>';
            return;
        }

        dropdown.innerHTML = notifications.map(item => `
            <a class="item ${item.is_read ? '' : 'unread'}" href="/partner/notifications/${item.id}">
                <div class="item-title">${item.title ?? 'Notification'}</div>
                <div class="item-summary">${item.summary ?? ''}</div>
                <div class="item-meta">${new Date(item.created_at).toLocaleString()}</div>
            </a>
        `).join('');
    }

    bellBtn.addEventListener('click', async () => {
        dropdown.classList.toggle('open');
        if (dropdown.classList.contains('open')) {
            await fetchNotifications();
        }
    });
</script>
</body>
</html>
