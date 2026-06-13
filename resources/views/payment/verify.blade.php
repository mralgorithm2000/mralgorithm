<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('payment.title') }}</title>

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f4f6f9;
            font-family: Arial, sans-serif;
        }

        .card {
            width: 550px;
            max-width: 90%;
            background: white;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .1);
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #ddd;
            border-top: 5px solid #0d6efd;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 25px auto;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .success {
            color: #198754;
        }

        .error {
            color: #dc3545;
        }

        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #0d6efd;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn:hover {
            opacity: .9;
        }

        .order-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .order-id {
            font-size: 18px;
            font-weight: bold;
            word-break: break-all;
        }

        .copy-success {
            color: #198754;
            margin-top: 10px;
        }

        .message {
            margin-top: 15px;
        }

        .try-again-container {
            text-align: center;
            margin-top: 20px;
        }

        .try-again-btn {
            background-color: #0dcaf0;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .try-again-btn:hover {
            background-color: #31d2f2;
        }
    </style>
</head>

<body>

    <div class="card">

        <h2>{{ __('payment.thanks') }}</h2>

        {{-- Loading --}}
        <div id="loading-section">
            <div class="spinner"></div>
            <p>{{ __('payment.verifying') }}</p>
        </div>

        {{-- Result --}}
        <div id="result-section" style="display:none;">

            <h3 id="result-message"></h3>

            <div class="try-again-container">
                <button type="button" id="try_again" class="try-again-btn" style="display:none"
                    onclick="refreshPage()">
                    @lang('payment.try_again')
                </button>
            </div>

            {{-- Order ID --}}
            <div id="order-id-container" style="display:none; margin-top:20px;">

                <p class="message">
                    {{ __('payment.copy_order_message') }}
                </p>

                <div class="order-box">
                    <span id="order-id-value" class="order-id"></span>

                    <button id="copy-order-btn" type="button" class="btn btn-sm">
                        {{ __('payment.copy') }}
                    </button>
                </div>

                <p id="copy-success" class="copy-success" style="display:none;">
                    {{ __('payment.copied') }}
                </p>

            </div>

            {{-- Status Button --}}
            <a id="status-button" href="/orders/status" class="btn" style="display:none;">
                {{ __('payment.status_button') }}
            </a>

        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {

            const uniqueCode = new URLSearchParams(window.location.search)
                .get('uniquecode');

            const loadingSection = document.getElementById('loading-section');
            const resultSection = document.getElementById('result-section');
            const resultMessage = document.getElementById('result-message');
            const statusButton = document.getElementById('status-button');

            const orderIdContainer = document.getElementById('order-id-container');
            const orderIdValue = document.getElementById('order-id-value');
            const copySuccess = document.getElementById('copy-success');
            const copyBtn = document.getElementById('copy-order-btn');
            const try_again = document.getElementById('try_again');

            // Copy order ID
            copyBtn.addEventListener('click', async () => {

                const orderId = orderIdValue.innerText;

                if (!orderId) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(orderId);

                    copySuccess.style.display = 'block';

                    setTimeout(() => {
                        copySuccess.style.display = 'none';
                    }, 3000);

                } catch (err) {
                    console.error(err);
                }
            });

            try {

                const response = await fetch(
                    '{{ url('/api/verify') }}', {
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
                    resultMessage.innerText =
                        "{{ __('payment.success') }}";

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
                    if (response?.show_try_again != false) {
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

        function refreshPage() {
            window.location.reload();
        }
    </script>

</body>

</html>
