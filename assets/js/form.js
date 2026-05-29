(function(){
  const validateCheckboxGroup = (root, selector, message) => {
    const inputs = root.querySelectorAll(selector);
    if (!inputs.length) return true;
    const checked = Array.prototype.some.call(inputs, (input) => input.checked);
    inputs.forEach((input) => {
      input.setCustomValidity(checked ? '' : message);
    });
    return checked;
  };

  const validateSlotGroup = (root, message) => {
    const inputs = root.querySelectorAll('input[name="slot_datetime"]');
    if (!inputs.length) return true;
    const checked = Array.prototype.some.call(inputs, (input) => input.checked);
    inputs.forEach((input) => {
      input.setCustomValidity(checked ? '' : message);
    });
    return checked;
  };

  const activateLocation = (root, location) => {
    root.querySelectorAll('.esms-location-panel').forEach((panel) => {
      panel.classList.toggle('active', panel.getAttribute('data-location-panel') === location);
    });

    root.querySelectorAll('.esms-slots input[type="radio"]').forEach((input) => {
      if (input.checked) {
        const panel = input.closest('.esms-location-panel');
        if (panel && panel.getAttribute('data-location-panel') !== location) {
          input.checked = false;
        }
      }
    });
  };

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.esms-tab');
    if (!btn) return;
    const root = btn.closest('.esms-wrap');
    if (!root) return;
    const location = btn.getAttribute('data-location');
    const day = btn.getAttribute('data-day');
    const panelRoot = root.querySelector('.esms-location-panel[data-location-panel="' + location + '"]');
    if (!panelRoot) return;
    panelRoot.querySelectorAll('.esms-tab').forEach((t) => { t.classList.remove('active'); });
    panelRoot.querySelectorAll('.esms-slot-day').forEach((p) => { p.classList.remove('active'); });
    btn.classList.add('active');
    const panel = panelRoot.querySelector('.esms-slot-day[data-location="' + location + '"][data-day-panel="' + day + '"]');
    if (panel) panel.classList.add('active');
  });

  document.addEventListener('change', (e) => {
    const select = e.target.closest('#esms_meeting_location');
    if (select) {
      const root = select.closest('.esms-wrap');
      if (!root) return;
      activateLocation(root, select.value);
      const form = root.querySelector('form');
      const slotMessage = form ? (form.getAttribute('data-validation-slot') || 'Please select a date and time.') : 'Please select a date and time.';
      validateSlotGroup(root, slotMessage);
      return;
    }

    const changed = e.target;
    const wrap = changed.closest('.esms-wrap');
    if (!wrap) return;
    const form = wrap.querySelector('form');
    const segmentMessage = form ? (form.getAttribute('data-validation-segment') || 'Please select at least one market segment.') : 'Please select at least one market segment.';
    const genderMessage = form ? (form.getAttribute('data-validation-gender') || 'Please select at least one gender.') : 'Please select at least one gender.';
    const slotMessage = form ? (form.getAttribute('data-validation-slot') || 'Please select a date and time.') : 'Please select a date and time.';

    if (changed.matches('input[name="market_segments[]"]')) {
      validateCheckboxGroup(wrap, 'input[name="market_segments[]"]', segmentMessage);
      return;
    }

    if (changed.matches('input[name="target_gender"]')) {
      validateCheckboxGroup(wrap, 'input[name="target_gender"]', genderMessage);
      return;
    }

    if (changed.matches('input[name="slot_datetime"]')) {
      validateSlotGroup(wrap, slotMessage);
    }
  });

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.esms-wrap').forEach((root) => {
      const select = root.querySelector('#esms_meeting_location');
      if (select) {
        activateLocation(root, select.value);
      }

      const form = root.querySelector('form');
      if (!form) return;
      const segmentMessage = form.getAttribute('data-validation-segment') || 'Please select at least one market segment.';
      const genderMessage = form.getAttribute('data-validation-gender') || 'Please select at least one gender.';
      const slotMessage = form.getAttribute('data-validation-slot') || 'Please select a date and time.';

      form.addEventListener('submit', (e) => {
        const validSegments = validateCheckboxGroup(root, 'input[name="market_segments[]"]', segmentMessage);
        const validGenders = validateCheckboxGroup(root, 'input[name="target_gender"]', genderMessage);
        const validSlot = validateSlotGroup(root, slotMessage);

        if (!validSegments || !validGenders || !validSlot) {
          e.preventDefault();
          form.reportValidity();
        }
      });
    });
  });
})();
