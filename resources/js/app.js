import './bootstrap';
import QRCode from 'qrcode';
import jsQR from 'jsqr';

// ─── Modal references ─────────────────────────────────────────────────────────
const scannerModal   = document.querySelector('#scanner-modal');
const memberModal    = document.querySelector('#member-modal');
const approvalsModal = document.querySelector('#approvals-modal');
const qrViewModal    = document.querySelector('#qr-view-modal');
const reportDetailsModal = document.querySelector('#report-details-modal');
let pendingReportUrl = null;

// ─── Scanner state ─────────────────────────────────────────────────────────────
let video    = null;
let canvas   = null;
let ctx      = null;
let raf      = null;
let scanTimer = null;
let active   = false;
let busy     = false;
let lastVal  = null;
let audioCtx = null;
let scanCanvas = null;
let scanCtx = null;
let processedCanvas = null;
let processedCtx = null;
let stateTimer = null;
let barcodeDetector = null;

// ─── Get session ID from DOM (never cached) ────────────────────────────────────
function sid() {
    const v =
        document.body.dataset.sessionId ||
        document.querySelector('[data-session-id]')?.dataset.sessionId ||
        '';
    return v && v !== 'null' ? v : null;
}

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? null;
}

function cameraErrorMessage(error) {
    const message = String(error?.message || error || '').trim();
    if (/NotReadableError|device in use|already in use/i.test(message)) return 'Camera is already in use by another app or tab.';
    if (/NotAllowedError|permission/i.test(message)) return 'Camera permission was denied. Allow camera access and retry.';
    if (/NotFoundError|no camera/i.test(message)) return 'No camera was found on this device.';
    return message || 'Camera could not be started. Check camera permission and retry.';
}

function appUrl(path) {
    const base = document.body.dataset.appUrl || window.location.origin;
    return `${base.replace(/\/$/, '')}/${path.replace(/^\//, '')}`;
}

// ─── Beep ─────────────────────────────────────────────────────────────────────
function beep(hz, len = 0.12) {
    try {
        audioCtx = audioCtx || new AudioContext();
        const o = audioCtx.createOscillator(), g = audioCtx.createGain();
        o.frequency.value = hz;
        g.gain.setValueAtTime(0.1, audioCtx.currentTime);
        g.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + len);
        o.connect(g).connect(audioCtx.destination);
        o.start(); o.stop(audioCtx.currentTime + len);
    } catch (_) {}
}

// ─── Status message in scanner modal ──────────────────────────────────────────
function setStatus(msg, color) {
    let el = document.getElementById('scan-result-banner');
    if (!el) {
        el = document.createElement('div');
        el.id = 'scan-result-banner';
        el.className = 'scanner-result';
        const frame = document.querySelector('.camera-frame');
        if (frame) frame.append(el);
    }
    el.textContent = msg;
    el.style.background = color;
    el.style.color = '#fff';
    el.style.opacity = '1';
    clearTimeout(el._t);
    el._t = setTimeout(() => { el.style.opacity = '0'; }, 2800);
}

function setScanState(state, duration = 2200) {
    const guide = document.querySelector('.camera-guide');
    if (!guide) return;
    guide.classList.remove('scan-success', 'scan-error', 'scan-duplicate');
    if (state) guide.classList.add(`scan-${state}`);
    clearTimeout(stateTimer);
    if (state) {
        stateTimer = setTimeout(() => {
            guide.classList.remove('scan-success', 'scan-error', 'scan-duplicate');
        }, duration);
    }
}

// ─── jsQR scan loop ────────────────────────────────────────────────────────────
function normalizeQrValue(value) {
    const raw = String(value || '').trim();
    if (!raw) return '';

    try {
        const object = JSON.parse(raw);
        const nested = object.qr_token || object.token || object.member_token || object.data;
        if (nested && nested !== raw) return normalizeQrValue(nested);
    } catch (_) {}

    try {
        const parsed = new URL(raw);
        const queryToken = parsed.searchParams.get('qr_token') || parsed.searchParams.get('token');
        if (queryToken) return decodeURIComponent(queryToken);
        const segments = parsed.pathname.split('/').filter(Boolean);
        return segments.length ? decodeURIComponent(segments.at(-1)) : raw;
    } catch (_) {
        return raw;
    }
}

