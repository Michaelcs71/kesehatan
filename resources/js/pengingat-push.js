function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const raw = window.atob(base64);
  return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}

async function aktifkanPengingat() {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    alert('Browser ini tidak mendukung notifikasi push. Anda tetap akan diingatkan via WhatsApp.');
    return;
  }

  const vapidPublic = document.querySelector('meta[name="vapid-public-key"]')?.content;
  if (!vapidPublic) return;

  const izin = await Notification.requestPermission();
  if (izin !== 'granted') return;

  const reg = await navigator.serviceWorker.register('/sw.js');
  const sub = await reg.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: urlBase64ToUint8Array(vapidPublic),
  });

  const json = sub.toJSON();
  await window.axios.post('/push/subscribe', {
    endpoint: json.endpoint,
    keys: { p256dh: json.keys.p256dh, auth: json.keys.auth },
  });

  alert('Pengingat via notifikasi telah diaktifkan.');
}

window.whenKesehatanReady &&
  window.whenKesehatanReady(() => {
    const btn = document.getElementById('btn-aktifkan-pengingat');
    if (btn) btn.addEventListener('click', aktifkanPengingat);
  });
