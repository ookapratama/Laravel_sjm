import Echo from "laravel-echo";
import Pusher from "pusher-js";
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === "https",
    enabledTransports: ["ws", "wss"],
});

window.Echo.connector.pusher.connection.bind("connected", function () {
    console.log("✅ Echo connected to Pusher!");
});

// ✅ Public channel test
window.Echo.channel("members").listen(".BroadcastTest", (e) => {
    console.log("📡 BroadcastTest diterima:", e);
});

// ✅ Private channel untuk sponsor/upline
if (window.userId) {
    console.log("⏳ Subscribe ke channel: upline." + window.userId);

    window.Echo.private(`upline.${window.userId}`).listen(
        ".NewMemberApproved",
        (e) => {
            console.log("📦 Member baru diterima:", e);
            toastr.success(`Member baru bergabung: ${e.name} (${e.username})`);
        },
    );
} else {
    console.warn("❗ window.userId tidak ditemukan");
}