function readQr(canvasToRead, width, height) {
    const image = canvasToRead.getContext('2d', { willReadFrequently: true })
        .getImageData(0, 0, width, height);
    return jsQR(image.data, width, height, { inversionAttempts: 'attemptBoth' });
}

function readQrFromRegion(source, left, top, sourceWidth, sourceHeight, width, height) {
    processedCanvas.width = width;
    processedCanvas.height = height;
    processedCtx.drawImage(source, left, top, sourceWidth, sourceHeight, 0, 0, width, height);
    return readQr(processedCanvas, width, height);
}

function readThresholdedQr(source, left, top, sourceWidth, sourceHeight, threshold) {
    const width = Math.max(1, Math.round(sourceWidth));
    const height = Math.max(1, Math.round(sourceHeight));
    processedCanvas.width = width;
    processedCanvas.height = height;
    processedCtx.drawImage(source, left, top, sourceWidth, sourceHeight, 0, 0, width, height);
    const pixels = processedCtx.getImageData(0, 0, width, height);
    for (let index = 0; index < pixels.data.length; index += 4) {
        const gray = 0.299 * pixels.data[index] + 0.587 * pixels.data[index + 1] + 0.114 * pixels.data[index + 2];
        const value = gray > threshold ? 255 : 0;
        pixels.data[index] = value;
        pixels.data[index + 1] = value;
        pixels.data[index + 2] = value;
    }
    processedCtx.putImageData(pixels, 0, 0);
    return readQr(processedCanvas, width, height);
}

function handleDecodedQr(value) {
    const token = normalizeQrValue(value);
    if (!token || busy || token === lastVal) return;

    busy = true;
    lastVal = token;
    beep(880);
    const guide = document.querySelector('.camera-guide');
    if (guide) {
        guide.classList.remove('scan-detected');
        void guide.offsetWidth;
        guide.classList.add('scan-detected');
    }
    checkin(token);
}

function scanFrame() {
    if (!active || busy || !video || video.readyState < 2 || !video.videoWidth) return;

    if (barcodeDetector) {
        barcodeDetector.detect(video).then(codes => {
            if (codes[0]?.rawValue) handleDecodedQr(codes[0].rawValue);
        }).catch(() => {});
    }

    const width = video.videoWidth;
    const height = video.videoHeight;
    scanCanvas.width = width;
    scanCanvas.height = height;
    scanCtx.drawImage(video, 0, 0, width, height);

    // Native, contrast, cropped, downscaled, and thresholded passes handle glare and poor lighting.
    let code = readQr(scanCanvas, width, height);
    if (!code) {
        processedCanvas.width = width;
        processedCanvas.height = height;
        processedCtx.filter = 'contrast(1.5) brightness(1.1)';
        processedCtx.drawImage(scanCanvas, 0, 0, width, height);
        processedCtx.filter = 'none';
        code = readQr(processedCanvas, width, height);
    }
    if (!code) {
        const cropSize = Math.min(width, height, 720);
        const left = Math.floor((width - cropSize) / 2);
        const top = Math.floor((height - cropSize) / 2);
        code = readQrFromRegion(scanCanvas, left, top, cropSize, cropSize, cropSize, cropSize);
    }
    if (!code) {
        const cropWidth = Math.round(width * 0.82);
        const cropHeight = Math.round(height * 0.82);
        const left = Math.floor((width - cropWidth) / 2);
        const top = Math.floor((height - cropHeight) / 2);
        for (const threshold of [80, 110, 145, 180]) {
            code = readThresholdedQr(scanCanvas, left, top, cropWidth, cropHeight, threshold);
            if (code) break;
        }
    }
    if (!code) {
        const scaledWidth = Math.max(1, Math.round(width * 0.5));
        const scaledHeight = Math.max(1, Math.round(height * 0.5));
        processedCanvas.width = scaledWidth;
        processedCanvas.height = scaledHeight;
        processedCtx.drawImage(scanCanvas, 0, 0, scaledWidth, scaledHeight);
        code = readQr(processedCanvas, scaledWidth, scaledHeight);
    }
    if (!code) {
        code = readThresholdedQr(scanCanvas, 0, 0, width, height, 128);
    }

    if (code?.data) handleDecodedQr(code.data);
}

