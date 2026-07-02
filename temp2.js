      const batches = null;
      const batchSelect = document.getElementById('batchSelect');
      const languageSelect = document.getElementById('languageSelect');
      const trainingDateSelect = document.getElementById('trainingDateSelect');
      const seatAvailability = document.getElementById('seatAvailability');
      const sessionDisplay = document.getElementById('sessionDisplay');
      const batchNumberDisplay = document.getElementById('batchNumberDisplay');
      const bookSafetyForm = document.getElementById('bookSafetyForm');
      const selectedWorkerRows = document.getElementById('selectedWorkerRows');
      const workerNameSearch = document.getElementById('workerNameSearch');
      const workerAadhaarSearch = document.getElementById('workerAadhaarSearch');
      const workerTempSearch = document.getElementById('workerTempSearch');
      const preselectWorkerId = null;
      let userTouchedSelection = preselectWorkerId > 0;

      function norm(value) {
        return String(value || '').trim().toLowerCase();
      }

      function remainingSeats(batch) {
        return Math.max(0, Number(batch?.seats_available ?? (Number(batch?.capacity || 0) - Number(batch?.selected_count || 0))));
      }

      function fallbackTrainingDates() {
        const dates = [];
        const cursor = new Date();
        for (let i = 1; i <= 30; i++) {
          const d = new Date(cursor);
          d.setDate(cursor.getDate() + i);
          const day = d.getDay();
          if (day === 0) continue;
          const value = d.toISOString().slice(0, 10);
          dates.push({ id: 'manual_' + value, training_date: value, session_name: 'FN', batch_number: 'Preferred date', manual: true });
          if (dates.length >= 12) break;
        }
        return dates;
      }

      function matchingBatches() {
        const language = norm(languageSelect.value);
        return batches.filter(batch => !language || norm(batch.language_name) === language);
      }

      function populateDates() {
        let rows = matchingBatches();
        const hasScheduledRows = rows.length > 0;
        if (!hasScheduledRows) {
          rows = fallbackTrainingDates();
        }
        trainingDateSelect.innerHTML = '<option value="">Select</option>' + rows.map(batch => {
          const label = batch.manual
            ? `${batch.training_date} - preferred booking (Safety will schedule)`
            : `${batch.training_date || ''}${batch.session_name ? ' - ' + batch.session_name : ''}`;
          return `<option value="${batch.id}" data-manual="${batch.manual ? '1' : '0'}" data-date="${batch.training_date}" data-session="${batch.session_name || 'FN'}">${escapeHtml(label)}</option>`;
        }).join('');
        if (rows.length) {
          trainingDateSelect.value = String(rows[0].id);
        }
        applySelectedBatch(true);
      }

      function selectedBatch() {
        const id = Number(trainingDateSelect.value || 0);
        return batches.find(batch => Number(batch.id) === id) || null;
      }

      function applySelectedBatch(autoTick) {
        const selectedOption = trainingDateSelect.selectedOptions[0];
        const isManual = selectedOption?.dataset.manual === '1';

        if (isManual) {
          batchSelect.removeAttribute('required');
          batchSelect.value = '';
          document.getElementById('manualDate').value = selectedOption.dataset.date;
          document.getElementById('manualSession').value = selectedOption.dataset.session;

          seatAvailability.textContent = 'Unlimited';
          sessionDisplay.value = selectedOption.dataset.session;
          batchNumberDisplay.value = 'Preferred Date';
        } else {
          const batch = selectedBatch();
          batchSelect.value = batch ? batch.id : '';
          document.getElementById('manualDate').value = '';
          document.getElementById('manualSession').value = '';

          seatAvailability.textContent = batch ? remainingSeats(batch) : '0';
          sessionDisplay.value = batch?.session_name || '';
          batchNumberDisplay.value = batch?.batch_number || '';

          if (batch && batch.language_name && languageSelect.value !== batch.language_name) {
            languageSelect.value = batch.language_name;
          }
        }

        filterWorkers();
        if (autoTick) autoSelectWorkers();
        refreshSelectedWorkers();
      }

      function filterWorkers() {
        const language = norm(languageSelect.value);
        const nameTerm = norm(workerNameSearch.value);
        const aadhaarTerm = norm(workerAadhaarSearch.value);
        const tempTerm = norm(workerTempSearch.value);
        document.querySelectorAll('[data-worker-row]').forEach(row => {
          const visible = (!language || !row.dataset.language || row.dataset.language === language)
            && (!nameTerm || row.dataset.name.includes(nameTerm))
            && (!aadhaarTerm || row.dataset.aadhaar.includes(aadhaarTerm))
            && (!tempTerm || row.dataset.temp.includes(tempTerm));
          row.style.display = visible ? '' : 'none';
          if (!visible && row.dataset.language && row.dataset.language !== language) {
            const checkbox = row.querySelector('.worker-check');
            if (checkbox) checkbox.checked = false;
          }
        });
      }

      function autoSelectWorkers() {
        const selectedOption = trainingDateSelect.selectedOptions[0];
        const isManual = selectedOption?.dataset.manual === '1';
        const batch = selectedBatch();
        const limit = isManual ? 99999 : remainingSeats(batch);
        const language = norm(languageSelect.value);
        const rows = Array.from(document.querySelectorAll('[data-worker-row]'))
          .filter(row => !row.dataset.language || row.dataset.language === language);
        rows.forEach(row => {
          const checkbox = row.querySelector('.worker-check');
          if (checkbox) checkbox.checked = false;
        });
        if (!isManual && (!batch || !limit)) return;
        let selected = 0;
        if (preselectWorkerId) {
          const preselected = document.querySelector(`.worker-check[value="${preselectWorkerId}"]`);
          if (preselected) {
            const rowLang = preselected.closest('[data-worker-row]')?.dataset.language;
            if (!rowLang || rowLang === language) {
              preselected.checked = true;
              selected = 1;
            }
          }
        }
        rows.forEach(row => {
          if (selected >= limit) return;
          const checkbox = row.querySelector('.worker-check');
          if (!checkbox || checkbox.checked) return;
          checkbox.checked = true;
          selected++;
        });
      }

      function refreshSelectedWorkers() {
        const selected = Array.from(document.querySelectorAll('.worker-check:checked'));
        if (!selected.length) {
          selectedWorkerRows.innerHTML = '<tr><td colspan="3" style="text-align:center;color:#64748b;">No workmen selected.</td></tr>';
          return;
        }
        selectedWorkerRows.innerHTML = selected.map((input, index) => {
          return `<tr><td>${index + 1}</td><td>${escapeHtml(input.dataset.workerAadhaar || '-')}</td><td>${escapeHtml(input.dataset.workerName || 'Worker')}</td></tr>`;
        }).join('');
      }

      function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
      }

      languageSelect?.addEventListener('change', () => {
        userTouchedSelection = false;
        populateDates();
      });
      trainingDateSelect?.addEventListener('change', () => {
        userTouchedSelection = false;
        applySelectedBatch(true);
      });
      [workerNameSearch, workerAadhaarSearch, workerTempSearch].forEach(input => input?.addEventListener('input', () => {
        filterWorkers();
        refreshSelectedWorkers();
      }));

      // Filter Date dropdown based on the checked workers' existing batch assignment
      function applyBatchFilterFromSelectedWorkers() {
        const checkedRows = Array.from(document.querySelectorAll('.worker-check:checked')).map(cb => cb.closest('[data-worker-row]'));
        // Find the first checked worker that is already in a batch
        const workerWithBatch = checkedRows.find(row => row && Number(row.dataset.batchId) > 0);
        if (workerWithBatch) {
          const fixedBatchId = Number(workerWithBatch.dataset.batchId);
          const fixedBatchNumber = workerWithBatch.dataset.batchNumber || '';
          // Repopulate date select showing ONLY this batch
          const matchedBatch = batches.find(b => Number(b.id) === fixedBatchId);
          const rows = matchedBatch ? [matchedBatch] : [];
          trainingDateSelect.innerHTML = '<option value="">Select</option>' + rows.map(batch => {
            const label = `${batch.training_date || ''}${batch.session_name ? ' - ' + batch.session_name : ''}`;
            return `<option value="${batch.id}" data-manual="0" data-date="${batch.training_date}" data-session="${batch.session_name || 'FN'}">${escapeHtml(label)} (Batch: ${escapeHtml(batch.batch_number || fixedBatchNumber)})</option>`;
          }).join('');
          if (rows.length) {
            trainingDateSelect.value = String(rows[0].id);
            trainingDateSelect.disabled = true;
            trainingDateSelect.title = 'Batch is fixed for the selected worker\'s current enrollment';
          } else {
            // batch exists in training_requests but may be past/closed — show batch number as info
            trainingDateSelect.innerHTML = `<option value="" selected>Batch: ${escapeHtml(fixedBatchNumber)} (already assigned)</option>`;
            trainingDateSelect.disabled = true;
            trainingDateSelect.title = 'This worker is already assigned to a batch';
          }
          applySelectedBatch(false);
          if (fixedBatchNumber) {
            batchNumberDisplay.value = fixedBatchNumber;
          }
        } else {
          // No checked worker has a fixed batch — restore all batches for the selected language
          trainingDateSelect.disabled = false;
          trainingDateSelect.title = '';
          populateDates();
        }
      }

      document.addEventListener('change', event => {
        if (!event.target.classList.contains('worker-check')) return;
        userTouchedSelection = true;
        const batch = selectedBatch();
        const limit = remainingSeats(batch);
        const selected = document.querySelectorAll('.worker-check:checked').length;
        if (limit && selected > limit) {
          event.target.checked = false;
          alert('Maximum seat limit exceeded. Seat availability is ' + limit + '.');
        }
        refreshSelectedWorkers();
        applyBatchFilterFromSelectedWorkers();
      });
      bookSafetyForm?.addEventListener('submit', event => {
        const selectedOption = trainingDateSelect.selectedOptions[0];
        const isManual = selectedOption?.dataset.manual === '1';
        const batch = selectedBatch();
        const remaining = isManual ? 99999 : remainingSeats(batch);
        const selected = document.querySelectorAll('.worker-check:checked').length;
        
        const dateValue = trainingDateSelect.value;
        if (!dateValue) {
          event.preventDefault();
          alert('Please select language, date and session.');
          return;
        }
        if (!selected) {
          event.preventDefault();
          alert('Please select at least one worker.');
          return;
        }
        if (selected > remaining) {
          event.preventDefault();
          alert('Maximum seat limit exceeded.');
        }
      });

      const preselectLanguage = null;
      if (preselectLanguage && languageSelect) {
        let matched = false;
        for (let i = 0; i < languageSelect.options.length; i++) {
          if (languageSelect.options[i].value === preselectLanguage) {
            languageSelect.selectedIndex = i;
            matched = true;
            break;
          }
        }
        if (!matched && languageSelect.options.length > 1) {
          languageSelect.selectedIndex = 1;
        }
      }
      populateDates();
