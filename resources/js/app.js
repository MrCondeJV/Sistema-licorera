import './bootstrap';
import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

// Sound feedback using Web Audio API (lightweight, zero network assets required)
window.PosSound = {
    audioCtx: null,
    enabled: localStorage.getItem('pos_sound') !== 'false',

    init() {
        if (!this.audioCtx && (window.AudioContext || window.webkitAudioContext)) {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            this.audioCtx = new AudioContextClass();
        }
    },

    toggle() {
        this.enabled = !this.enabled;
        localStorage.setItem('pos_sound', this.enabled ? 'true' : 'false');
        return this.enabled;
    },

    beep(type = 'success') {
        if (!this.enabled) return;
        try {
            this.init();
            if (!this.audioCtx) return;
            if (this.audioCtx.state === 'suspended') {
                this.audioCtx.resume();
            }

            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();
            osc.connect(gain);
            gain.connect(this.audioCtx.destination);

            const now = this.audioCtx.currentTime;

            if (type === 'success') {
                // Pleasant ascending chime
                osc.type = 'sine';
                osc.frequency.setValueAtTime(587.33, now); // D5
                osc.frequency.exponentialRampToValueAtTime(880, now + 0.12); // A5
                gain.gain.setValueAtTime(0.15, now);
                gain.gain.exponentialRampToValueAtTime(0.01, now + 0.18);
                osc.start(now);
                osc.stop(now + 0.18);
            } else if (type === 'add') {
                // Quick soft click/tap
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(440, now);
                gain.gain.setValueAtTime(0.1, now);
                gain.gain.exponentialRampToValueAtTime(0.01, now + 0.08);
                osc.start(now);
                osc.stop(now + 0.08);
            } else if (type === 'error') {
                // Lower buzz
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(220, now);
                osc.frequency.setValueAtTime(164.81, now + 0.08);
                gain.gain.setValueAtTime(0.2, now);
                gain.gain.exponentialRampToValueAtTime(0.01, now + 0.25);
                osc.start(now);
                osc.stop(now + 0.25);
            }
        } catch (e) {
            console.warn('Audio playback error', e);
        }
    }
};

// Global SweetAlert2 notification and confirmation manager
window.Swal = Swal;

window.AppAlert = {
    isDark() {
        return document.documentElement.classList.contains('dark');
    },

    getThemeColors() {
        const dark = this.isDark();
        return {
            background: dark ? '#0f172a' : '#ffffff',
            color: dark ? '#f8fafc' : '#0f172a',
            backdrop: dark ? 'rgba(15, 23, 42, 0.75)' : 'rgba(15, 23, 42, 0.4)',
        };
    },

    toast(title, icon = 'success', timer = 3200) {
        if (icon === 'success' && window.PosSound) window.PosSound.beep('success');
        if (icon === 'error' && window.PosSound) window.PosSound.beep('error');

        const colors = this.getThemeColors();
        return Swal.fire({
            toast: true,
            position: 'top-end',
            icon: icon,
            title: title,
            showConfirmButton: false,
            timer: timer,
            timerProgressBar: true,
            background: colors.background,
            color: colors.color,
            customClass: {
                popup: 'pos-toast-popup shadow-xl rounded-2xl border border-slate-200 dark:border-slate-800'
            }
        });
    },

    success(title, text = '') {
        if (window.PosSound) window.PosSound.beep('success');
        const colors = this.getThemeColors();
        return Swal.fire({
            icon: 'success',
            title: title,
            text: text,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#059669', // emerald-600
            background: colors.background,
            color: colors.color,
            backdrop: colors.backdrop,
            customClass: {
                popup: 'rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl',
                confirmButton: 'rounded-xl font-bold px-6 py-2.5 text-sm shadow-md'
            }
        });
    },

    error(title, text = '') {
        if (window.PosSound) window.PosSound.beep('error');
        const colors = this.getThemeColors();
        return Swal.fire({
            icon: 'error',
            title: title,
            text: text,
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#e11d48', // rose-600
            background: colors.background,
            color: colors.color,
            backdrop: colors.backdrop,
            customClass: {
                popup: 'rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl',
                confirmButton: 'rounded-xl font-bold px-6 py-2.5 text-sm shadow-md'
            }
        });
    },

    warning(title, text = '') {
        if (window.PosSound) window.PosSound.beep('error');
        const colors = this.getThemeColors();
        return Swal.fire({
            icon: 'warning',
            title: title,
            text: text,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#f59e0b', // amber-500
            background: colors.background,
            color: colors.color,
            backdrop: colors.backdrop,
            customClass: {
                popup: 'rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl',
                confirmButton: 'rounded-xl font-bold px-6 py-2.5 text-sm shadow-md'
            }
        });
    },

    info(title, text = '') {
        const colors = this.getThemeColors();
        return Swal.fire({
            icon: 'info',
            title: title,
            text: text,
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#0284c7', // sky-600
            background: colors.background,
            color: colors.color,
            backdrop: colors.backdrop,
            customClass: {
                popup: 'rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl',
                confirmButton: 'rounded-xl font-bold px-6 py-2.5 text-sm shadow-md'
            }
        });
    },

    async confirm({
        title = '¿Estás seguro?',
        text = 'Esta acción no se puede revertir.',
        confirmButtonText = 'Sí, continuar',
        cancelButtonText = 'Cancelar',
        icon = 'warning',
        isDanger = true
    } = {}) {
        const colors = this.getThemeColors();
        const result = await Swal.fire({
            icon: icon,
            title: title,
            text: text,
            showCancelButton: true,
            confirmButtonColor: isDanger ? '#e11d48' : '#059669',
            cancelButtonColor: colors.background === '#ffffff' ? '#64748b' : '#334155',
            confirmButtonText: confirmButtonText,
            cancelButtonText: cancelButtonText,
            reverseButtons: true,
            background: colors.background,
            color: colors.color,
            backdrop: colors.backdrop,
            customClass: {
                popup: 'rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl',
                confirmButton: 'rounded-xl font-bold px-5 py-2.5 text-sm shadow-md',
                cancelButton: 'rounded-xl font-bold px-5 py-2.5 text-sm'
            }
        });
        return result.isConfirmed;
    }
};

