import './bootstrap';

const resendButton = document.querySelector('[data-otp-resend]');
if (resendButton) {
    const seconds = Number(resendButton.dataset.cooldownSeconds);
    const deadline = Date.now() + Math.max(0, seconds) * 1000;
    let timer;

    const updateCooldown = () => {
        const remaining = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
        resendButton.disabled = remaining > 0;
        resendButton.textContent = remaining > 0 ? `Gửi lại mã (${remaining}s)` : 'Gửi lại mã OTP';
        if (remaining === 0) {
            clearInterval(timer);
        }
    };

    updateCooldown();
    if (seconds > 0) {
        timer = setInterval(updateCooldown, 1000);
    }
}
