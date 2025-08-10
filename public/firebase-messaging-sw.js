importScripts(
    "https://www.gstatic.com/firebasejs/9.6.10/firebase-app-compat.js",
);
importScripts(
    "https://www.gstatic.com/firebasejs/9.6.10/firebase-messaging-compat.js",
);

const firebaseConfig = {
    apiKey: "AIzaSyBO9U28bXjlMOqsaS9YR3Q_Yn6zmV7uvhI",
    authDomain:
        "https://sairjayamandiri-default-rtdb.asia-southeast1.firebasedatabase.app",
    projectId: "sairjayamandiri",
    messagingSenderId: "563902439042",
    appId: "1:563902439042:web:167abd4d53f56f81881e9b",
};

firebase.initializeApp(firebaseConfig);

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function (payload) {
    console.log(
        "[firebase-messaging-sw.js] Received background message ",
        payload,
    );

    const notificationTitle = payload.notification.title;
    const notificationOptions = {
        body: payload.notification.body,
        icon: "/path-to-your-icon.png", // opsional
    };

    return self.registration.showNotification(
        notificationTitle,
        notificationOptions,
    );
});