function loop() {
    if (!active) return;
    scanFrame();
    raf = requestAnimationFrame(loop);
}

// ─── Check in via API ──────────────────────────────────────────────────────────
async function checkin(token) {
    const sessionId = sid();
    const csrfToken = csrf();

    // Log to console so you can see what's happening
    console.log('▶ QR scanned:', token);
    console.log('▶ session_id:', sessionId);
    console.log('▶ csrf:', csrfToken ? 'present' : 'MISSING');

    if (!sessionId) {
        setScanState('error');
        setStatus('⚠ No active session found', '#f59e0b');
        unlock(2000);
        return;
    }
    if (!csrfToken) {
        setScanState('error');
        setStatus('⚠ Page error — please refresh', '#ef4444');
        unlock(2000);
        return;
    }

    setStatus('Checking in…', '#3b82f6');

    try {
        const r = await fetch(appUrl('/api/attendance/check-ins'), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ session_id: sessionId, qr_token: token }),
        });

        const contentType = r.headers.get('content-type') || '';
        const d = contentType.includes('application/json')
            ? await r.json()
            : { status: 'failed', message: `Server returned HTTP ${r.status}. Please sign in again.` };
        console.log('▶ API response:', r.status, d);

        if (d.status === 'success') {
            beep(1047, 0.15);
            setScanState('success');
            setStatus('✓ ' + d.member.name + ' — Present!', '#10b981');
            await refreshList();
        } else if (d.status === 'already_attended') {
            beep(440, 0.2);
            setScanState('duplicate');
            setStatus('⚠ ' + d.message, '#f59e0b');
        } else {
            beep(220, 0.2);
            setScanState('error');
            setStatus('✗ ' + (d.message || 'QR not recognised'), '#ef4444');
        }
    } catch (e) {
        console.error('▶ fetch error:', e);
        setScanState('error');
        setStatus('✗ Network error', '#ef4444');
    }

    unlock(1500);
}

function unlock(ms) {
    setTimeout(() => { busy = false; lastVal = null; }, ms);
}

// ─── Refresh attendee list ─────────────────────────────────────────────────────
async function refreshList() {
    try {
        const r = await fetch(appUrl('/api/attendance/dashboard'), { headers: { Accept: 'application/json' } });
        if (!r.ok) return;
        const d = await r.json();
        if (!d.active_session) return;

        document.querySelectorAll('#attendance-count, #attendance-count-secondary')
            .forEach(el => (el.textContent = d.today_count));

        const list = document.querySelector('#attendee-list');
        if (list && d.recent_check_ins?.length) {
            list.innerHTML = d.recent_check_ins.map(x =>
                `<div class="attendee-row">
                    <strong>${x.member.name}</strong>
                    <span>${new Date(x.checked_in_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                    <em>Present</em>
                </div>`).join('');
        }
        const roster = document.querySelector('#scanner-roster-list');
        if (roster) {
            roster.innerHTML = d.recent_check_ins?.length
                ? d.recent_check_ins.map(x => `<div class="scanner-roster-item"><strong>${x.member.name}</strong><span>${new Date(x.checked_in_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span></div>`).join('')
                : '<p class="muted">Waiting for first scan...</p>';
            document.querySelector('#scanner-roster-count')?.replaceChildren(document.createTextNode(String(d.today_count)));
        }
    } catch (_) {}
}
setInterval(refreshList, 3000);

document.querySelector('#manual-attendance-form')?.addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget;
    const member = form.querySelector('#manual-member');
    const button = form.querySelector('button');
    if (!member?.value || !button) return;

    button.disabled = true;
    try {
        const response = await fetch(appUrl('/api/attendance/manual-check-ins'), {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({ session_id: sid(), member_id: member.value }),
        });
        const data = await response.json();
        if (data.status === 'success') {
            setScanState('success');
            setStatus('✓ ' + data.member.name + ' — Present!', '#10b981');
            member.value = '';
            await refreshList();
        } else {
            setScanState(data.status === 'already_attended' ? 'duplicate' : 'error');
            setStatus((data.status === 'already_attended' ? '⚠ ' : '✗ ') + (data.message || 'Could not mark attendance.'), data.status === 'already_attended' ? '#f59e0b' : '#ef4444');
        }
    } catch (_) {
        setScanState('error');
        setStatus('✗ Manual attendance request failed.', '#ef4444');
    } finally {
        button.disabled = false;
    }
});

