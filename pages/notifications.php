<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Notifications – CMP</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
</head>
<body>
<div class="topbar">
  <div class="topbar-brand">
    <div class="topbar-logo"><i class="fas fa-bell"></i></div>
    <div>
      <div class="topbar-title">Notifications & Alerts</div>
      <div class="topbar-subtitle">Email · SMS · Push Notifications</div>
    </div>
  </div>
  <div class="topbar-right">
    <a href="contractor-dashboard.php" class="btn btn-outline btn-sm" style="color:white;border-color:rgba(255,255,255,0.3)"><i class="fas fa-home"></i> Dashboard</a>
    <div class="user-avatar">RC</div>
  </div>
</div>

<div class="page-container">
  <div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between">
    <div>
      <div class="page-title">Notification Centre</div>
      <div class="page-subtitle">All Email, SMS, and Push Notifications across the contractor lifecycle</div>
    </div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-outline btn-sm" onclick="markAllRead()"><i class="fas fa-check-double"></i> Mark All Read</button>
      <button class="btn btn-outline btn-sm"><i class="fas fa-cog"></i> Settings</button>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card">
      <div class="stat-icon" style="background:#fee2e2;color:var(--danger)"><i class="fas fa-bell"></i></div>
      <div class="stat-value" id="unreadCount" data-dynamic>—</div><div class="stat-label">Unread</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#dbeafe;color:var(--primary)"><i class="fas fa-envelope"></i></div>
      <div class="stat-value">12</div><div class="stat-label">Email Sent</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#d1fae5;color:var(--success)"><i class="fas fa-sms"></i></div>
      <div class="stat-value">18</div><div class="stat-label">SMS Sent</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-mobile-alt"></i></div>
      <div class="stat-value">9</div><div class="stat-label">Push Sent</div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 360px;gap:20px">
    <!-- Notification List -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-inbox"></i> All Notifications</div>
        <div style="display:flex;gap:8px">
          <select class="form-control" style="width:160px" onchange="filterNotifications(this.value)">
            <option value="all">All Types</option>
            <option value="email">Email</option>
            <option value="sms">SMS</option>
            <option value="push">Push</option>
            <option value="system">System</option>
          </select>
        </div>
      </div>
      <div id="notification-list">
        <!-- Dynamic notifications will load here -->
        <div style="text-align:center;padding:60px;color:var(--text-mid);">
          <i class="fas fa-spinner fa-spin" style="font-size:48px;"></i>
          <div style="margin-top:20px;font-size:18px;">Loading notifications...</div>
        </div>
      </div>
    </div>

    <!-- Notification Channels & Settings -->
    <div>
      <!-- Channel Status -->
      <div class="card" style="margin-bottom:16px">
        <div class="card-header"><div class="card-title"><i class="fas fa-broadcast-tower"></i> Notification Channels</div></div>
        <div class="card-body">
          <div class="info-row">
            <span class="info-label"><i class="fas fa-envelope" style="width:18px;color:var(--primary)"></i> Email</span>
            <span class="info-value"><span class="badge badge-success">Active</span></span>
          </div>
          <div style="font-size:11px;color:var(--gray-400);margin-bottom:10px;padding-left:24px">ravi@raviconst.com</div>
          <div class="info-row">
            <span class="info-label"><i class="fas fa-sms" style="width:18px;color:var(--success)"></i> SMS</span>
            <span class="info-value"><span class="badge badge-success">Active</span></span>
          </div>
          <div style="font-size:11px;color:var(--gray-400);margin-bottom:10px;padding-left:24px">+91 98765 43210</div>
          <div class="info-row">
            <span class="info-label"><i class="fas fa-mobile-alt" style="width:18px;color:#7c3aed"></i> Push</span>
            <span class="info-value"><span class="badge badge-success">Active</span></span>
          </div>
          <div style="font-size:11px;color:var(--gray-400);padding-left:24px">App Notifications Enabled</div>
        </div>
      </div>

      <!-- Recipients -->
      <div class="card" style="margin-bottom:16px">
        <div class="card-header"><div class="card-title"><i class="fas fa-users"></i> Notification Recipients</div></div>
        <div class="card-body">
          <div style="font-size:12px;color:var(--gray-600);margin-bottom:10px;font-weight:600">Teams Notified</div>
          <div style="display:flex;flex-direction:column;gap:8px">
            <div style="display:flex;align-items:center;gap:8px;font-size:12px"><div style="width:8px;height:8px;background:var(--success);border-radius:50%"></div><span>Welfare Team (Email + SMS)</span></div>
            <div style="display:flex;align-items:center;gap:8px;font-size:12px"><div style="width:8px;height:8px;background:var(--success);border-radius:50%"></div><span>Safety Team (Email + SMS)</span></div>
            <div style="display:flex;align-items:center;gap:8px;font-size:12px"><div style="width:8px;height:8px;background:var(--success);border-radius:50%"></div><span>Contractor (All Channels)</span></div>
            <div style="display:flex;align-items:center;gap:8px;font-size:12px"><div style="width:8px;height:8px;background:var(--primary);border-radius:50%"></div><span>Pass Issuing Officer (Push)</span></div>
            <div style="display:flex;align-items:center;gap:8px;font-size:12px"><div style="width:8px;height:8px;background:#7c3aed;border-radius:50%"></div><span>Executing Officer (Email)</span></div>
          </div>
        </div>
      </div>

      <!-- Send Manual Notification -->
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-paper-plane"></i> Send Notification</div></div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">To</label>
            <select class="form-control">
              <option>Welfare Team</option>
              <option>Safety Team</option>
              <option>Contractor</option>
              <option>Pass Issuing Officer</option>
              <option>All</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Channel</label>
            <div style="display:flex;gap:10px">
              <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer"><input type="checkbox" checked /> Email</label>
              <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer"><input type="checkbox" checked /> SMS</label>
              <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer"><input type="checkbox" /> Push</label>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Message</label>
            <textarea class="form-control" rows="3" placeholder="Type your message..."></textarea>
          </div>
          <button class="btn btn-primary btn-full" onclick="sendManualNotif()"><i class="fas fa-paper-plane"></i> Send Now</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../js/navigation.js"></script>
