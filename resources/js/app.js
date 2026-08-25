import './bootstrap';
import QRCode from 'qrcode';
import { BrowserQRCodeReader } from '@zxing/browser';

const views = document.querySelectorAll('[data-page]');
const navItems = document.querySelectorAll('[data-view]');
const scannerModal = document.querySelector('#scanner-modal');
const memberModal = document.querySelector('#member-modal');
const approvalsModal = document.querySelector('#approvals-modal');
const qrViewModal = document.querySelector('#qr-view-modal');
const sessionId = document.querySelector('[data-session-id]')?.dataset.sessionId || document.body.dataset.sessionId;
if (document.body.classList.contains('admin-dashboard')) document.querySelectorAll('[data-open-scanner]').forEach((button) => button.remove());
let scanTimer;
let countdown = 3;
let cameraStream;
let qrDetector;
let scannerControls;
let lastQrValue;
let audioContext;

function beep(frequency, duration = 0.12) {
	try {
		audioContext ??= new AudioContext();
		const oscillator = audioContext.createOscillator();
		const gain = audioContext.createGain();
		oscillator.frequency.value = frequency;
		gain.gain.setValueAtTime(0.08, audioContext.currentTime);
		gain.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + duration);
		oscillator.connect(gain).connect(audioContext.destination);
		oscillator.start();
		oscillator.stop(audioContext.currentTime + duration);
	} catch (error) {
	}
}

function showScanMessage(message, tone) {
	const element = document.querySelector('#scanner-message');
	if (element) element.textContent = message;
	if (tone === 'success') beep(880);
	if (tone === 'already') beep(440, 0.2);
	if (tone === 'failed') { beep(220, 0.16); setTimeout(() => beep(180, 0.16), 150); }
}

function showView(name) {
	views.forEach((view) => view.classList.toggle('hidden', view.dataset.page !== name));
	navItems.forEach((item) => item.classList.toggle('active', item.dataset.view === name));
	document.querySelector('.breadcrumb strong').textContent = name[0].toUpperCase() + name.slice(1);
}

navItems.forEach((item) => item.addEventListener('click', () => showView(item.dataset.view)));
document.querySelectorAll('[data-view-link]').forEach((item) => item.addEventListener('click', () => showView(item.dataset.viewLink)));

function openModal(modal) {
	if (!modal) return;
	modal.classList.add('open');
	modal.setAttribute('aria-hidden', 'false');
}

function closeModal(modal) {
	if (!modal) return;
	modal.classList.remove('open');
	modal.setAttribute('aria-hidden', 'true');
}

async function startCamera() {
	const guide = document.querySelector('.camera-guide');
	let video = document.querySelector('#camera-preview');
	if (!video) {
		video = document.createElement('video');
		video.id = 'camera-preview';
		video.autoplay = true;
		video.muted = true;
		video.playsInline = true;
		video.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.58;';
		guide.prepend(video);
	}
	if (!navigator.mediaDevices?.getUserMedia) return;
	try {
		const reader = new BrowserQRCodeReader();
		scannerControls = await reader.decodeFromVideoDevice(undefined, video, async (result) => {
			if (result?.getText()) await submitQr(result.getText());
		});
	} catch (error) {
		guide.querySelector('span').textContent = 'Camera permission needed';
	}
}

function stopCamera() {
	scannerControls?.stop();
	scannerControls = undefined;
	cameraStream?.getTracks().forEach((track) => track.stop());
	cameraStream = undefined;
}

async function scanCamera() {
	return;
}

async function submitQr(value) {
	try {
		if (!value || value === lastQrValue) return;
		lastQrValue = value;
		if (!sessionId) return showScanMessage('No active attendance session', 'failed');
		const response = await fetch('/api/attendance/check-ins', { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify({ session_id: sessionId, qr_token: value }) });
		const result = await response.json();
		if (result.status === 'success') showScanMessage(`${result.member.name} checked in`, 'success');
		else if (result.status === 'already_attended') showScanMessage(result.message, 'already');
		else showScanMessage(result.message || 'QR code not recognized', 'failed');
		setTimeout(() => { lastQrValue = undefined; }, 1800);
	} catch (error) {
		showScanMessage('QR scan failed', 'failed');
		lastQrValue = undefined;
	}
}

