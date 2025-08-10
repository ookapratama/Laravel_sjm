import { initializeApp } from "firebase/app";
import { getDatabase, ref, onValue, set } from "firebase/database";

const firebaseConfig = {
    apiKey: "AIzaSyBO9U28bXjlMOqsaS9YR3Q_Yn6zmV7uvhI",
    authDomain: "sairjayamandiri.firebaseapp.com",
    databaseURL:
        "https://sairjayamandiri-default-rtdb.asia-southeast1.firebasedatabase.app",
    projectId: "sairjayamandiri",
    storageBucket: "sairjayamandiri.firebasestorage.app",
    messagingSenderId: "563902439042",
    appId: "1:563902439042:web:167abd4d53f56f81881e9b",
    measurementId: "G-8H9Y1WVW59",
};

const app = initializeApp(firebaseConfig);
const db = getDatabase(app);

export { db, ref, onValue, set };