<script>
  // Dynamic Notifications
  async function loadNotifications() {
    try {
      showToast('🔄', 'Loading notifications...', 2000);
      const response = await fetch('/clms/api/get_notifications.php', { credentials: 'include' });
      const data = await response.json();
      
      if (data.status === 'success') {
        renderNotifications(data.notifications);
        document.getElementById('unreadCount').textContent = data.notifications.filter(n => !n.is_read).length;
        showToast('✅', `Loaded ${data.notifications.length} notifications`);
      } else {
        throw new Error(data.message || 'Load failed');
      }
    } catch (error) {
      console.error('Notifications error:', error);
      document.getElementById('notification-list').innerHTML = '<div style="text-align:center;padding:60px;color:var(--danger);"><i class="fas fa-exclamation-triangle"></i><br><strong>Error loading notifications</strong><br><small>' + error.message + '</small></div>';
      showToast('❌', 'Notifications load failed: ' + error.message);
    }
  }

  function renderNotifications(notifications) {
    const container = document.getElementById('notification-list');
    if (!notifications || notifications.length === 0) {
      container.innerHTML = '<div style="text-align:center;padding:80px;color:var(--text-mid);"><i class="fas fa-inbox" style="font-size:64px;"></i><br><strong>No notifications</strong><br><small>Your notification list is empty</small></div>';
      return;
    }

    container.innerHTML = notifications.map(n => `
      <div class="notif-item ${n.is_read ? '' : 'unread'}" onclick="readNotif(this, ${n.id})">
        <div class="notif-icon" style="background:${
          n.notification_type === 'success' ? '#d1fae5' :
          n.notification_type === 'warning' ? '#fef3c7' :
          n.notification_type === 'error' ? '#fee2e2' : '#dbeafe'
        };">
          <span style="font-size:14px;">${n.icon}</span>
        </div>
        <div style="flex:1">
          <div style="font-size:13px;font-weight:600;color:var(--gray-800)">${n.title}</div>
          <div style="font-size:12px;color:var(--gray-500);margin-top:2px">${n.message}</div>
          <div style="display:flex;gap:8px;margin-top:6px;align-items:center;font-size:11px;color:var(--gray-400);">
            <span class="badge badge-${n.notification_type}" style="font-size:10px;">${n.notification_type.toUpperCase()}</span>
            <span>${n.created_at}</span>
            ${!n.is_read ? '<span style="font-size:10px;font-weight:600;color:var(--danger);">● Unread</span>' : ''}
          </div>
        </div>
        ${n.related_id ? `<a href="#related-${n.related_id}" class="btn btn-sm btn-primary" style="font-size:11px;">View</a>` : ''}
      </div>
    `).join('');
  }

  async function readNotif(el, id) {
    if (el.classList.contains('unread')) {
      try {
        el.classList.remove('unread');
        const formData = new FormData();
        formData.append('id', id);
        await fetch('/clms/api/mark_notification_read.php', {
          method: 'POST', 
          body: formData,
          credentials: 'include'
        });
        
        // Refresh page badges
        if (window.parent && window.parent.updateNotificationBadges) {
          window.parent.updateNotificationBadges();
        }
      } catch (error) {
        console.error('Mark read failed:', error);
      }
      
      const countEl = document.getElementById('unreadCount');
      let count = parseInt(countEl.textContent) - 1;
      countEl.textContent = Math.max(0, count);
    }
  }

  async function markAllRead() {
    try {
      const response = await fetch('/clms/api/mark_notification_read.php?all=1', {
        method: 'POST',
        credentials: 'include'
      });
      
      if (response.ok) {
        document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
        document.getElementById('unreadCount').textContent = '0';
        
        // Refresh parent badges
        if (window.parent && window.parent.updateNotificationBadges) {
          window.parent.updateNotificationBadges();
        }
        
        showToast('✅', 'All notifications marked as read');
      }
    } catch (error) {
      console.error('Mark all read failed:', error);
      showToast('❌', 'Failed to mark all read');
    }
  }

  function sendManualNotif() { showToast('✅', 'Manual notification sent!'); }
  function filterNotifications(type) { showToast(`📂 Showing ${type} notifications`, 'info'); }

  // Load on page ready
  document.addEventListener('DOMContentLoaded', loadNotifications);
</script>
</body>
</html>