async function refreshDashboard() {
	try {
		const response = await fetch('/api/attendance/dashboard', { headers: { Accept: 'application/json' } });
		if (!response.ok) return;
		const data = await response.json();
		if (!data.active_session) return;
		const count = data.today_count;
		document.querySelector('#attendance-count').textContent = count;
		const secondary = document.querySelector('#attendance-count-secondary');
		if (secondary) secondary.textContent = count;
		const list = document.querySelector('#attendee-list');
		if (list && data.recent_check_ins?.length) list.innerHTML = data.recent_check_ins.map((record) => `<div class="attendee-row"><strong>${record.member.name}</strong><span>${new Date(record.checked_in_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span><em>Present</em></div>`).join('');
	} catch (error) {
	}
}

setInterval(refreshDashboard, 3000);

document.querySelectorAll('[data-open-scanner]').forEach((button) => button.addEventListener('click', () => {
	openModal(scannerModal);
	startCamera();
	countdown = 3;
	clearInterval(scanTimer);
	scanTimer = setInterval(() => {
		countdown = countdown === 1 ? 3 : countdown - 1;
		document.querySelector('#scan-countdown').textContent = countdown;
		if (countdown === 3) scanCamera();
	}, 1000);
}));
document.querySelectorAll('[data-open-member]').forEach((button) => button.addEventListener('click', () => openModal(memberModal)));
document.querySelectorAll('[data-open-approvals]').forEach((button) => button.addEventListener('click', () => openModal(approvalsModal)));
async function showMemberQr(button) {
	const target = document.querySelector('#qr-view-code');
	target.innerHTML = '';
	const canvas = document.createElement('canvas');
	await QRCode.toCanvas(canvas, button.dataset.token, { width: 210, margin: 2, color: { dark: '#111111', light: '#ffffff' } });
	target.append(canvas);
	document.querySelector('#qr-view-name').textContent = button.dataset.name;
	document.querySelector('#qr-view-member-code').textContent = `${button.dataset.code} · Unique member code`;
	openModal(qrViewModal);
}

document.querySelector('#member-cards')?.addEventListener('click', (event) => {
	const button = event.target.closest('.view-qr');
	if (button) showMemberQr(button);
});
document.querySelectorAll('[data-close-modal]').forEach((button) => button.addEventListener('click', () => {
	closeModal(scannerModal);
	closeModal(memberModal);
	closeModal(qrViewModal);
	closeModal(approvalsModal);
	clearInterval(scanTimer);
	stopCamera();
}));
document.addEventListener('click', (event) => {
	const closeButton = event.target.closest('#approvals-modal [data-close-modal]');
	if (!closeButton) return;
	event.preventDefault();
	closeModal(approvalsModal);
});
document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.addEventListener('click', (event) => {
	if (event.target === backdrop) {
		closeModal(backdrop);
		clearInterval(scanTimer);
		stopCamera();
	}
}));
document.querySelector('#member-name')?.addEventListener('input', (event) => {
	document.querySelector('#qr-member-name').textContent = event.target.value || 'New member';
});
document.querySelector('#generate-member')?.addEventListener('click', async () => {
	const button = document.querySelector('#generate-member');
	button.disabled = true;
	const response = await fetch('/api/members', { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify({ name: document.querySelector('#member-name').value, email: document.querySelector('#member-email').value }) });
	if (response.ok) {
		const member = await response.json();
		const qr = document.querySelector('#member-qr');
		qr.innerHTML = '';
		const canvas = document.createElement('canvas');
		await QRCode.toCanvas(canvas, member.qr_token, { width: 130, margin: 1, color: { dark: '#111111', light: '#ffffff' } });
		qr.append(canvas);
		document.querySelector('#member-title').textContent = 'QR card ready';
		document.querySelector('#qr-member-name').textContent = member.name;
		document.querySelector('#qr-member-code').textContent = `${member.member_code} · Scan to check in`;
		const cards = document.querySelector('#member-cards');
		cards?.querySelector('.muted')?.remove();
		if (cards) {
			const row = document.createElement('div');
			row.className = 'directory-row';
			row.dataset.memberId = member.id;
			row.innerHTML = '<div class="member-cell"><div class="member-avatar"></div><strong></strong></div><span></span><span class="tag">QR active</span><div class="row-actions"><button class="row-action view-qr" type="button">QR</button><button class="row-action delete-member" type="button">×</button></div>';
			row.querySelector('.member-avatar').textContent = member.name.split(' ').map((part) => part[0]).join('').slice(0, 2);
			row.querySelector('strong').textContent = member.name;
			row.querySelector('span:not(.tag)').textContent = `Member since ${new Date().getFullYear()}`;
			row.querySelector('.view-qr').dataset.name = member.name;
			row.querySelector('.view-qr').dataset.code = member.member_code;
			row.querySelector('.view-qr').dataset.token = member.qr_token;
			row.querySelector('.delete-member').dataset.url = `/api/members/${member.id}`;
			cards.prepend(row);
			document.querySelector('#member-count').textContent = `${cards.querySelectorAll('.directory-row').length} active members`;
		}
		button.innerHTML = 'Member added <span>✓</span>';
	} else {
		button.innerHTML = 'Could not add member';
	}
	button.disabled = false;
});
document.querySelector('#print-card')?.addEventListener('click', () => window.print());
document.querySelector('#print-existing-card')?.addEventListener('click', () => window.print());

