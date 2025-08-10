<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusher Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h1>Pusher Test Page</h1>
    <p>User ID: {{ $user->id }}</p>

    <script src="https://js.pusher.com/8.0/pusher.min.js"></script>
    <script>
        console.log('Pusher Key:', '{{ config('broadcasting.connections.pusher.key') }}');
        console.log('Pusher Cluster:', '{{ config('broadcasting.connections.pusher.options.cluster') }}');

        const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
            cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }
        });

        const userId = '{{ $user->id }}';
        const channel = pusher.subscribe(`private-notifications.${userId}`);

        channel.bind('pusher:subscription_succeeded', () => {
            console.log('Subscription berhasil!');
        });
        channel.bind('pusher:subscription_error', (status) => {
            console.error('Subscription gagal:', status);
        });
    </script>
</body>
</html>