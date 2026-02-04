import './bootstrap';
import '../css/app.css';

import './../../vendor/power-components/livewire-powergrid/dist/powergrid';

import flatpickr from "flatpickr";
import { Portuguese } from 'flatpickr/dist/l10n/pt.js';

import TomSelect from "tom-select";
window.TomSelect = TomSelect;

import SlimSelect from 'slim-select';
window.SlimSelect = SlimSelect;

/**
 * Fullscreen por tabela:
 * espera receber: window.dispatchEvent(new CustomEvent('pg-toggle-fullscreen', { detail: { table, on } }))
 */
function setFullscreen(table, on) {
  const wrap = document.getElementById(`pg-wrap-${table}`);
  if (!wrap) return;

  wrap.classList.toggle('pg-fullscreen', !!on);
  document.body.classList.toggle('pg-lock-scroll', !!on);
}

// evento vindo do Livewire (ELE/TUB)
window.addEventListener('pg-toggle-fullscreen', (e) => {
  const { table, on } = e.detail || {};
  if (!table) return;
  setFullscreen(table, on);
});

// ESC: fecha qualquer fullscreen ativo (sem depender do Livewire)
window.addEventListener('keydown', (e) => {
  if (e.key !== 'Escape') return;

  const openWraps = document.querySelectorAll('.pg-fullscreen[id^="pg-wrap-"]');
  if (!openWraps.length) return;

  // fecha visualmente
  openWraps.forEach((wrap) => wrap.classList.remove('pg-fullscreen'));
  document.body.classList.remove('pg-lock-scroll');

  // sincroniza Livewire (desliga a flag no componente)
  openWraps.forEach((wrap) => {
    const table = wrap.id.replace('pg-wrap-', '');

    // regra: se for ELE chama closeFullscreenEle, senão closeFullscreenTub
    // (pode ajustar se tiver mais tabelas no futuro)
    if (table.includes('levantamento-ele-table') || table.includes('varredura-ele-table')) {
      Livewire.dispatch('closeFullscreenEle');
    } else if (table.includes('levantamento-tub-table') || table.includes('varredura-tub-table')) {
      Livewire.dispatch('closeFullscreenTub');
    }
  });
  if (window.Livewire) {
    window.Livewire.dispatch('closeDescricao');
  }  
});
