<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('status.title') }}</title>

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

        .form-group {
            margin-top: 20px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .btn {
            display: inline-block;
            margin-top: 15px;
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

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #ddd;
            border-top: 4px solid #0d6efd;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
            display: none;
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
            margin-top: 20px;
        }

        .error {
            color: #dc3545;
            margin-top: 20px;
        }

        .status-box {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            display: none;
        }

        #order-details {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
        }

        #order-details table {
            width: 100%;
            border-collapse: collapse;
        }

        #order-details td {
            padding: 8px;
            vertical-align: top;
        }

        #order-details tr:not(:last-child) {
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>

<body>

    <div class="card">

        <h2>{{ __('status.heading') }}</h2>

        <div class="form-group">
            <input type="text" id="order_id" class="form-control" placeholder="{{ __('status.order_id_placeholder') }}">
        </div>

        <button id="check_btn" class="btn">
            {{ __('status.check_button') }}
        </button>

        <div id="loading" class="spinner"></div>

        <div id="status-box" class="status-box">
            <h3 id="status-message"></h3>

            <div id="order-details" style="display:none; margin-top:20px;">
                <table style="width:100%; text-align:left;">
                    <tr>
                        <td><strong>{{ __('status.order_status') }}</strong></td>
                        <td id="detail-status"></td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('status.quantity') }}</strong></td>
                        <td id="detail-quantity"></td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('status.remains') }}</strong></td>
                        <td id="detail-remains"></td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('status.link') }}</strong></td>
                        <td>
                            <a id="detail-link" href="" target="_blank"></a>
                        </td>
                    </tr>
                </table>
            </div>
        </div>



    </div>

    <script>
        document.getElementById('check_btn').addEventListener('click', async () => {

            const orderId = document.getElementById('order_id').value.trim();

            if (!orderId) {
                return;
            }

            const loading = document.getElementById('loading');
            const statusBox = document.getElementById('status-box');
            const statusMessage = document.getElementById('status-message');

            const orderDetails = document.getElementById('order-details');
            const detailStatus = document.getElementById('detail-status');
            const detailQuantity = document.getElementById('detail-quantity');
            const detailRemains = document.getElementById('detail-remains');
            const detailLink = document.getElementById('detail-link');

            loading.style.display = 'block';
            statusBox.style.display = 'none';

            try {

                const response = await fetch('{{ url('/api/order-status') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderId
                    })
                });

                const data = await response.json();

                loading.style.display = 'none';
                statusBox.style.display = 'block';

                if (response.ok && data.success) {

                    statusMessage.className = 'success';
                    statusMessage.innerText = data.message;

                    detailStatus.innerText = data.order.status;
                    detailQuantity.innerText = data.order.quantity;
                    detailRemains.innerText = data.order.remains;

                    detailLink.href = data.order.link;
                    detailLink.innerText = data.order.link;

                    orderDetails.style.display = 'block';

                } else {

                    orderDetails.style.display = 'none';

                    statusMessage.className = 'error';
                    statusMessage.innerText =
                        data.message ||
                        "{{ __('status.not_found') }}";
                }

            } catch (error) {

                loading.style.display = 'none';
                statusBox.style.display = 'block';

                statusMessage.className = 'error';
                statusMessage.innerText =
                    "{{ __('status.error') }}";
            }
        });
    </script>

</body>

</html>
