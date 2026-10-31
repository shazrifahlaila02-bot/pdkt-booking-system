'use strict';
const menu = document.querySelector('.buka-menu');
const sidebar = document.querySelector('.sisi');
function tutupMenu() {
  document.body.classList.remove('menu-dibuka');
  if (menu) menu.setAttribute('aria-expanded', 'false');
}
if (menu && sidebar) {
  menu.addEventListener('click', () => {
    const open = document.body.classList.toggle('menu-dibuka');
    menu.setAttribute('aria-expanded', String(open));
    menu.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');
  });
  document.addEventListener('keydown', event => { if (event.key === 'Escape') { tutupMenu(); menu.focus(); } });
  document.addEventListener('click', event => { if (!sidebar.contains(event.target) && !menu.contains(event.target)) tutupMenu(); });
  window.matchMedia('(min-width: 901px)').addEventListener('change', tutupMenu);
}
// Announce active page to assistive technologies.
document.querySelectorAll('.menu a.aktif').forEach(link => link.setAttribute('aria-current', 'page'));
// Malay browser validation messages for application-owned inputs.
const mesej = input => {
  input.setCustomValidity('');
  if (input.validity.valueMissing) input.setCustomValidity(input.type === 'checkbox' ? 'Sila tandakan pilihan ini.' : input.tagName === 'SELECT' ? 'Sila pilih satu pilihan.' : 'Sila lengkapkan ruangan ini.');
  else if (input.validity.typeMismatch) input.setCustomValidity('Sila masukkan alamat e-mel yang sah.');
  else if (input.validity.tooShort) input.setCustomValidity('Sila masukkan sekurang-kurangnya ' + input.minLength + ' aksara.');
  else if (input.validity.rangeUnderflow) input.setCustomValidity('Sila pilih tarikh pada atau selepas ' + input.min + '.');
  else if (input.validity.rangeOverflow) input.setCustomValidity('Sila pilih tarikh pada atau sebelum ' + input.max + '.');
  else if (input.validity.badInput) input.setCustomValidity('Sila masukkan maklumat yang sah.');
};
document.querySelectorAll('input, select, textarea').forEach(input => {
  input.addEventListener('invalid', () => mesej(input));
  input.addEventListener('input', () => input.setCustomValidity(''));
  input.addEventListener('change', () => input.setCustomValidity(''));
});
