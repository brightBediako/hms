(function () {
  const form = document.getElementById('reservation-form');
  if (!form) return;

  const checkIn = document.getElementById('check_in_date');
  const checkOut = document.getElementById('check_out_date');
  const roomSelect = document.getElementById('room_id');
  const rateInput = document.getElementById('agreed_rate');
  const hint = document.getElementById('room-availability-hint');
  const url = form.getAttribute('data-availability-url');
  const guestSearchUrl = form.getAttribute('data-guest-search-url');
  const exceptId = form.getAttribute('data-except-id') || '0';

  const newPanel = document.getElementById('guest-new-panel');
  const returningPanel = document.getElementById('guest-returning-panel');
  const guestNameInput = document.getElementById('guest_full_name');
  const guestIdInput = document.getElementById('guest_id');
  const guestSearch = document.getElementById('guest_search');
  const guestResults = document.getElementById('guest-search-results');
  const guestSelectedLabel = document.getElementById('guest-selected-label');
  const modeRadios = form.querySelectorAll('[data-guest-mode]');

  let previousCheckIn = checkIn ? checkIn.value : '';
  let searchTimer = null;

  function formatMoney(amount) {
    const n = Number(amount);
    if (Number.isNaN(n)) return amount;
    return n.toFixed(2);
  }

  function addDays(isoDate, days) {
    const d = new Date(isoDate + 'T12:00:00');
    d.setDate(d.getDate() + days);
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + day;
  }

  function syncGuestMode() {
    const mode = form.querySelector('input[name="guest_mode"]:checked');
    const isNew = !mode || mode.value === 'new';

    if (newPanel) newPanel.classList.toggle('hidden', !isNew);
    if (returningPanel) returningPanel.classList.toggle('hidden', isNew);

    if (guestNameInput) {
      guestNameInput.required = isNew;
      if (!isNew) guestNameInput.removeAttribute('required');
    }
    if (guestIdInput) {
      guestIdInput.required = !isNew;
      if (isNew) {
        guestIdInput.removeAttribute('required');
      }
    }

    modeRadios.forEach(function (radio) {
      const label = radio.closest('label');
      if (!label) return;
      const active = radio.checked;
      label.classList.toggle('border-primary', active);
      label.classList.toggle('bg-primary-fixed', active);
      label.classList.toggle('text-on-primary-fixed', active);
      label.classList.toggle('bg-surface', !active);
    });
  }

  async function refreshRooms() {
    if (!checkIn || !checkOut || !roomSelect || !url) return;

    const ci = checkIn.value;
    const co = checkOut.value;
    if (!ci || !co || co <= ci) {
      if (hint) hint.textContent = 'Check-out must be after check-in.';
      return;
    }

    const selected = roomSelect.value;
    const params = new URLSearchParams({
      check_in_date: ci,
      check_out_date: co,
      except_id: exceptId,
    });

    try {
      const res = await fetch(url + '?' + params.toString(), {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });
      const data = await res.json();
      const rooms = data.rooms || [];

      roomSelect.innerHTML = '';
      const placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.textContent = rooms.length ? 'Select available room…' : 'No rooms available for these dates';
      roomSelect.appendChild(placeholder);

      rooms.forEach(function (room) {
        const opt = document.createElement('option');
        opt.value = String(room.id);
        opt.dataset.rate = room.base_rate;
        opt.textContent =
          '#' + room.room_number + ' · ' + room.room_type_name + ' · ' + formatMoney(room.base_rate) + '/night (' + room.status + ')';
        if (String(room.id) === selected) opt.selected = true;
        roomSelect.appendChild(opt);
      });

      if (hint) {
        hint.textContent = rooms.length
          ? rooms.length + ' room(s) available for these dates.'
          : 'No rooms free — adjust dates or room type inventory.';
      }

      syncRate();
    } catch (err) {
      if (hint) hint.textContent = 'Could not refresh availability.';
    }
  }

  function syncRate() {
    if (!rateInput || !roomSelect) return;
    const opt = roomSelect.options[roomSelect.selectedIndex];
    if (opt && opt.dataset.rate) {
      rateInput.value = formatMoney(opt.dataset.rate);
    }
  }

  function onCheckInDateChange() {
    if (!checkIn || !checkOut) return;
    const ci = checkIn.value;
    if (!ci) return;

    const expectedOldCheckout = previousCheckIn ? addDays(previousCheckIn, 1) : '';
    if (!checkOut.value || checkOut.value === expectedOldCheckout || checkOut.value <= ci) {
      checkOut.value = addDays(ci, 1);
    }
    previousCheckIn = ci;
    refreshRooms();
  }

  function hideGuestResults() {
    if (guestResults) guestResults.classList.add('hidden');
  }

  function selectGuest(guest) {
    if (guestIdInput) guestIdInput.value = String(guest.id);
    if (guestSearch) guestSearch.value = guest.full_name;
    if (guestSelectedLabel) {
      guestSelectedLabel.classList.remove('hidden');
      const nameEl = guestSelectedLabel.querySelector('[data-guest-selected-name]');
      if (nameEl) nameEl.textContent = guest.full_name;
    }
    hideGuestResults();
  }

  async function searchGuests(query) {
    if (!guestSearchUrl || !guestResults) return;
    if (!query || query.length < 1) {
      hideGuestResults();
      return;
    }

    try {
      const res = await fetch(guestSearchUrl + '?q=' + encodeURIComponent(query), {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });
      const data = await res.json();
      const guests = data.guests || [];

      guestResults.innerHTML = '';
      if (!guests.length) {
        const li = document.createElement('li');
        li.className = 'px-3 py-2 text-body-sm text-on-surface-variant';
        li.textContent = 'No matching guests.';
        guestResults.appendChild(li);
        guestResults.classList.remove('hidden');
        return;
      }

      guests.forEach(function (guest) {
        const li = document.createElement('li');
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'block w-full px-3 py-2 text-left text-body-sm hover:bg-surface-container-low';
        btn.textContent =
          guest.full_name +
          (guest.phone ? ' · ' + guest.phone : '') +
          (guest.stay_count ? ' · ' + guest.stay_count + ' stay(s)' : '');
        btn.addEventListener('click', function () {
          selectGuest(guest);
        });
        li.appendChild(btn);
        guestResults.appendChild(li);
      });
      guestResults.classList.remove('hidden');
    } catch (err) {
      hideGuestResults();
    }
  }

  modeRadios.forEach(function (radio) {
    radio.addEventListener('change', syncGuestMode);
  });
  syncGuestMode();

  if (checkIn) {
    checkIn.addEventListener('change', onCheckInDateChange);
  }
  if (checkOut) {
    checkOut.addEventListener('change', refreshRooms);
  }
  if (roomSelect) {
    roomSelect.addEventListener('change', syncRate);
  }

  if (guestSearch) {
    guestSearch.addEventListener('input', function () {
      if (guestIdInput) guestIdInput.value = '';
      if (guestSelectedLabel) guestSelectedLabel.classList.add('hidden');
      clearTimeout(searchTimer);
      const q = guestSearch.value.trim();
      searchTimer = setTimeout(function () {
        searchGuests(q);
      }, 250);
    });

    guestSearch.addEventListener('focus', function () {
      if (guestSearch.value.trim()) {
        searchGuests(guestSearch.value.trim());
      }
    });
  }

  document.addEventListener('click', function (event) {
    if (!guestResults || !guestSearch) return;
    if (event.target === guestSearch || guestResults.contains(event.target)) return;
    hideGuestResults();
  });

  // Payment estimate on New Reservation
  const paymentBox = document.getElementById('payment-at-booking');
  const paymentAmount = document.getElementById('payment_amount');
  const includeTax = document.getElementById('payment_include_tax');
  const estimateNights = document.getElementById('estimate-nights');
  const estimateRoom = document.getElementById('estimate-room');
  const estimateTotal = document.getElementById('estimate-total');

  function nightsBetween(ci, co) {
    if (!ci || !co || co <= ci) return 0;
    const start = new Date(ci + 'T12:00:00');
    const end = new Date(co + 'T12:00:00');
    const days = Math.round((end - start) / 86400000);
    return Math.max(1, days);
  }

  function currentEstimate() {
    if (!paymentBox || !checkIn || !checkOut || !rateInput) {
      return { nights: 0, room: 0, tax: 0, total: 0 };
    }
    const taxRate = Number(paymentBox.getAttribute('data-tax-rate') || '0');
    const currency = paymentBox.getAttribute('data-currency') || '';
    const nights = nightsBetween(checkIn.value, checkOut.value);
    const rate = Number(rateInput.value || '0');
    const room = Math.round(nights * Math.max(0, rate) * 100) / 100;
    const taxOn = includeTax && includeTax.checked;
    const tax = taxOn && taxRate > 0 ? Math.round(room * taxRate * 100) / 100 : 0;
    const total = Math.round((room + tax) * 100) / 100;
    return { nights: nights, room: room, tax: tax, total: total, currency: currency };
  }

  function moneyLabel(amount, currency) {
    return (currency ? currency + ' ' : '') + Number(amount).toFixed(2);
  }

  function refreshEstimate() {
    if (!paymentBox) return;
    const est = currentEstimate();
    if (estimateNights) estimateNights.textContent = est.nights ? String(est.nights) : '—';
    if (estimateRoom) estimateRoom.textContent = est.nights ? moneyLabel(est.room, est.currency) : '—';
    if (estimateTotal) estimateTotal.textContent = est.nights ? moneyLabel(est.total, est.currency) : '—';
  }

  if (paymentBox) {
    ['change', 'input'].forEach(function (evt) {
      if (checkIn) checkIn.addEventListener(evt, refreshEstimate);
      if (checkOut) checkOut.addEventListener(evt, refreshEstimate);
      if (rateInput) rateInput.addEventListener(evt, refreshEstimate);
      if (includeTax) includeTax.addEventListener(evt, refreshEstimate);
    });

    const payFull = document.getElementById('pay-full-btn');
    const payHalf = document.getElementById('pay-half-btn');
    const payClear = document.getElementById('pay-clear-btn');

    if (payFull) {
      payFull.addEventListener('click', function () {
        const est = currentEstimate();
        if (paymentAmount && est.total > 0) paymentAmount.value = est.total.toFixed(2);
      });
    }
    if (payHalf) {
      payHalf.addEventListener('click', function () {
        const est = currentEstimate();
        if (paymentAmount && est.total > 0) {
          paymentAmount.value = (Math.round(est.total * 50) / 100).toFixed(2);
        }
      });
    }
    if (payClear) {
      payClear.addEventListener('click', function () {
        if (paymentAmount) paymentAmount.value = '';
      });
    }

    refreshEstimate();
  }
})();