// ─── Start camera ──────────────────────────────────────────────────────────────
async function startCamera() {
    if (active) return;

    const guide = document.querySelector('.camera-guide');
    if (!guide) return;

    await stopCamera();

    const reader = document.createElement('div');
    reader.id = 'qr-reader';
    reader.style.cssText = 'position:absolute;inset:0;z-index:0;overflow:hidden;';
    guide.prepend(reader);

    video = document.createElement('video');
    video.autoplay = true;
    video.muted = true;
    video.playsInline = true;
    video.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block;';
    reader.append(video);
    canvas = document.createElement('canvas');
    ctx = canvas.getContext('2d', { willReadFrequently: true });
    scanCanvas = canvas;
    scanCtx = ctx;
    processedCanvas = document.createElement('canvas');
    processedCtx = processedCanvas.getContext('2d', { willReadFrequently: true });

    guide.style.position = 'relative';

    // Style the hint label on top of the video
    const label = guide.querySelector('#scanner-message');
    if (label) {
        label.classList.remove('camera-error');
        label.style.cssText =
            'position:absolute;bottom:10px;left:50%;transform:translateX(-50%);' +
            'background:rgba(0,0,0,.55);color:#fff;padding:4px 12px;border-radius:4px;' +
            'font-size:11px;z-index:20;pointer-events:none;white-space:nowrap;';
        label.textContent = 'Starting camera…';
    }

    const cameraBottom = guide.parentElement?.querySelector('.camera-bottom');
    let retry = document.querySelector('#scanner-retry');
    if (!retry && cameraBottom) {
        retry = document.createElement('button');
        retry.id = 'scanner-retry';
        retry.type = 'button';
        retry.className = 'scan-now';
        retry.textContent = 'Retry camera';
        cameraBottom.append(retry);
        retry.addEventListener('click', async () => {
            retry.classList.remove('visible');
            await startCamera();
        });
    }
    retry?.classList.remove('visible');

    try {
        if (!navigator.mediaDevices?.getUserMedia) {
            throw new Error('Camera access requires HTTPS or localhost');
        }

        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 }, resizeMode: 'none' },
            audio: false,
        });
        video.srcObject = stream;
        await video.play();
        const track = stream.getVideoTracks()[0];
        try {
            await track.applyConstraints({ advanced: [{ focusMode: 'continuous', exposureMode: 'continuous', whiteBalanceMode: 'continuous' }] });
        } catch (_) {}
        await new Promise(resolve => {
            const waitForFrame = () => video.videoWidth > 0 ? resolve() : requestAnimationFrame(waitForFrame);
            waitForFrame();
        });
        active = true;
        if (label) label.textContent = 'Point at member QR card';
        scanTimer = setInterval(scanFrame, 120);
        setStatus('Scanner active — searching for QR...', '#3b82f6');
        console.log('▶ Direct QR scanner started:', video.videoWidth, '×', video.videoHeight);

    } catch (err) {
        console.error('▶ camera error:', err);
        video?.srcObject?.getTracks().forEach(track => track.stop());
        reader.remove();
        if (label) {
            label.textContent = cameraErrorMessage(err);
            label.classList.add('camera-error');
        }
        document.querySelector('#scanner-retry')?.classList.add('visible');
        setStatus(cameraErrorMessage(err), '#ef4444');
    }
}

