// Customer Manager - small helpers for the menu, search, add/edit form and saved passwords.
(function () {
  'use strict';

  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

  // ----- mobile menu -----
  var menuBtn = $('#menuToggle');
  var sidebar = $('#sidebar');
  if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', function () { document.body.classList.toggle('nav-open'); });
    document.addEventListener('click', function (e) {
      if (document.body.classList.contains('nav-open') && !sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
        document.body.classList.remove('nav-open');
      }
    });
  }

  // ----- messages -----
  $$('[data-dismiss]').forEach(function (btn) {
    btn.addEventListener('click', function () { btn.closest('.flash').remove(); });
  });

  // ----- year filter -----
  $$('[data-autosubmit]').forEach(function (sel) {
    sel.addEventListener('change', function () { sel.form.submit(); });
  });

  // ----- quick search -----
  var search = $('#tableSearch');
  var table = $('#dataTable');
  if (search && table) {
    search.addEventListener('input', function () {
      var q = search.value.trim().toLowerCase();
      $$('tbody tr', table).forEach(function (tr) {
        tr.hidden = q !== '' && tr.textContent.toLowerCase().indexOf(q) === -1;
      });
    });
  }

  // ----- delete confirmation -----
  $$('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirm'))) { e.preventDefault(); }
    });
  });

  // ----- add / edit dialog -----
  var dlg = $('#formDialog');
  var form = $('#recordForm');
  if (!dlg || !form) { return; }

  var title = $('#dialogTitle');
  var canReveal = dlg.getAttribute('data-can-reveal') === '1';

  function openDialog() {
    if (typeof dlg.showModal === 'function') { dlg.showModal(); } else { dlg.setAttribute('open', ''); }
    var first = $('input:not([type="hidden"]), select, textarea', form);
    if (first) { first.focus(); }
  }
  function closeDialog() {
    if (typeof dlg.close === 'function') { dlg.close(); } else { dlg.removeAttribute('open'); }
  }

  function prepareSecrets(data) {
    var editing = !!data;
    $$('[data-secret]', form).forEach(function (input) {
      var field = input.closest('.field');
      var hint = $('[data-secret-hint]', field);
      var reveal = $('[data-reveal]', field);
      var isPassword = input.hasAttribute('data-pw');
      var hasSaved = editing && (isPassword || !!data['__has_' + input.name]);

      input.value = '';
      input.type = 'password';
      input.required = isPassword && !editing;
      if (hint) { hint.hidden = !hasSaved; }
      if (reveal) { reveal.hidden = !(hasSaved && canReveal && !isPassword); }
    });
  }

  $$('[data-add]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      form.reset();
      form.elements['id'].value = '0';
      title.textContent = dlg.getAttribute('data-title-add');
      prepareSecrets(null);
      openDialog();
    });
  });

  $$('[data-edit]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var data = JSON.parse(btn.getAttribute('data-edit'));
      form.reset();
      Object.keys(data).forEach(function (key) {
        if (key.indexOf('__has_') === 0) { return; }
        var el = form.elements[key];
        if (!el) { return; }
        if (el.type === 'checkbox') { el.checked = String(data[key]) === '1'; } else { el.value = data[key]; }
      });
      form.elements['id'].value = String(data.id);
      title.textContent = dlg.getAttribute('data-title-edit');
      prepareSecrets(data);
      openDialog();
    });
  });

  $$('[data-close]', dlg).forEach(function (btn) { btn.addEventListener('click', closeDialog); });
  dlg.addEventListener('click', function (e) { if (e.target === dlg) { closeDialog(); } });

  // show / hide what is typed in a password box
  form.addEventListener('click', function (e) {
    var t = e.target.closest('[data-toggle-secret]');
    if (!t) { return; }
    var input = $('input', t.closest('.secret-wrap'));
    input.type = input.type === 'password' ? 'text' : 'password';
  });

  // load the saved (encrypted) value from the server
  form.addEventListener('click', function (e) {
    var b = e.target.closest('[data-reveal]');
    if (!b) { return; }
    var input = form.elements[b.getAttribute('data-reveal')];
    var body = new FormData();
    body.append('csrf', form.elements['csrf'].value);
    body.append('m', form.getAttribute('data-slug'));
    body.append('id', form.elements['id'].value);
    body.append('field', b.getAttribute('data-reveal'));

    b.disabled = true;
    fetch('reveal.php', { method: 'POST', body: body, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (j.ok) { input.value = j.value; input.type = 'text'; } else { window.alert(j.error || 'Could not load the saved value.'); }
      })
      .catch(function () { window.alert('Could not load the saved value.'); })
      .then(function () { b.disabled = false; });
  });
})();
