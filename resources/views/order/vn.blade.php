<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@lang('sms.title')</title>

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

        .step > i:first-child {
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
            to { transform: rotate(360deg); }
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
            *, *::before, *::after {
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
                            <button class="copy-btn" type="button" onclick="copyText('phone')" aria-label="@lang('sms.phone_number')">
                                <i class="fa-regular fa-copy" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="label">
                            <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                            Service
                        </div>
                        <div class="service-row">
                            <img class="service-icon" id="serviceIcon" alt="" hidden>
                            <div class="service-name" id="serviceName"></div>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="label">
                            <i class="fa-solid fa-globe" aria-hidden="true"></i>
                            @lang('sms.country_code')
                        </div>
                        <div class="value-row">
                            <div class="value" id="country"></div>
                            <button class="copy-btn" type="button" onclick="copyText('country')" aria-label="@lang('sms.country_code')">
                                <i class="fa-regular fa-copy" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="activity-panel">
                    <div class="timer-card">
                        <div class="timer-label">
                            <i class="fa-regular fa-clock" aria-hidden="true"></i>
                        </div>
                        <div class="timer" id="timer">20:00</div>
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
                                <div class="sms-value" id="code">582341</div>
                                <button class="copy-btn" type="button" onclick="copyText('code')" aria-label="@lang('sms.received_code')">
                                    <i class="fa-regular fa-copy" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="notice">
                    <strong>@lang('sms.not_received')</strong>
                    <br><br>
                    @lang('sms.contact')
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

            let toast = document.getElementById('toast');
            toast.style.display = 'block';

            setTimeout(() => {
                toast.style.display = 'none';
            }, 1800);
        }

        setTimeout(() => {
            document.getElementById("loading").style.display = "none";
            document.getElementById("smsCode").style.display = "block";
            document.getElementById("ding").play();
        }, 10000);

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

        document.addEventListener('DOMContentLoaded', async () => {
            const uniqueCode = new URLSearchParams(window.location.search)
                .get('uniquecode');

            if (!uniqueCode) {
                console.error('Unique code missing');
                return;
            }

            try {
                const response = await fetch('{{ url('/api/vm/verify') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        uniquecode: uniqueCode
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    setTimeout(() => {
                        complete(0);
                        activate(1);
                    }, 1200);

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

                    const phone = document.getElementById('phone');
                    const country = document.getElementById('country');

                    if (phone) {
                        phone.innerText = data.data.number;
                    }

                    if (country) {
                        country.innerText = data.data.country_code;
                    }

                    const serviceName = document.getElementById('serviceName');
                    const serviceIcon = document.getElementById('serviceIcon');

                    if (serviceName) {
                        serviceName.innerText = data.data.serviceName || '';
                    }

                    if (serviceIcon && data.data.serviceIcon) {
                        serviceIcon.src = data.data.serviceIcon;
                        serviceIcon.alt = data.data.serviceName || '';
                        serviceIcon.hidden = false;
                    }

                    const statusBadge = document.getElementById('statusBadge');
                    const statusLabel = document.getElementById('statusLabel');
                    const allowedStatuses = ['waiting', 'received', 'expired', 'refunded'];
                    const status = allowedStatuses.includes(data.data.status)
                        ? data.data.status
                        : 'waiting';

                    if (statusBadge) {
                        statusBadge.dataset.status = status;
                    }

                    if (statusLabel) {
                        statusLabel.innerText = data.data.statusLabel || '';
                    }

                    if (data.data.expires_at > 0) {
                        let total = Number(data.data.expires_at);
                        const timer = document.getElementById('timer');

                        const updateTimer = () => {
                            const minutes = Math.floor(total / 60);
                            let seconds = total % 60;
                            seconds = Math.floor(seconds);
                            timer.innerText =
                                String(minutes).padStart(2, '0') + ':' +
                                String(seconds).padStart(2, '0');
                        };

                        updateTimer();

                        const interval = setInterval(() => {
                            total--;

                            if (total <= 0) {
                                total = 0;
                                updateTimer();
                                clearInterval(interval);
                                return;
                            }

                            updateTimer();
                        }, 1000);
                    }
                } else {
                    console.error('Verification failed:', data.message || data);
                }
            } catch (error) {
                console.error('API Error:', error);
            }
        });
    </script>
</body>
</html>