// ─── Stop camera ───────────────────────────────────────────────────────────────
async function stopCamera() {
    active = false;
    if (raf) { cancelAnimationFrame(raf); raf = null; }
    if (scanTimer) { clearInterval(scanTimer); scanTimer = null; }
    if (video) {
        video.srcObject?.getTracks().forEach(t => t.stop());
        video.srcObject = null;
        video.remove();
        video = null;
    }
    canvas = null; ctx = null;
    scanCanvas = null; scanCtx = null;
    processedCanvas = null; processedCtx = null;
    barcodeDetector = null;
    document.querySelector('#qr-reader')?.remove();
    busy = false; lastVal = null;
    clearTimeout(stateTimer);
    document.querySelector('.camera-guide')?.classList.remove('scan-success', 'scan-error', 'scan-duplicate', 'scan-detected');
}

window.addEventListener('beforeunload', () => {
    if (video?.srcObject) video.srcObject.getTracks().forEach(track => track.stop());
});
// ─── Modal helpers ─────────────────────────────────────────────────────────────
const openModal  = m => { if (m) { m.classList.add('open');    m.setAttribute('aria-hidden', 'false'); } };
const closeModal = m => { if (m) { m.classList.remove('open'); m.setAttribute('aria-hidden', 'true');  } };

// ─── Open scanner ──────────────────────────────────────────────────────────────
document.querySelectorAll('.service-panel [data-open-scanner]').forEach(btn => {
    btn.removeAttribute('data-open-scanner');
    btn.dataset.viewLink = 'attendance';
    btn.type = 'button';
});

document.querySelectorAll('[data-open-scanner]').forEach(btn =>
    btn.addEventListener('click', async () => {
        openModal(scannerModal);
        busy = false; lastVal = null;
        await startCamera();
    })
);

document.querySelectorAll('[data-open-member]').forEach(btn =>
    btn.addEventListener('click', () => openModal(memberModal))
);

document.addEventListener('click', event => {
    const link = event.target.closest('.report-actions a');
    if (!link) return;
    event.preventDefault();
    event.stopPropagation();
    pendingReportUrl = new URL(link.href, window.location.origin);
    openModal(reportDetailsModal);
});
document.querySelector('#continue-report-download')?.addEventListener('click', () => {
    const name = document.querySelector('#report-church-name')?.value.trim();
    const location = document.querySelector('#report-church-location')?.value.trim();
    if (!name || !location || !pendingReportUrl) return;
    pendingReportUrl.searchParams.set('church_name', name);
    pendingReportUrl.searchParams.set('church_location', location);
    closeModal(reportDetailsModal);
    window.location.href = pendingReportUrl.toString();
});

// ─── Close modals ──────────────────────────────────────────────────────────────
document.querySelectorAll('[data-close-modal]').forEach(btn =>
    btn.addEventListener('click', () => {
        closeModal(scannerModal); closeModal(memberModal);
        closeModal(qrViewModal);  closeModal(approvalsModal); closeModal(reportDetailsModal);
        stopCamera();
    })
);

document.querySelectorAll('.modal-backdrop').forEach(bd =>
    bd.addEventListener('click', e => { if (e.target === bd) { closeModal(bd); stopCamera(); } })
);

document.addEventListener('click', e => {
    if (e.target.closest('#approvals-modal [data-close-modal]')) {
        e.preventDefault(); closeModal(approvalsModal);
    }
});

// ─── QR view modal ─────────────────────────────────────────────────────────────
async function showMemberQr(btn) {
    const target = document.querySelector('#qr-view-code');
    if (!target) return;
    target.innerHTML = '';
    const c = document.createElement('canvas');
    await QRCode.toCanvas(c, btn.dataset.token, { width: 210, margin: 2, color: { dark: '#111', light: '#fff' } });
    target.append(c);
    document.querySelector('#qr-view-name').textContent        = btn.dataset.name;
    document.querySelector('#qr-view-member-code').textContent = `${btn.dataset.code} · Unique member code`;
    openModal(qrViewModal);
}
document.querySelector('#member-cards')?.addEventListener('click', e => {
    const btn = e.target.closest('.view-qr');
    if (btn) showMemberQr(btn);
});

// ─── Add member & generate QR ──────────────────────────────────────────────────
document.querySelector('#member-name')?.addEventListener('input', e => {
    const p = document.querySelector('#qr-member-name');
    if (p) p.textContent = e.target.value || 'New member';
});

