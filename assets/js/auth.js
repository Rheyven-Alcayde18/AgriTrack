// assets/js/auth.js
// Sign up, sign in, sign out and page protection.
// Every Firebase import uses the same version (11.0.2) as firebase.js.
import { auth, database } from "./firebase.js";
import {
  createUserWithEmailAndPassword,
  signInWithEmailAndPassword,
  signOut,
  onAuthStateChanged,
  updateProfile
} from "https://www.gstatic.com/firebasejs/11.0.2/firebase-auth.js";
import { ref, get, set } from "https://www.gstatic.com/firebasejs/11.0.2/firebase-database.js";

const msg = document.getElementById("message");
const show = (text) => { if (msg) msg.textContent = text; };

// Today in the Philippines, as YYYY-MM-DD
const today = () => new Intl.DateTimeFormat("en-CA", { timeZone: "Asia/Manila", year: "numeric", month: "2-digit", day: "2-digit" }).format(new Date());

// Reads users/{uid}. Accounts made before profiles existed get a farmer profile on first sign-in.
async function loadProfile(user, name) {
  const r = ref(database, "users/" + user.uid);
  const snap = await get(r);
  if (snap.exists()) return snap.val();
  const profile = {
    name: name || user.displayName || (user.email || "").split("@")[0] || "Farmer",
    email: user.email || "", phone: "", municipality: "Bay", barangay: "", farm_size: 0,
    role: "farmer", status: "active", joined: today(), lastActive: today()
  };
  await set(r, profile);
  return profile;
}

const friendly = (err) => {
  const c = err && err.code;
  if (c === "auth/email-already-in-use") return "That email is already registered. Try signing in instead.";
  if (c === "auth/weak-password") return "Password should be at least 6 characters.";
  if (c === "auth/invalid-email") return "Please enter a valid email address.";
  if (c === "PERMISSION_DENIED" || (err && /permission/i.test(err.message || ""))) return "The database rules are not published yet. Paste database.rules.json into Firebase > Realtime Database > Rules.";
  return (err && err.message) || "Something went wrong. Please try again.";
};

// Show the message left by a redirect (deactivated account)
if (msg && new URLSearchParams(location.search).get("deactivated") === "1") {
  show("This account has been deactivated. Please contact your administrator.");
}

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
      await loadProfile(cred.user, name); // saves users/{uid}
      window.location.href = "user/dashboard.php";
    } catch (err) {
      show(friendly(err));
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

    let cred;
    try {
      cred = await signInWithEmailAndPassword(auth, email, password);
    } catch (err) {
      return show("Invalid email or password.");
    }
    try {
      const profile = await loadProfile(cred.user);
      if (profile.status === "inactive") {
        await signOut(auth);
        return show("This account has been deactivated. Please contact your administrator.");
      }
      window.location.href = profile.role === "admin" ? "admin/dashboard.php" : "user/dashboard.php";
    } catch (err) {
      show(friendly(err));
    }
  });
}

// PROTECTED PAGES: redirect when signed out, show the name, handle sign out
const logoutBtn = document.getElementById("logoutBtn");
if (logoutBtn) {
  onAuthStateChanged(auth, (user) => {
    if (!user) return (window.location.href = "../index.html");
    const w = document.getElementById("welcome");
    if (w && !w.textContent) w.textContent = "Welcome, " + (user.displayName || user.email) + "!";
  });
  logoutBtn.addEventListener("click", async () => {
    await signOut(auth);
    window.location.href = "../index.html";
  });
}