// assets/js/admin-settings.js
// Admin settings: account name, password reset email, Firebase counts and sample-data tools.

import {
  auth, requireUser, listAllUsers, listAll, loadMarkets, updateUser,
  seedMarkets, seedSample, clearSample, signOutAndGo, explain, showError, toast,
} from './db.js';
import { sendPasswordResetEmail } from 'https://www.gstatic.com/firebasejs/11.0.2/firebase-auth.js';

const $ = (id) => document.getElementById(id);

let session = null;

function confirmAction(title, text, okLabel, danger) {
  const dlg = $('confirmDlg');
  $('confirmTitle').textContent = title;
  $('confirmText').textContent = text;
  const ok = $('confirmOk');
  const cancel = $('confirmCancel');
  ok.textContent = okLabel;
  ok.className = 'st-btn primary' + (danger ? ' danger-solid' : '');
  return new Promise((resolve) => {
    function done(result) {
      ok.removeEventListener('click', onOk);
      cancel.removeEventListener('click', onCancel);
      dlg.removeEventListener('close', onCancel);
      if (dlg.open) dlg.close();
      resolve(result);
    }
    const onOk = () => done(true);
    const onCancel = () => done(false);
    ok.addEventListener('click', onOk);
    cancel.addEventListener('click', onCancel);
    dlg.addEventListener('close', onCancel);
    dlg.showModal();
  });
}

async function busy(btn, fn) {
  btn.disabled = true;
  try { await fn(); } finally { btn.disabled = false; }
}

async function loadCounts() {
  try {
    const [users, crops, markets] = await Promise.all([listAllUsers(), listAll('crops'), loadMarkets()]);
    const farmers = users.filter((u) => u.role !== 'admin');
    $('cFarmers').textContent = farmers.length;
    $('cCrops').textContent = Object.values(crops).reduce((n, byUser) => n + Object.keys(byUser || {}).length, 0);
    $('cMarkets').textContent = markets.length;
    $('cSample').textContent = farmers.filter((u) => u.sample).length;
  } catch (e) {
    console.error(e);
    showError(explain(e, 'Could not read the counts.'));
  }
}

async function init() {
  session = await requireUser({ role: 'admin' });
  const { user, profile } = session;

  $('adminName').value = profile.name || user.displayName || '';
  $('adminEmail').value = user.email || profile.email || '';
  $('iUid').textContent = user.email || user.uid;
  try {
    const opts = (auth.app && auth.app.options) || {};
    $('iProject').textContent = opts.projectId || '--';
    $('iDb').textContent = opts.databaseURL || '--';
  } catch (e) { /* leave placeholders */ }

  $('profileForm').addEventListener('submit', (ev) => {
    ev.preventDefault();
    const name = $('adminName').value.trim();
    if (!name) return;
    busy($('saveProfile'), async () => {
      try {
        await updateUser(user.uid, { name });
        toast('Your name was saved.');
      } catch (e) {
        console.error(e);
        toast('Could not save. ' + (e && e.code === 'PERMISSION_DENIED' ? 'Your database rules do not allow this.' : 'Check your connection.'));
      }
    });
  });

  $('resetPw').addEventListener('click', () => busy($('resetPw'), async () => {
    try {
      await sendPasswordResetEmail(auth, user.email);
      toast('Reset link sent to ' + user.email);
    } catch (e) {
      console.error(e);
      toast('Could not send the email. Please try again.');
    }
  }));

  $('signOutBtn').addEventListener('click', signOutAndGo);
  $('refreshBtn').addEventListener('click', () => busy($('refreshBtn'), loadCounts));

  $('seedMarkets').addEventListener('click', () => busy($('seedMarkets'), async () => {
    try {
      const added = await seedMarkets();
      toast(added ? 'Market listings added.' : 'Market listings already exist.');
      await loadCounts();
    } catch (e) { console.error(e); showError(explain(e, 'Could not add markets.')); }
  }));

  $('seedSample').addEventListener('click', async () => {
    const yes = await confirmAction('Add sample farmers?',
      'Demo farmers, crops and harvests will be added so the dashboard has data. You can remove them any time.', 'Add sample data', false);
    if (!yes) return;
    busy($('seedSample'), async () => {
      try {
        await seedSample();
        toast('Sample data added.');
        await loadCounts();
      } catch (e) { console.error(e); showError(explain(e, 'Could not add sample data.')); }
    });
  });

  $('clearSample').addEventListener('click', async () => {
    const yes = await confirmAction('Remove all sample farmers?',
      'Only the demo farmers and the data saved under them will be deleted. Real farmer accounts stay as they are.', 'Remove sample data', true);
    if (!yes) return;
    busy($('clearSample'), async () => {
      try {
        const n = await clearSample();
        toast(n ? n + ' sample farmers removed.' : 'There was no sample data to remove.');
        await loadCounts();
      } catch (e) { console.error(e); showError(explain(e, 'Could not remove sample data.')); }
    });
  });

  await loadCounts();
}

init();