document.querySelector('#generate-member')?.addEventListener('click', async () => {
    const btn = document.querySelector('#generate-member');
    btn.disabled = true;
    const res = await fetch(appUrl('/api/members'), {
        method: 'POST',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify({ name: document.querySelector('#member-name').value, email: document.querySelector('#member-email').value }),
    });
    if (res.ok) {
        const m = await res.json();
        const qr = document.querySelector('#member-qr');
        qr.innerHTML = '';
        const c = document.createElement('canvas');
        await QRCode.toCanvas(c, m.qr_token, { width: 130, margin: 1, color: { dark: '#111', light: '#fff' } });
        qr.append(c);
        document.querySelector('#member-title').textContent   = 'QR card ready';
        document.querySelector('#qr-member-name').textContent = m.name;
        document.querySelector('#qr-member-code').textContent = `${m.member_code} · Scan to check in`;
        const cards = document.querySelector('#member-cards');
        cards?.querySelector('.muted')?.remove();
        if (cards) {
            const row = document.createElement('div');
            row.className = 'directory-row';
            row.dataset.memberId = m.id;
            row.innerHTML =
                '<div class="member-cell"><div class="member-avatar"></div><strong></strong></div>' +
                '<span></span><span class="tag">QR active</span>' +
                '<div class="row-actions"><button class="row-action view-qr" type="button">QR</button>' +
                '<button class="row-action delete-member" type="button">×</button></div>';
            row.querySelector('.member-avatar').textContent = m.name.split(' ').map(p => p[0]).join('').slice(0, 2);
            row.querySelector('strong').textContent         = m.name;
            row.querySelector('span:not(.tag)').textContent = `Member since ${new Date().getFullYear()}`;
            row.querySelector('.view-qr').dataset.name  = m.name;
            row.querySelector('.view-qr').dataset.code  = m.member_code;
            row.querySelector('.view-qr').dataset.token = m.qr_token;
            row.querySelector('.delete-member').dataset.url = `/api/members/${m.id}`;
            cards.prepend(row);
            const cnt = document.querySelector('#member-count');
            if (cnt) cnt.textContent = `${cards.querySelectorAll('.directory-row').length} active members`;
        }
        btn.innerHTML = 'Member added <span>✓</span>';
    } else {
        btn.innerHTML = 'Could not add member';
    }
    btn.disabled = false;
});

document.querySelector('#print-card')?.addEventListener('click', () => window.print());
document.querySelector('#print-existing-card')?.addEventListener('click', () => window.print());

// ─── Delete member ─────────────────────────────────────────────────────────────
function bindMemberActions(scope = document) {
    scope.querySelectorAll('.delete-member').forEach(btn =>
        btn.addEventListener('click', async () => {
            if (!confirm('Delete this member and their attendance history?')) return;
            const url = btn.dataset.url || `/api/members/${btn.closest('.directory-row').dataset.memberId}`;
            const res = await fetch(url, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() } });
            if (res.ok) {
                btn.closest('.directory-row').remove();
                const cnt = document.querySelector('#member-count');
                if (cnt) cnt.textContent = `${document.querySelectorAll('#member-cards .directory-row').length} active members`;
            }
        })
    );
}
bindMemberActions();

// ─── Approve users ─────────────────────────────────────────────────────────────
document.querySelectorAll('.approve-user').forEach(btn =>
    btn.addEventListener('click', async () => {
        btn.disabled = true;
        const res = await fetch(btn.dataset.url, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() } });
        if (res.ok) {
            btn.closest('.approval-row').remove();
            if (!document.querySelector('.approval-row'))
                document.querySelector('#approval-list').innerHTML = '<p class="muted">No leader accounts are waiting for approval.</p>';
        }
        btn.disabled = false;
    })
);

// ─── Misc UI ───────────────────────────────────────────────────────────────────
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[character]);
}

