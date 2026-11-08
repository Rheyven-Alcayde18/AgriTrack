import { auth } from "./firebase.js";
import {
  createUserWithEmailAndPassword,
  signInWithEmailAndPassword,
  signOut,
  onAuthStateChanged,
  updateProfile
} from "https://www.gstatic.com/firebasejs/10.12.0/firebase-auth.js";

const msg = document.getElementById("message");
const show = (text) => { if (msg) msg.textContent = text; };

// REGISTER
const registerForm = document.getElementById("registerForm");
if (registerForm) {
  registerForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;
    const confirm = document.getElementById("confirm").value;

    if (password !== confirm) return show("Passwords do not match.");

    try {
      const cred = await createUserWithEmailAndPassword(auth, email, password);
      await updateProfile(cred.user, { displayName: name });
      window.location.href = "user/dashboard.php";
    } catch (err) {
      show(err.message);
    }
  });
}

// LOGIN
const loginForm = document.getElementById("loginForm");
if (loginForm) {
  loginForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;

    try {
      await signInWithEmailAndPassword(auth, email, password);
      window.location.href = "user/dashboard.html";
    } catch (err) {
      show("Invalid email or password.");
    }
  });
}

// DASHBOARD: protect page + logout
const logoutBtn = document.getElementById("logoutBtn");
if (logoutBtn) {
  onAuthStateChanged(auth, (user) => {
    if (!user) return (window.location.href = "../index.html");
    document.getElementById("welcome").textContent =
      "Welcome, " + (user.displayName || user.email) + "!";
  });
  logoutBtn.addEventListener("click", async () => {
    await signOut(auth);
    window.location.href = "../index.html";
  });
}