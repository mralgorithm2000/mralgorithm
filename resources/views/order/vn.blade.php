<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="pusher-key" content="{{ config('broadcasting.connections.pusher.key') }}">
    <meta name="pusher-cluster" content="{{ config('broadcasting.connections.pusher.options.cluster', 'mt1') }}">
    <title>@lang('sms.title')</title>

    @vite('resources/js/app.js')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <style>
        :root {
            --page: #f7f8fc;
            --surface: rgba(255, 255, 255, .92);
            --surface-soft: #f8f9fc;
            --text: #303747;
            --muted: #778096;
            --line: #e9ebf3;
            --lavender: #7467b8;
            --lavender-soft: #eeebfb;
            --blue-soft: #edf6fc;
            --green: #39846c;
            --green-soft: #e9f6f0;
            --amber: #9b6b2f;
            --amber-soft: #fbf2df;
            --red: #a85661;
            --red-soft: #faeaed;
            --gray-soft: #eef0f5;
            --shadow: 0 18px 50px rgba(67, 72, 101, .10);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
            overflow-x: hidden;
            color: var(--text);
            background:
                radial-gradient(circle at 12% 12%, rgba(235, 223, 246, .72), transparent 31%),
                radial-gradient(circle at 88% 88%, rgba(219, 239, 249, .78), transparent 32%),
                var(--page);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        button {
            font: inherit;
        }

        .page-shell {
            width: min(880px, 100%);
        }

        .order-card {
            overflow: hidden;
            border: 1px solid rgba(231, 233, 242, .85);
            border-radius: 24px;
            background: var(--surface);
            box-shadow: var(--shadow);
            backdrop-filter: blur(14px);
        }

        .order-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 22px 24px 18px;
            border-bottom: 1px solid var(--line);
        }

        .title-group {
            display: flex;
            align-items: center;
            min-width: 0;
            gap: 13px;
        }

        .title-icon {
            width: 42px;
            height: 42px;
            display: grid;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 13px;
            color: var(--lavender);
            background: var(--lavender-soft);
            font-size: 18px;
        }

        h1 {
            color: var(--text);
            font-size: clamp(20px, 3vw, 25px);
            line-height: 1.2;
            letter-spacing: -.025em;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            flex: 0 0 auto;
            gap: 8px;
            min-height: 34px;
            padding: 7px 12px;
            border-radius: 999px;
            color: var(--amber);
            background: var(--amber-soft);
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, .55);
        }

        .status-badge[data-status="received"] {
            color: var(--green);
            background: var(--green-soft);
        }

        .status-badge[data-status="expired"] {
            color: var(--red);
            background: var(--red-soft);
        }

        .status-badge[data-status="refunded"] {
            color: #657086;
            background: var(--gray-soft);
        }

        .status-badge[data-status="refund_pending"] {
            color: var(--amber);
            background: var(--amber-soft);
        }

        .order-body {
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(280px, .92fr);
            gap: 18px;
            padding: 20px 24px 24px;
        }

        .details-column {
            display: grid;
            align-content: start;
            gap: 12px;
        }

        .info-card {
            min-width: 0;
            padding: 14px 15px;
            border-radius: 16px;
            background: var(--surface-soft);
            transition: background-color .2s ease, transform .2s ease;
        }

        .info-card:hover {
            background: #f4f5fa;
            transform: translateY(-1px);
        }

        .label {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 8px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .015em;
        }

        .label i {
            width: 15px;
            color: #9991c5;
            text-align: center;
        }

        .value-row {
            display: flex;
            min-width: 0;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .value {
            min-width: 0;
            overflow-wrap: anywhere;
            color: var(--text);
            font-size: 18px;
            font-weight: 700;
            letter-spacing: .01em;
        }

        .copy-btn {
            width: 40px;
            height: 40px;
            display: grid;
            flex: 0 0 auto;
            place-items: center;
            border: 0;
            border-radius: 12px;
            color: var(--lavender);
            background: var(--lavender-soft);
            cursor: pointer;
            font-size: 16px;
            transition: background-color .2s ease, transform .2s ease;
        }

        .copy-btn:hover {
            background: #e2def6;
            transform: translateY(-1px);
        }

        .copy-btn:focus-visible {
            outline: 3px solid rgba(116, 103, 184, .25);
            outline-offset: 2px;
        }

        .service-row {
            display: flex;
            min-height: 40px;
            align-items: center;
            gap: 10px;
        }

        .service-icon {
            width: 28px;
            height: 28px;
            flex: 0 0 auto;
            object-fit: contain;
        }

        .service-name {
            color: var(--text);
            font-size: 16px;
            font-weight: 650;
        }

        .activity-panel {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 12px;
        }

        .timer-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 13px 16px;
            border-radius: 16px;
            color: var(--red);
            background: var(--red-soft);
        }

        .timer-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #845d67;
            font-size: 13px;
            font-weight: 600;
        }

        .timer {
            color: var(--red);
            font-size: 25px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            letter-spacing: .02em;
        }

        .sms-area {
            min-height: 160px;
            display: grid;
            flex: 1;
            place-items: center;
            padding: 20px;
            border-radius: 18px;
            background: var(--blue-soft);
            text-align: center;
        }

        #loading h3 {
            margin-bottom: 6px;
            color: var(--text);
            font-size: 16px;
        }

        #loading p {
            max-width: 280px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .spinner {
            width: 30px;
            height: 30px;
            margin: 14px auto;
            border: 3px solid rgba(116, 103, 184, .16);
            border-top-color: var(--lavender);
            border-radius: 50%;
            animation: spin .8s linear infinite;
        }

        .sms-code {
            width: 100%;
            display: none;
        }

        .sms-code .label {
            justify-content: center;
        }

        .sms-value-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-top: 10px;
        }

        .sms-value {
            color: var(--green);
            font-size: clamp(27px, 5vw, 34px);
            font-weight: 700;
            letter-spacing: .13em;
        }

        .notice {
            grid-column: 1 / -1;
            padding: 13px 15px;
            border-radius: 15px;
            color: #596578;
            background: #f2f6f8;
            font-size: 13px;
            line-height: 1.55;
        }

        .notice strong {
            color: #455165;
        }

        .replacement-action {
            display: none;
            grid-column: 1 / -1;
            text-align: center;
        }

        .replacement-btn {
            padding: 12px 20px;
            border: 0;
            border-radius: 12px;
            color: #fff;
            background: var(--lavender);
            cursor: pointer;
            font-weight: 700;
        }

        .replacement-btn:disabled {
            cursor: not-allowed;
            opacity: .65;
        }

        .replacement-btn.is-loading {
            cursor: wait;
        }

        .replacement-btn .button-spinner {
            display: none;
            margin-right: 7px;
        }

        .replacement-btn.is-loading .button-spinner {
            display: inline-block;
        }

        .refund-btn {
            background: var(--red);
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: none;
            padding: 12px 17px;
            border-radius: 12px;
            color: #fff;
            background: var(--green);
            box-shadow: 0 10px 25px rgba(48, 89, 76, .18);
            font-size: 14px;
            font-weight: 600;
        }

        #verifyScreen {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: grid;
            place-items: center;
            padding: 20px;
            background:
                radial-gradient(circle at 15% 15%, rgba(235, 223, 246, .82), transparent 36%),
                radial-gradient(circle at 85% 85%, rgba(219, 239, 249, .88), transparent 38%),
                #f8f9fc;
            transition: opacity .5s ease, visibility .5s ease;
        }

        .verify-card {
            width: min(470px, 100%);
            padding: 28px;
            border: 1px solid rgba(231, 233, 242, .8);
            border-radius: 22px;
            background: rgba(255, 255, 255, .94);
            box-shadow: var(--shadow);
        }

        .verify-icon {
            display: flex;
            justify-content: center;
        }

        .loader {
            width: 54px;
            height: 54px;
            border: 4px solid var(--lavender-soft);
            border-top-color: var(--lavender);
            border-radius: 50%;
            animation: spin .9s linear infinite;
        }

        .verify-card h1 {
            margin-top: 18px;
            text-align: center;
        }

        .verify-subtitle {
            margin: 8px auto 22px;
            color: var(--muted);
            text-align: center;
            font-size: 14px;
            line-height: 1.6;
        }

        .verification-error {
            display: none;
            text-align: center;
        }

        .error-icon {
            width: 54px;
            height: 54px;
            display: grid;
            margin: 0 auto;
            place-items: center;
            border-radius: 50%;
            color: var(--red);
            background: var(--red-soft);
            font-size: 22px;
        }

        .error-message {
            margin: 8px 0 20px;
            padding: 13px 15px;
            border-radius: 13px;
            color: #845d67;
            background: var(--red-soft);
            font-size: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .retry-btn {
            display: inline-flex;
            min-height: 42px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border: 0;
            border-radius: 12px;
            color: #fff;
            background: var(--lavender);
            cursor: pointer;
            font-weight: 700;
            transition: background-color .2s ease, transform .2s ease;
        }

        .retry-btn:hover {
            background: #6659aa;
            transform: translateY(-1px);
        }

        .retry-btn:focus-visible {
            outline: 3px solid rgba(116, 103, 184, .25);
            outline-offset: 2px;
        }

        .error-contact {
            margin-top: 18px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .steps {
            display: grid;
            gap: 9px;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 14px;
            border-radius: 13px;
            color: #949bad;
            background: var(--surface-soft);
            font-size: 14px;
            transition: color .3s ease, background-color .3s ease;
        }

        .step>i:first-child {
            width: 18px;
            text-align: center;
        }

        .step span {
            flex: 1;
            font-weight: 600;
        }

        .step.active {
            color: var(--lavender);
            background: var(--lavender-soft);
        }

        .step.done {
            color: var(--green);
            background: var(--green-soft);
        }

        .spinner-icon,
        .success-icon {
            display: none;
        }

        .step.active .spinner-icon,
        .step.done .success-icon {
            display: block;
        }

        .fadeOut {
            opacity: 0;
            visibility: hidden;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 680px) {
            body {
                display: block;
                padding: 12px;
            }

            .page-shell {
                margin: 0 auto;
            }

            .order-card {
                border-radius: 20px;
            }

            .order-header {
                align-items: flex-start;
                padding: 18px;
            }

            .title-icon {
                width: 38px;
                height: 38px;
            }

            .order-body {
                grid-template-columns: 1fr;
                gap: 12px;
                padding: 14px 18px 18px;
            }

            .notice {
                grid-column: auto;
            }

            .sms-area {
                min-height: 150px;
            }

            .toast {
                top: 12px;
                right: 12px;
                left: 12px;
                text-align: center;
            }
        }

        @media (max-width: 390px) {
            .order-header {
                flex-direction: column;
            }

            .status-badge {
                margin-left: 51px;
            }

            .verify-card {
                padding: 22px 18px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>
</head>

<body>
    <div id="verifyScreen">
        <div class="verify-card">
            <div id="verificationLoading">
                <div class="verify-icon">
                    <div class="loader"></div>
                </div>

                <h1>@lang('sms.verifying_payment')</h1>

                <p class="verify-subtitle">
                    @lang('sms.verifying_payment_description')
                </p>

                <div class="steps">
                    <div class="step active" id="step1">
                        <i class="fa-solid fa-credit-card"></i>
                        <span>@lang('sms.step_payment_received')</span>
                        <i class="fa-solid fa-circle-notch fa-spin spinner-icon"></i>
                        <i class="fa-solid fa-check success-icon"></i>
                    </div>

                    <div class="step" id="step2">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>@lang('sms.step_transaction')</span>
                        <i class="fa-solid fa-circle-notch fa-spin spinner-icon"></i>
                        <i class="fa-solid fa-check success-icon"></i>
                    </div>

                    <div class="step" id="step3">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                        <span>@lang('sms.step_preparing')</span>
                        <i class="fa-solid fa-circle-notch fa-spin spinner-icon"></i>
                        <i class="fa-solid fa-check success-icon"></i>
                    </div>
                </div>
            </div>

            <div class="verification-error" id="verificationError" role="alert">
                <div class="error-icon" aria-hidden="true">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h1>@lang('sms.error_title')</h1>
                <p class="error-message" id="verificationErrorMessage"></p>
                <button class="retry-btn" id="retryVerification" type="button">
                    <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                    @lang('sms.retry')
                </button>
                <p class="error-contact">@lang('sms.error_contact')</p>
            </div>
        </div>
    </div>

    <main class="page-shell" id="content" style="display:none">
        <section class="order-card">
            <header class="order-header">
                <div class="title-group">
                    <div class="title-icon" aria-hidden="true">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                    </div>
                    <h1>@lang('sms.title')</h1>
                </div>

                <div class="status-badge" id="statusBadge" data-status="waiting" role="status">
                    <span class="status-dot" aria-hidden="true"></span>
                    <span id="statusLabel"></span>
                </div>
            </header>

            <div class="order-body">
                <div class="details-column">
                    <div class="info-card">
                        <div class="label">
                            <i class="fa-solid fa-phone" aria-hidden="true"></i>
                            @lang('sms.phone_number')
                        </div>
                        <div class="value-row">
                            <div class="value" id="phone"></div>
                            <button class="copy-btn" type="button" onclick="copyText('phone')"
                                aria-label="@lang('sms.phone_number')">
                                <i class="fa-regular fa-copy" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="label">
                            <i class="fa-solid fa-globe" aria-hidden="true"></i>
                            @lang('sms.country_code')
                        </div>
                        <div class="value-row">
                            <div class="value" id="country"></div>
                            <button class="copy-btn" type="button" onclick="copyText('country')"
                                aria-label="@lang('sms.country_code')">
                                <i class="fa-regular fa-copy" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="label">
                            <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                            @lang('sms.service')
                        </div>
                        <div class="service-row">
                            <img class="service-icon" id="serviceIcon" alt="" hidden>
                            <div class="service-name" id="serviceName"></div>
                        </div>
                    </div>
                </div>

                <div class="activity-panel">
                    <div class="timer-card">
                        <div class="timer-label">
                            <i class="fa-regular fa-clock" aria-hidden="true"></i>
                        </div>
                        <div class="timer" id="timer">00:00</div>
                    </div>

                    <div class="sms-area" aria-live="polite">
                        <div id="loading">
                            <h3>@lang('sms.waiting')</h3>
                            <div class="spinner"></div>
                            <p>@lang('sms.waiting_description')</p>
                        </div>

                        <div class="sms-code" id="smsCode">
                            <div class="label">@lang('sms.received_code')</div>
                            <div class="sms-value-box">
                                <div class="sms-value" id="code"></div>
                                <button class="copy-btn" type="button" onclick="copyText('code')"
                                    aria-label="@lang('sms.received_code')">
                                    <i class="fa-regular fa-copy" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="notice" id="waitingCancellationNotice" style="display:none">
                    <b>@lang('sms.cancel_number_question')</b>
                    <p>@lang('sms.cancel_number_description')</p>
                </div>

                <div class="replacement-action" id="cancellationAction">
                    <button class="replacement-btn refund-btn" id="cancelNumber" type="button">
                        <i class="fa-solid fa-circle-notch fa-spin button-spinner" aria-hidden="true"></i>
                        @lang('sms.cancel_number')
                    </button>
                </div>

                <div class="notice" id="expiredNotice" style="display:none">
                    <strong>@lang('sms.sms_not_received')</strong>
                    <p>@lang('sms.expired_actions_description')</p>
                    <ul>
                        <li><strong>@lang('sms.get_another_number')</strong> – @lang('sms.get_another_number_description')</li>
                        <li><strong>@lang('sms.request_refund')</strong> – @lang('sms.request_refund_description')</li>
                    </ul>
                </div>

                <div class="replacement-action" id="replacementAction">
                    <button class="replacement-btn" id="getAnotherNumber" type="button">
                        @lang('sms.get_another_number')
                    </button>
                </div>

                <div class="replacement-action" id="refundAction">
                    <button class="replacement-btn refund-btn" id="requestRefund" type="button">
                        @lang('sms.request_refund')
                    </button>
                </div>
            </div>
        </section>
    </main>

    <div class="toast" id="toast" role="status">
        @lang('sms.copied')
    </div>

    <audio id="ding">
        <source src="https://actions.google.com/sounds/v1/cartoon/clang_and_wobble.ogg">
    </audio>

    <script>
        function copyText(id) {
            navigator.clipboard.writeText(
                document.getElementById(id).innerText
            );

            showToast(@json(__('sms.copied')));
        }

        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.innerText = message;
            toast.style.display = 'block';

            setTimeout(() => {
                toast.style.display = 'none';
            }, 1800);
        }

        function subscribeToSmsCode(orderId) {
            if (!window.Echo) {
                console.error(
                    'Laravel Echo is unavailable. Configure the Pusher credentials and set BROADCAST_CONNECTION=pusher.'
                );

                return;
            }

            window.Echo.private(`phone-attempt.${orderId}`)
                .listen('.sms.code.received', (event) => {
                    if (Number(event.order_id) !== Number(orderId)) {
                        return;
                    }

                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('smsCode').style.display = 'block';
                    document.getElementById('code').innerText = event.sms_code;
                    document.getElementById('ding').play().catch(() => {});
                    document.getElementById('statusLabel').innerText = @json(__('sms.status_received'));
                    document.getElementById('statusBadge').dataset.status = 'received';
                    document.getElementById('replacementAction').style.display = 'none';
                    document.getElementById('refundAction').style.display = 'none';
                    showStatusActions('received', true);

                    if (attemptTimer) {
                        clearInterval(attemptTimer);
                    }
                })
                .listen('.phone.number.cancelled', (event) => {
                    if (Number(event.order_id) !== Number(orderId)) {
                        return;
                    }

                    showToast(@json(__('sms.number_canceled')));
                    showStatusActions('expired', false);
                    showReplacementButton(Boolean(event.can_order_replacement));
                    showRefundButton(Boolean(event.can_request_refund));
                    document.getElementById('statusBadge').dataset.status = 'expired';
                    document.getElementById('statusLabel').innerText = @json(__('sms.status_expired'));

                    if (attemptTimer) {
                        clearInterval(attemptTimer);
                        attemptTimer = null;
                    }
                });
        }

        let attemptTimer = null;
        let subscribedAttemptId = null;

        function showReplacementButton(show) {
            document.getElementById('replacementAction').style.display = show ? 'block' : 'none';
        }

        function showRefundButton(show) {
            document.getElementById('refundAction').style.display = show ? 'block' : 'none';
        }

        function showStatusActions(status, hasCode) {
            const isWaiting = status === 'waiting' && !hasCode;
            const isCompleted = status === 'completed' || status === 'received' || hasCode;

            document.getElementById('waitingCancellationNotice').style.display = isWaiting ? 'block' : 'none';
            document.getElementById('cancellationAction').style.display = isWaiting ? 'block' : 'none';
            document.getElementById('expiredNotice').style.display = !isWaiting && !isCompleted ? 'block' : 'none';
        }

        function displayAttempt(
            data,
            canOrderReplacement = false,
            canRequestRefund = false,
            purchaseStatus = 'pending'
        ) {
            if (data.order_id && Number(data.order_id) !== Number(subscribedAttemptId)) {
                subscribeToSmsCode(data.order_id);
                subscribedAttemptId = data.order_id;
            }

            document.getElementById('phone').innerText = data.number || '';
            document.getElementById('country').innerText = data.country_code || '';

            const serviceName = document.getElementById('serviceName');
            const serviceIcon = document.getElementById('serviceIcon');
            serviceName.innerText = data.serviceName || '';

            if (data.serviceIcon) {
                serviceIcon.src = data.serviceIcon;
                serviceIcon.alt = data.serviceName || '';
                serviceIcon.hidden = false;
            }

            const hasCode = data.sms_code !== '' && data.sms_code !== null && data.sms_code !== undefined;
            document.getElementById('loading').style.display = hasCode ? 'none' : 'block';
            document.getElementById('smsCode').style.display = hasCode ? 'block' : 'none';
            document.getElementById('code').innerText = data.sms_code || '';

            const allowedStatuses = ['waiting', 'received', 'completed', 'expired', 'refunded'];
            const status = allowedStatuses.includes(data.status) ? data.status : 'waiting';
            document.getElementById('statusBadge').dataset.status = status;
            document.getElementById('statusLabel').innerText = data.statusLabel || '';
            showStatusActions(status, hasCode);

            if (purchaseStatus === 'refund_pending') {
                document.getElementById('statusBadge').dataset.status = 'refund_pending';
                document.getElementById('statusLabel').innerText = @json(__('sms.status_refund_pending'));
            }

            if (attemptTimer) {
                clearInterval(attemptTimer);
                attemptTimer = null;
            }

            const timer = document.getElementById('timer');
            let total = Math.max(0, Number(data.expires_at) || 0);
            const actionsAllowedWhenTimerEnds = purchaseStatus === 'pending' && status === 'waiting' && !hasCode;
            showReplacementButton(Boolean(canOrderReplacement));
            showRefundButton(Boolean(canRequestRefund));

            const updateTimer = () => {
                total = Math.max(0, total);
                const minutes = Math.floor(total / 60);
                const seconds = Math.floor(total % 60);
                timer.innerText = String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0');
            };

            updateTimer();

            if (total > 0 && status === 'waiting') {
                attemptTimer = setInterval(() => {
                    total = Math.max(0, total - 1);
                    updateTimer();

                    if (total <= 0) {
                        clearInterval(attemptTimer);
                        attemptTimer = null;
                        document.getElementById('statusBadge').dataset.status = 'expired';
                        document.getElementById('statusLabel').innerText = @json(__('sms.status_expired'));
                        showStatusActions('expired', false);
                        showReplacementButton(actionsAllowedWhenTimerEnds);
                        showRefundButton(actionsAllowedWhenTimerEnds);
                    }
                }, 1000);
            }
        }

        const steps = [
            document.getElementById('step1'),
            document.getElementById('step2'),
            document.getElementById('step3')
        ];

        function activate(index) {
            steps.forEach(s => s.classList.remove('active'));
            steps[index].classList.add('active');
        }

        function complete(index) {
            steps[index].classList.remove('active');
            steps[index].classList.add('done');
        }

        const genericVerificationError = @json(__('sms.verification_error'));

        function showVerificationError(message) {
            const loading = document.getElementById('verificationLoading');
            const errorSection = document.getElementById('verificationError');
            const errorMessage = document.getElementById('verificationErrorMessage');

            loading.style.display = 'none';
            errorMessage.textContent = message || genericVerificationError;
            errorSection.style.display = 'block';
        }

        var retry = false;
        async function verifyPayment() {
            const uniqueCode = new URLSearchParams(window.location.search)
                .get('uniquecode');

            if (!uniqueCode) {
                showVerificationError(genericVerificationError);
                return;
            }

            try {

                if (retry == false) {
                    setTimeout(() => {
                        complete(0);
                        activate(1);
                    }, 1200);
                }

                const response = await fetch('{{ url('/api/vm/verify') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: JSON.stringify({
                        uniquecode: uniqueCode
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    displayAttempt(
                        data.data,
                        data.can_order_replacement,
                        data.can_request_refund,
                        data.purchase_status
                    );

                    setTimeout(() => {
                        complete(1);
                        activate(2);
                    }, 1500);

                    setTimeout(() => {
                        complete(2);

                        const loader = document.querySelector('.loader');
                        if (loader) {
                            loader.style.borderTopColor = '#39846c';
                        }
                    }, 2000);

                    setTimeout(() => {
                        const verifyScreen = document.getElementById('verifyScreen');
                        const content = document.getElementById('content');

                        if (verifyScreen) {
                            verifyScreen.classList.add('fadeOut');

                            setTimeout(() => {
                                verifyScreen.remove();

                                if (content) {
                                    content.style.display = 'block';
                                }
                            }, 500);
                        }
                    }, 2500);

                } else {
                    if (data.type === 'purchase_error') {
                        if (retry == false) {
                            setTimeout(() => {
                                complete(1);
                                activate(2);
                            }, 1500);
                        }


                        setTimeout(() => {
                            showVerificationError(data.message);
                        }, 3500);
                    } else {
                        showVerificationError(genericVerificationError);
                    }
                }
            } catch (error) {
                console.error('API Error:', error);
                showVerificationError(genericVerificationError);
            }
        }

        async function orderReplacement() {
            const button = document.getElementById('getAnotherNumber');
            const uniqueCode = new URLSearchParams(window.location.search).get('uniquecode');

            if (!uniqueCode || button.disabled) {
                return;
            }

            button.disabled = true;

            try {
                const response = await fetch('{{ url('/api/vm/replacement') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: JSON.stringify({
                        uniquecode: uniqueCode
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    displayAttempt(
                        data.data,
                        data.can_order_replacement,
                        data.can_request_refund,
                        data.purchase_status
                    );
                    return;
                }

                if (data.can_order_replacement === false) {
                    showReplacementButton(false);
                }

                window.alert(data.message || genericVerificationError);
            } catch (error) {
                console.error('Replacement API Error:', error);
                window.alert(genericVerificationError);
            } finally {
                button.disabled = false;
            }
        }

        async function requestRefund() {
            const button = document.getElementById('requestRefund');
            const uniqueCode = new URLSearchParams(window.location.search).get('uniquecode');

            if (!uniqueCode || button.disabled) {
                return;
            }

            button.disabled = true;

            try {
                const response = await fetch('{{ url('/api/vm/refund-request') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: JSON.stringify({
                        uniquecode: uniqueCode
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showRefundButton(false);
                    showReplacementButton(false);
                    document.getElementById('statusBadge').dataset.status = 'refund_pending';
                    document.getElementById('statusLabel').innerText = @json(__('sms.status_refund_pending'));
                    return;
                }

                if (data.can_request_refund === false) {
                    showRefundButton(false);
                }

                window.alert(data.message || genericVerificationError);
            } catch (error) {
                console.error('Refund Request API Error:', error);
                window.alert(genericVerificationError);
            } finally {
                button.disabled = false;
            }
        }

        async function cancelNumber() {
            const button = document.getElementById('cancelNumber');
            const uniqueCode = new URLSearchParams(window.location.search).get('uniquecode');

            if (!uniqueCode || button.disabled) {
                return;
            }

            button.disabled = true;
            button.classList.add('is-loading');
            let cancellationRequested = false;

            try {
                const response = await fetch('{{ url('/api/vm/cancel-number') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: JSON.stringify({
                        uniqueCode: uniqueCode
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    cancellationRequested = true;
                    showToast(data.message || @json(__('sms.cancellation_request_sent')));
                    return;
                }

                window.alert(data.message || genericVerificationError);
            } catch (error) {
                console.error('Cancellation API Error:', error);
                window.alert(genericVerificationError);
            } finally {
                button.classList.remove('is-loading');
                button.disabled = cancellationRequested;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('retryVerification').addEventListener('click', () => {
                const loading = document.getElementById('verificationLoading');
                const errorSection = document.getElementById('verificationError');

                errorSection.style.display = 'none';
                loading.style.display = 'block';
                retry = true;
                verifyPayment();
            });

            document.getElementById('getAnotherNumber').addEventListener('click', orderReplacement);
            document.getElementById('requestRefund').addEventListener('click', requestRefund);
            document.getElementById('cancelNumber').addEventListener('click', cancelNumber);

            verifyPayment();
        });
    </script>
</body>

</html>