function bindMemberActions(scope = document) {
	scope.querySelectorAll('.delete-member').forEach((button) => button.addEventListener('click', async () => {
		if (!window.confirm('Delete this member and their attendance history?')) return;
		const response = await fetch(button.dataset.url || `/api/members/${button.closest('.directory-row').dataset.memberId}`, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
		if (response.ok) {
			button.closest('.directory-row').remove();
			const total = document.querySelectorAll('#member-cards .directory-row').length;
			document.querySelector('#member-count').textContent = `${total} active members`;
		}
	}));
}

bindMemberActions();
document.querySelectorAll('.approve-user').forEach((button) => button.addEventListener('click', async () => {
	button.disabled = true;
	const response = await fetch(button.dataset.url, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
	if (response.ok) {
		button.closest('.approval-row').remove();
		if (!document.querySelector('.approval-row')) document.querySelector('#approval-list').innerHTML = '<p class="muted">No leader accounts are waiting for approval.</p>';
	}
	button.disabled = false;
}));
document.querySelector('.mobile-menu')?.addEventListener('click', () => document.querySelector('.sidebar').classList.toggle('open'));
document.querySelector('[data-view-attendees]')?.addEventListener('click', () => document.querySelector('#attendee-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));

document.querySelectorAll('.password-toggle').forEach((button) => button.addEventListener('click', () => {
	const input = button.parentElement.querySelector('input');
	input.type = input.type === 'password' ? 'text' : 'password';
	button.textContent = input.type === 'password' ? 'Show' : 'Hide';
}));

let logoutForm;
let logoutModal;

function openLogoutModal(form) {
	logoutForm = form;
	if (!logoutModal) {
		logoutModal = document.createElement('div');
		logoutModal.className = 'modal-backdrop';
		logoutModal.innerHTML = '<section class="modal logout-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="logout-title"><button class="modal-close" type="button" aria-label="Close logout confirmation">×</button><span class="section-kicker">Secure session</span><h2 id="logout-title">Log out of Gather?</h2><p class="muted">Your session will end on this device.</p><div class="modal-footer"><button class="button button-light logout-cancel" type="button">Cancel</button><button class="button button-dark logout-continue" type="button">Log out</button></div></section>';
		document.body.append(logoutModal);
		logoutModal.querySelector('.modal-close').addEventListener('click', () => closeModal(logoutModal));
		logoutModal.querySelector('.logout-cancel').addEventListener('click', () => closeModal(logoutModal));
		logoutModal.querySelector('.logout-continue').addEventListener('click', () => logoutForm.submit());
		logoutModal.addEventListener('click', (event) => { if (event.target === logoutModal) closeModal(logoutModal); });
	}
	openModal(logoutModal);
}

document.querySelectorAll('.logout-form').forEach((form) => form.addEventListener('submit', (event) => {
	event.preventDefault();
	openLogoutModal(form);
}));

document.querySelectorAll('[data-duration]').forEach((button) => button.addEventListener('click', () => {
	const value = document.querySelector('#duration-value');
	const label = document.querySelector('#duration-label');
	if (!value || !label) return;
	const next = Math.max(15, Math.min(720, Number(value.value) + Number(button.dataset.duration)));
	value.value = next;
	label.textContent = `${next} min`;
}));

if (document.body.dataset.autoOpenScanner === 'true' && scannerModal) document.querySelector('[data-open-scanner]')?.click();