// Fallback safety: redirect browser-native alert to AppAlert.warning
window.alert = function (message) {
    window.AppAlert.warning('Aviso del Sistema', String(message));
};

// Global interceptor for forms with data-confirm
document.addEventListener('submit', async function (e) {
    const form = e.target;
    if (form && form.hasAttribute && form.hasAttribute('data-confirm')) {
        e.preventDefault();
        const message = form.getAttribute('data-confirm');
        const title = form.getAttribute('data-confirm-title') || '¿Confirmar acción?';
        const isDanger = form.getAttribute('data-confirm-danger') !== 'false';
        const confirmBtn = form.getAttribute('data-confirm-btn') || (isDanger ? 'Sí, continuar' : 'Aceptar');
        const cancelBtn = form.getAttribute('data-cancel-btn') || 'Cancelar';
        const icon = form.getAttribute('data-confirm-icon') || (isDanger ? 'warning' : 'question');

        const confirmed = await window.AppAlert.confirm({
            title: title,
            text: message,
            confirmButtonText: confirmBtn,
            cancelButtonText: cancelBtn,
            isDanger: isDanger,
            icon: icon
        });

        if (confirmed) {
            form.removeAttribute('data-confirm');
            form.submit();
        }
    }
});

// Global interceptor for clickable elements (links/buttons) with data-confirm
document.addEventListener('click', async function (e) {
    const trigger = e.target.closest('a[data-confirm], button[data-confirm]:not([type="submit"])');
    if (trigger) {
        e.preventDefault();
        const message = trigger.getAttribute('data-confirm');
        const title = trigger.getAttribute('data-confirm-title') || '¿Confirmar acción?';
        const isDanger = trigger.getAttribute('data-confirm-danger') !== 'false';
        const confirmBtn = trigger.getAttribute('data-confirm-btn') || (isDanger ? 'Sí, continuar' : 'Aceptar');
        const cancelBtn = trigger.getAttribute('data-cancel-btn') || 'Cancelar';
        const icon = trigger.getAttribute('data-confirm-icon') || (isDanger ? 'warning' : 'question');

        const confirmed = await window.AppAlert.confirm({
            title: title,
            text: message,
            confirmButtonText: confirmBtn,
            cancelButtonText: cancelBtn,
            isDanger: isDanger,
            icon: icon
        });

        if (confirmed) {
            if (trigger.tagName.toLowerCase() === 'a' && trigger.href) {
                window.location.href = trigger.href;
            }
        }
    }
});

window.Alpine = Alpine;
Alpine.start();