function renderDashboardData() {
    const dataElement = document.querySelector('#dashboard-data');
    if (!dataElement) return;
    let data;
    try {
        data = JSON.parse(dataElement.textContent || '{}');
    } catch (_) {
        return;
    }
    const sessions = data.sessions || [];
    const checkIns = data.checkIns || [];
    const memberCount = document.body.dataset.memberCount;
    if (memberCount) {
        document.querySelector('.stat-card:nth-child(3) > strong')?.replaceChildren(document.createTextNode(memberCount));
        const memberLabel = document.querySelector('#member-count');
        if (memberLabel) memberLabel.textContent = `${memberCount} active members`;
    }
    const trend = data.trend || [];
    const maximum = Math.max(1, ...trend.map(day => Number(day.count) || 0));
    const points = trend.map((day, index) => `${(index / Math.max(1, trend.length - 1)) * 700},${200 - ((day.count / maximum) * 180)}`).join(' ');
    const chart = document.querySelector('.chart svg');
    if (chart) chart.innerHTML = `<polyline class="line" points="${points}"/><polygon class="area" points="${points} 700,210 0,210"/>`;
    document.querySelectorAll('.stat-foot').forEach((footer, index) => {
        const value = footer.querySelector('span:last-child');
        if (!value) return;
        if (index === 0) value.textContent = `${data.todayCount ? Math.round((data.todayCount / Math.max(1, maximum)) * 100) : 0}%`;
        if (index === 1) value.textContent = `${sessions.length ? Math.round((sessions.filter(session => session.count > 0).length / sessions.length) * 100) : 0}%`;
        if (index === 3) value.textContent = `${data.memberCount ? Math.round((data.todayCount / data.memberCount) * 100) : 0}%`;
    });
    const serviceList = document.querySelector('.service-list');
    if (serviceList) {
        serviceList.innerHTML = sessions.length ? sessions.map(session => `<div class="service-row"><div class="service-time">${escapeHtml(session.time)}</div><div class="service-info"><strong>${escapeHtml(session.name)}</strong><span>${escapeHtml(session.location)} · ${escapeHtml(session.type)}</span></div><div class="service-count">${session.count || '—'} <small>${session.count ? 'present' : 'upcoming'}</small></div></div>`).join('') : '<p class="muted empty-service">No sessions scheduled today.</p>';
    }
    const activityList = document.querySelector('#activity-list');
    if (activityList) {
        activityList.innerHTML = checkIns.length ? checkIns.map(checkIn => `<div class="activity-row"><div class="member-cell"><div class="member-avatar">${escapeHtml(checkIn.name.split(' ').map(part => part[0]).join(''))}</div><strong>${escapeHtml(checkIn.name)}</strong></div><span>${escapeHtml(checkIn.session)}</span><span>${escapeHtml(checkIn.time)}</span><em>Present</em></div>`).join('') : '<p class="muted empty-service">No check-ins recorded today.</p>';
    }
}
renderDashboardData();

