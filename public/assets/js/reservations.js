(function () {
  const form = document.getElementById('reservation-form');
  if (!form) return;

  const checkIn = document.getElementById('check_in_date');
  const checkOut = document.getElementById('check_out_date');
  const roomSelect = document.getElementById('room_id');
  const rateInput = document.getElementById('agreed_rate');
  const hint = document.getElementById('room-availability-hint');
  const url = form.getAttribute('data-availability-url');
  const exceptId = form.getAttribute('data-except-id') || '0';

  if (!checkIn || !checkOut || !roomSelect || !url) return;

  function formatMoney(amount) {
    const n = Number(amount);
    if (Number.isNaN(n)) return amount;
    return n.toFixed(2);
  }

  async function refreshRooms() {
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
    if (!rateInput) return;
    const opt = roomSelect.options[roomSelect.selectedIndex];
    if (opt && opt.dataset.rate) {
      rateInput.value = formatMoney(opt.dataset.rate);
    }
  }

  checkIn.addEventListener('change', refreshRooms);
  checkOut.addEventListener('change', refreshRooms);
  roomSelect.addEventListener('change', syncRate);
})();
