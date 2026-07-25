<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@lang('sms.title')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Inter, sans-serif;
        }

        body {

            background: #f7f8fc;

            background-image:
                linear-gradient(120deg, #fdf2f8, #eef6ff);

            display: flex;
            justify-content: center;
            align-items: center;

            min-height: 100vh;

            padding: 30px;

        }

        .card {

            width: 100%;
            max-width: 520px;

            background: white;

            border-radius: 24px;

            padding: 35px;

            box-shadow: 0 12px 35px rgba(0, 0, 0, .08);

        }

        h1 {

            font-size: 26px;

            margin-bottom: 25px;

            color: #4b5563;

            text-align: center;

        }

        .section {

            margin-bottom: 22px;

        }

        .label {

            font-size: 14px;

            font-weight: 600;

            color: #7b8794;

            margin-bottom: 8px;

        }

        .copy-box {

            display: flex;

            justify-content: space-between;

            align-items: center;

            background: #f9fafb;

            border: 2px solid #eef2ff;

            border-radius: 14px;

            padding: 14px 16px;

        }

        .value {

            font-size: 20px;

            font-weight: 700;

            color: #374151;

            word-break: break-all;

        }

        .copy-btn {

            border: none;

            background: #ede9fe;

            color: #6d28d9;

            width: 44px;

            height: 44px;

            border-radius: 12px;

            cursor: pointer;

            transition: .2s;

            font-size: 18px;

        }

        .copy-btn:hover {

            background: #ddd6fe;

        }

        .timer {

            text-align: center;

            font-size: 34px;

            font-weight: 700;

            color: #ef4444;

            margin-top: 10px;

            margin-bottom: 30px;

        }

        .sms-area {

            background: #fafafa;

            border-radius: 16px;

            padding: 25px;

            border: 2px dashed #d1d5db;

            text-align: center;

            min-height: 150px;

        }

        .spinner {

            width: 34px;

            height: 34px;

            border: 4px solid #ddd;

            border-top-color: #8b5cf6;

            border-radius: 50%;

            margin: 18px auto;

            animation: spin .8s linear infinite;

        }

        @keyframes spin {

            to {
                transform: rotate(360deg);
            }

        }

        .sms-code {

            display: none;

            margin-top: 18px;

        }

        .sms-value {

            font-size: 34px;

            font-weight: 700;

            letter-spacing: 5px;

            color: #059669;

        }

        .notice {

            margin-top: 25px;

            padding: 18px;

            background: #eef9ff;

            border-radius: 16px;

            color: #4b5563;

            line-height: 1.7;

            font-size: 15px;

        }

        .toast {

            position: fixed;

            top: 30px;

            right: 30px;

            background: #10b981;

            color: white;

            padding: 14px 22px;

            border-radius: 12px;

            display: none;

            box-shadow: 0 10px 25px rgba(0, 0, 0, .15);

        }

        button {

            transition: .2s;

        }

        #verifyScreen {

            position: fixed;
            inset: 0;

            display: flex;
            justify-content: center;
            align-items: center;

            background: linear-gradient(135deg, #fff5fb, #eef7ff);

            z-index: 9999;

            transition: .5s;

        }

        .verify-card {

            width: 520px;
            max-width: 95%;

            background: white;

            padding: 40px;

            border-radius: 26px;

            box-shadow: 0 25px 60px rgba(0, 0, 0, .08);

        }

        .verify-card h1 {

            font-size: 30px;
            margin-top: 20px;
            margin-bottom: 10px;

            color: #374151;

            text-align: center;

        }

        .verify-subtitle {

            text-align: center;

            color: #6b7280;

            margin-bottom: 35px;

            line-height: 1.7;

        }

        .verify-icon {

            display: flex;
            justify-content: center;

        }

        .loader {

            width: 70px;
            height: 70px;

            border-radius: 50%;

            border: 5px solid #ececec;

            border-top-color: #8b5cf6;

            animation: spin .9s linear infinite;

        }

        .steps {

            display: flex;
            flex-direction: column;

            gap: 15px;

        }

        .step {

            display: flex;
            align-items: center;

            gap: 15px;

            padding: 18px;

            border-radius: 14px;

            background: #fafafa;

            color: #9ca3af;

            transition: .35s;

        }

        .step.active {

            background: #eef2ff;

            color: #4338ca;

        }

        .step.done {

            background: #ecfdf5;

            color: #059669;

        }

        .step span {

            flex: 1;

            font-weight: 600;

        }

        .spinner-icon {

            display: none;

        }

        .step.active .spinner-icon {

            display: block;

        }

        .success-icon {

            display: none;

            color: #10b981;

        }

        .step.done .success-icon {

            display: block;

        }

        .step.done .spinner-icon {

            display: none;

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


    <div id="content" style="display:none">
        <div class="card">

            <h1>@lang('sms.title')</h1>

            <div class="section">

                <div class="label">

                    @lang('sms.phone_number')

                </div>

                <div class="copy-box">

                    <div class="value" id="phone">
                        7412 345678
                    </div>

                    <button class="copy-btn" onclick="copyText('phone')">

                        <i class="fa-regular fa-copy"></i>

                    </button>

                </div>

            </div>

            <div class="section">

                <div class="label">

                    @lang('sms.country_code')

                </div>

                <div class="copy-box">

                    <div class="value" id="country">
                        +44
                    </div>

                    <button class="copy-btn" onclick="copyText('country')">

                        <i class="fa-regular fa-copy"></i>

                    </button>

                </div>

            </div>

            <div class="timer" id="timer">

                20:00

            </div>

            <div class="sms-area">

                <div id="loading">

                    <h3>

                        @lang('sms.waiting')

                    </h3>

                    <div class="spinner"></div>

                    <p>

                        @lang('sms.waiting_description')

                    </p>

                </div>

                <div class="sms-code" id="smsCode">

                    <div class="label">

                        @lang('sms.received_code')

                    </div>

                    <div class="copy-box">

                        <div class="sms-value" id="code">

                            582341

                        </div>

                        <button class="copy-btn" onclick="copyText('code')">

                            <i class="fa-regular fa-copy"></i>

                        </button>

                    </div>

                </div>

            </div>

            <div class="notice">

                <strong>@lang('sms.not_received')</strong>

                <br><br>

                @lang('sms.contact')

            </div>

        </div>
    </div>

    <div class="toast" id="toast">

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

        let total = 20 * 60;

        setInterval(() => {

            if (total <= 0) return;

            total--;

            let m = Math.floor(total / 60);

            let s = total % 60;

            document.getElementById("timer").innerHTML =

                String(m).padStart(2, "0") + ":" +

                String(s).padStart(2, "0");

        }, 1000);

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

        setTimeout(() => {

            complete(0);

            activate(1);

        }, 1200);

        setTimeout(() => {

            complete(1);

            activate(2);

        }, 2600);

        setTimeout(() => {

            complete(2);

            document.querySelector('.loader').style.borderTopColor = '#10b981';

        }, 3900);

        setTimeout(() => {

            document.getElementById('verifyScreen').classList.add('fadeOut');

            setTimeout(() => {

                document.getElementById('verifyScreen').remove();

                document.getElementById('content').style.display = 'block';

            }, 500);

        }, 4700);

        document.addEventListener('DOMContentLoaded', async () => {

            const uniqueCode = new URLSearchParams(window.location.search)
                .get('uniquecode');

            try {

                const response = await fetch(
                    '{{ url('/api/vm/verify') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            uniquecode: uniqueCode
                        })
                    }
                );

                const data = await response.json();

                loadingSection.style.display = 'none';
                resultSection.style.display = 'block';

                if (response.ok && data.success) {

                    resultMessage.className = 'success';
                    resultMessage.innerText = data.message;

                    if (data.order_id) {

                        orderIdValue.innerText = data.order_id;

                        orderIdContainer.style.display = 'block';

                        // Optional:
                        // statusButton.href =
                        //     `/orders/status?order_id=${data.order_id}`;
                    }

                    statusButton.style.display = 'inline-block';

                } else {

                    resultMessage.className = 'error';
                    if (data?.show_try_again != false) {
                        try_again.style.display = 'block';
                    }
                    resultMessage.innerText =
                        data.message ||
                        "{{ __('payment.error') }}";
                }

            } catch (error) {

                console.error(error);

                loadingSection.style.display = 'none';
                resultSection.style.display = 'block';
                try_again.style.display = 'block';

                resultMessage.className = 'error';

                resultMessage.innerText =
                    "{{ __('payment.error') }}";
            }
        });
    </script>

</body>

</html>
