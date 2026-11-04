
import { initializeApp } from
"https://www.gstatic.com/firebasejs/11.0.2/firebase-app.js";

import { getAuth } from
"https://www.gstatic.com/firebasejs/11.0.2/firebase-auth.js";

import { getDatabase } from
"https://www.gstatic.com/firebasejs/11.0.2/firebase-database.js";

// Firebase Configuration
const firebaseConfig = {
    apiKey: "AIzaSyD5tdY_y88RaEM9bc1n1ZwUo-3MxfEog1Y",
    authDomain: "agritrack-4137a.firebaseapp.com",

    databaseURL: "https://agritrack-4137a-default-rtdb.asia-southeast1.firebasedatabase.app",

    projectId: "agritrack-4137a",
    storageBucket: "agritrack-4137a.firebasestorage.app",
    messagingSenderId: "39904209601",
    appId: "1:39904209601:web:38473e6c83a6e5f9ff0240"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);

// Initialize Firebase Services
export const auth = getAuth(app);
export const database = getDatabase(app);

console.log("AgriTrack Firebase Connected!");
