import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const pusherKey = document
    .querySelector('meta[name="pusher-key"]')
    ?.getAttribute('content');

const pusherCluster = document
    .querySelector('meta[name="pusher-cluster"]')
    ?.getAttribute('content') || 'mt1';

window.Echo = null;

if (pusherKey) {
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: pusherCluster,
        forceTLS: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '',
            },
        },
    });
} else {
    console.error(
        'Pusher is not configured. Set PUSHER_APP_KEY in the Laravel environment.',
    );
}
