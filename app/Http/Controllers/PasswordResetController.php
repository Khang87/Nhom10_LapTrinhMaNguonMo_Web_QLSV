<?php

namespace App\Http\Controllers;

use App\Services\PasswordResetDeliveryException;
use App\Services\PasswordResetOtpRateLimitException;
use App\Services\PasswordResetOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function showLinkRequestForm(Request $request, PasswordResetOtpService $otpService): View
    {
        if ($request->boolean('change_email')) {
            $request->session()->forget(['reset_email', 'password_reset_grant']);
        }

        $email = (string) $request->session()->get('reset_email', '');

        return view('auth.forgot-password', [
            'email' => $email,
            'cooldownSeconds' => $email !== '' ? $otpService->cooldownSeconds($email) : 0,
        ]);
    }

    public function sendResetOtp(Request $request, PasswordResetOtpService $otpService): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);
        $email = mb_strtolower(trim($validated['email']));
        $request->session()->forget('password_reset_grant');

        try {
            $otpService->send($email, (string) $request->ip());
        } catch (PasswordResetOtpRateLimitException) {
            $request->session()->put('reset_email', $email);

            return redirect()->route('password.request')
                ->with('error', 'Vui lòng chờ trước khi yêu cầu mã OTP mới.');
        } catch (PasswordResetDeliveryException $exception) {
            $cause = $exception->getPrevious();
            Log::error('Password reset OTP could not be delivered by Nodemailer.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'cause' => $cause ? $cause::class : null,
            ]);

            $request->session()->put('reset_email', $email);

            return redirect()->route('password.request')
                ->with('error', 'Không thể gửi mã lúc này. Vui lòng thử lại sau.');
        }

        $request->session()->put('reset_email', $email);

        return redirect()->route('password.request')
            ->with('status', 'Nếu email tồn tại, mã xác thực sẽ được gửi đến hộp thư của bạn.');
    }

    public function verifyOtp(Request $request, PasswordResetOtpService $otpService): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'digits:6'],
        ]);
        $email = mb_strtolower(trim($validated['email']));
        $request->session()->put('reset_email', $email);
        $request->session()->regenerate();

        $token = $otpService->verify($email, $validated['otp'], $request->session()->getId());
        if ($token === null) {
            return redirect()->route('password.request')
                ->with('error', 'Email hoặc mã OTP không đúng, đã hết hạn hoặc đã được sử dụng.');
        }

        $request->session()->put('password_reset_grant', $token);

        return redirect()->route('password.reset');
    }

    public function showResetForm(Request $request, PasswordResetOtpService $otpService): View|RedirectResponse
    {
        if (! $otpService->canReset($request->session()->get('password_reset_grant'), $request->session()->getId())) {
            $request->session()->forget('password_reset_grant');

            return redirect()->route('password.request')
                ->with('error', 'Vui lòng xác thực OTP trước khi đặt mật khẩu mới.');
        }

        return view('auth.reset-password');
    }

    public function resetPassword(Request $request, PasswordResetOtpService $otpService): RedirectResponse
    {
        $token = $request->session()->get('password_reset_grant');
        $sessionId = $request->session()->getId();
        if (! $otpService->canReset($token, $sessionId)) {
            $request->session()->forget('password_reset_grant');

            return redirect()->route('password.request')
                ->with('error', 'Phiên đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng xác thực OTP lại.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        if ($otpService->passwordMatchesCurrent($token, $validated['password'], $sessionId)) {
            return back()->withErrors([
                'password' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
            ]);
        }

        if (! $otpService->reset($token, $validated['password'], $sessionId)) {
            $request->session()->forget('password_reset_grant');

            return redirect()->route('password.request')
                ->with('error', 'Phiên đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng xác thực OTP lại.');
        }

        $request->session()->forget(['password_reset_grant', 'reset_email']);

        return redirect()->route('login')
            ->with('status', 'Mật khẩu đã được cập nhật. Vui lòng đăng nhập lại.');
    }
}