async function refreshAdminDashboard() {
    if (!document.body.classList.contains('admin-dashboard')) return;
    try {
        const response = await fetch(appUrl('/api/admin/dashboard'), { headers: { Accept: 'application/json' } });
        if (!response.ok) return;
        const liveData = await response.json();
        const currentData = {
            ...liveData,
            todayCount: liveData.today_count,
            memberCount: liveData.member_count,
            checkIns: (liveData.recent_check_ins || []).map(record => ({
                name: record.member?.name || 'Unknown member',
                session: record.session?.name || 'Unknown session',
                time: new Date(record.checked_in_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
            })),
        };
        document.querySelector('#attendance-count')?.replaceChildren(document.createTextNode(String(liveData.today_count)));
        document.querySelector('.stat-card:nth-child(3) > strong')?.replaceChildren(document.createTextNode(String(liveData.member_count)));
        const rate = document.querySelector('.stat-card:nth-child(4) > strong');
        if (rate) rate.replaceChildren(document.createTextNode(String(liveData.checkin_rate)), Object.assign(document.createElement('span'), { className: 'unit', textContent: '%' }));
        const dataElement = document.querySelector('#dashboard-data');
        if (dataElement) dataElement.textContent = JSON.stringify(currentData);
        renderDashboardData();
    } catch (_) {}
}
setInterval(refreshAdminDashboard, 3000);

const dashboardViews = document.querySelectorAll('[data-page]');
const currentDashboardView = document.body.dataset.currentView || 'overview';
dashboardViews.forEach(view => view.classList.toggle('hidden', view.dataset.page !== currentDashboardView));
document.querySelector('.admin-dashboard .history-pagination:not(.attendance-pagination)')?.remove();
document.querySelectorAll('.admin-dashboard .attendance-pagination').forEach((pagination, index) => { if (index > 0) pagination.remove(); });
const showDashboardView = viewName => {
    window.location.href = `${window.location.pathname}?view=${encodeURIComponent(viewName)}`;
};
document.querySelectorAll('[data-view]').forEach(item => item.addEventListener('click', () => showDashboardView(item.dataset.view)));
document.querySelectorAll('[data-view-link]').forEach(item => item.addEventListener('click', () => showDashboardView(item.dataset.viewLink)));
document.querySelector('[data-open-approvals]')?.addEventListener('click', () => openModal(approvalsModal));
document.querySelector('.dashboard-view-reports [data-page="reports"] .page-heading .button-light')?.remove();

document.querySelector('.mobile-menu')?.addEventListener('click', () =>
    document.querySelector('.sidebar')?.classList.toggle('open'));

document.querySelector('[data-view-attendees]')?.addEventListener('click', () =>
    document.querySelector('#attendee-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));

document.querySelector('[data-view-history]')?.addEventListener('click', () =>
    document.querySelector('#leader-history')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));

document.querySelector('[data-end-session]')?.addEventListener('click', async e => {
    if (!confirm('End this attendance session?')) return;
    const btn = e.currentTarget; btn.disabled = true;
    const res = await fetch(btn.dataset.url, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() } });
    if (res.ok) window.location.reload();
    btn.disabled = false;
});

document.querySelectorAll('.password-toggle').forEach(btn =>
    btn.addEventListener('click', () => {
        const inp = btn.parentElement.querySelector('input');
        inp.type = inp.type === 'password' ? 'text' : 'password';
        btn.textContent = inp.type === 'password' ? 'Show' : 'Hide';
    })
);

// ─── Logout modal ──────────────────────────────────────────────────────────────
let logoutForm, logoutModal;
function buildLogoutModal(form) {
    logoutForm = form;
    if (!logoutModal) {
        logoutModal = document.createElement('div');
        logoutModal.className = 'modal-backdrop';
        logoutModal.innerHTML =
            '<section class="modal logout-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="logout-title">' +
            '<button class="modal-close" type="button" aria-label="Close">×</button>' +
            '<span class="section-kicker">Secure session</span>' +
            '<h2 id="logout-title">Log out of Gather?</h2>' +
            '<p class="muted">Your session will end on this device.</p>' +
            '<div class="modal-footer">' +
            '<button class="button button-light logout-cancel" type="button">Cancel</button>' +
            '<button class="button button-dark logout-continue" type="button">Log out</button>' +
            '</div></section>';
        document.body.append(logoutModal);
        logoutModal.querySelector('.modal-close').addEventListener('click',     () => closeModal(logoutModal));
        logoutModal.querySelector('.logout-cancel').addEventListener('click',   () => closeModal(logoutModal));
        logoutModal.querySelector('.logout-continue').addEventListener('click', () => logoutForm.submit());
        logoutModal.addEventListener('click', e => { if (e.target === logoutModal) closeModal(logoutModal); });
    }
    openModal(logoutModal);
}
document.querySelectorAll('.logout-form').forEach(f =>
    f.addEventListener('submit', e => { e.preventDefault(); buildLogoutModal(f); }));

// ─── Duration control ──────────────────────────────────────────────────────────
document.querySelectorAll('[data-duration]').forEach(btn =>
    btn.addEventListener('click', () => {
        const v = document.querySelector('#duration-value');
        const l = document.querySelector('#duration-label');
        if (!v || !l) return;
        const n = Math.max(15, Math.min(720, Number(v.value) + Number(btn.dataset.duration)));
        v.value = n; l.textContent = `${n} min`;
    })
);

// ─── Auto-open scanner on session-just-started ─────────────────────────────────
if (document.body.dataset.autoOpenScanner === 'true' && scannerModal)
    document.querySelector('[data-open-scanner]')?.click();
