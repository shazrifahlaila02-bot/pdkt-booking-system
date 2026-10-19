'use strict';
const start = document.querySelector('#start-date');
const end = document.querySelector('#end-date');
if (start && end) {
  start.addEventListener('change', () => {
    end.min = start.value || start.min;
    if (end.value && end.value < end.min) end.value = '';
  });
}
